<?php

namespace App\Fleet;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Which repository a CI request is coming from, proved rather than claimed.
 *
 * A workflow with `permissions: id-token: write` can ask GitHub for a short
 * signed document saying which repository the job is in, on which ref, and for
 * which audience. GitHub signs it; the public half of that key is published.
 * So nothing here holds a secret for a repository, and a repository cannot
 * name itself as a different one, because it did not write the document.
 *
 * This replaces a token pasted into every repo's Actions secrets. That was the
 * only step in connecting an app that needed a browser, which made it the only
 * one an agent could not do, and the one that quietly did not get done. A
 * missing line in a workflow file is visible to anything that reads the repo;
 * a missing GitHub secret is visible to nobody.
 *
 * Only inside GitHub Actions. There is no such document on a laptop, which is
 * the right shape here: CI reports, and a person uses their own token for
 * everything they run themselves.
 */
final class GitHubIdentity
{
    public const ISSUER = 'https://token.actions.githubusercontent.com';

    public const JWKS = self::ISSUER.'/.well-known/jwks';

    /**
     * GitHub publishes a new signing key well before it signs anything with
     * it, so an hour-old copy has the key a fresh token was signed by. Cached
     * rather than fetched per report, because otherwise GitHub being slow is
     * this endpoint being slow, and a bad token would be a free way to make us
     * call them.
     */
    private const KEYS_FOR = 3600;

    private const CACHE_KEY = 'fleet.github-oidc-keys';

    /**
     * A GitHub Actions token is minutes old and CI clocks drift.
     */
    private const LEEWAY = 60;

    /**
     * The repository the token proves, or null.
     *
     * Null for every failure and never an exception, because the caller's
     * answer is the same either way: this request does not get to say who it
     * is. Nothing here reports which check failed, for the same reason the
     * report endpoint gives one answer to a missing token and a wrong one.
     */
    public static function repository(string $token): ?string
    {
        $claims = self::claims($token);

        $repository = $claims['repository'] ?? null;

        // owner/name, the shape the rest of the fleet is keyed by. Checked
        // rather than trusted: it is about to become a row and a link.
        return is_string($repository) && preg_match('/^[A-Za-z0-9._-]+\/[A-Za-z0-9._-]+$/', $repository) === 1
            ? $repository
            : null;
    }

    /**
     * The audience a token has to name.
     *
     * The workflow asks GitHub for a token with this audience and this checks
     * it, which is what stops a token minted for somebody else's service from
     * working here, and ours from working there.
     */
    public static function audience(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /**
     * @return array<string,mixed>
     */
    private static function claims(string $token): array
    {
        // Shape first, so rubbish costs nothing. Without this, anything at
        // all arriving in the header would send us to fetch GitHub's keys,
        // which makes a bad request a free way to put load on them and on us.
        if (preg_match('/^[\w-]+\.[\w-]+\.[\w-]+$/', trim($token)) !== 1) {
            return [];
        }

        $keys = self::keys();

        if ($keys === []) {
            return [];
        }

        try {
            JWT::$leeway = self::LEEWAY;

            // RS256 named here rather than read out of the token's own header.
            // A verifier that believes the header about which algorithm to use
            // is a verifier that can be told "none".
            $decoded = JWT::decode($token, JWK::parseKeySet($keys, 'RS256'));
        } catch (Throwable) {
            return [];
        }

        $claims = (array) $decoded;

        if (($claims['iss'] ?? null) !== self::ISSUER) {
            return [];
        }

        // decode() checks the signature and the expiry. The audience is ours.
        $audience = $claims['aud'] ?? null;
        $audiences = is_array($audience) ? $audience : [$audience];

        return in_array(self::audience(), $audiences, true) ? $claims : [];
    }

    /**
     * @return array<string,mixed>
     */
    private static function keys(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        try {
            $response = Http::timeout(10)->acceptJson()->get(self::JWKS);
        } catch (Throwable) {
            return [];
        }

        if ($response->failed()) {
            return [];
        }

        $keys = (array) $response->json();

        // Only a real answer is kept. Caching an empty set would turn one bad
        // minute at GitHub into an hour of refused reports.
        if ($keys !== []) {
            Cache::put(self::CACHE_KEY, $keys, self::KEYS_FOR);
        }

        return $keys;
    }
}
