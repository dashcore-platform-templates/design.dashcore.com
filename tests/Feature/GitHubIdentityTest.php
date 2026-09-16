<?php

namespace Tests\Feature;

use App\Fleet\GitHubIdentity;
use App\Models\Site;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A repository proving which one it is, without a secret anywhere.
 *
 * These sign with a keypair made here and serve it as GitHub's key set, so the
 * real verification runs: signature, issuer, audience, expiry. Nothing is
 * stubbed except who published the key.
 */
class GitHubIdentityTest extends TestCase
{
    use RefreshDatabase;

    private string $privateKey;

    /** @var array<string,mixed> */
    private array $jwks;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('fleet.github-oidc-keys');

        // Any request these tests did not ask for is a test reaching the real
        // internet, which is how the one below quietly called GitHub.
        Http::preventStrayRequests();

        // A throwaway pair kept as a fixture rather than made per run. Making
        // one needs an openssl.cnf that is somewhere different on every
        // machine, and these tests are about verification, not key generation.
        // The private half signs nothing real and is in the repository on
        // purpose, which is why it says so in its own name.
        $this->privateKey = file_get_contents(base_path('tests/fixtures/oidc-signing-key.pem'));
        $this->jwks = json_decode(file_get_contents(base_path('tests/fixtures/oidc-jwks.json')), true);

    }

    /** What GitHub publishes, for the tests that want the ordinary case. */
    private function serveKeys(): void
    {
        Http::fake([GitHubIdentity::JWKS => Http::response($this->jwks)]);
    }

    #[Test]
    public function a_signed_token_names_its_repository(): void
    {
        $this->serveKeys();

        $this->assertSame(
            'dashcoretech/newproject.dashcore.com',
            GitHubIdentity::repository($this->token())
        );
    }

    #[Test]
    public function a_token_for_somebody_else_is_refused(): void
    {
        $this->serveKeys();

        // The audience is the whole reason a token cannot be lifted out of a
        // workflow that talks to a different service and replayed here.
        $this->assertNull(GitHubIdentity::repository($this->token(['aud' => 'https://someone-else.example'])));
    }

    #[Test]
    public function a_token_from_somewhere_other_than_github_is_refused(): void
    {
        $this->serveKeys();

        $this->assertNull(GitHubIdentity::repository($this->token(['iss' => 'https://token.actions.githubusercontent.com.evil.example'])));
    }

    #[Test]
    public function an_expired_token_is_refused(): void
    {
        $this->serveKeys();

        $this->assertNull(GitHubIdentity::repository($this->token(['exp' => time() - 3600, 'iat' => time() - 7200])));
    }

    #[Test]
    public function a_token_signed_by_the_wrong_key_is_refused(): void
    {
        // The signature is the point. Everything else in the token is a claim
        // anybody can write. This one is signed by a key GitHub never
        // published, and says all the right things.
        $this->serveKeys();

        $stranger = file_get_contents(base_path('tests/fixtures/oidc-stranger-key.pem'));
        $forged = JWT::encode($this->claims(), $stranger, 'RS256', 'dashcore-test-key');

        $this->assertNull(GitHubIdentity::repository($forged));
    }

    #[Test]
    public function nonsense_is_refused_without_reaching_github(): void
    {
        foreach (['', '   ', 'not-a-jwt', 'a.b.c'] as $rubbish) {
            $this->assertNull(GitHubIdentity::repository($rubbish));
        }
    }

    #[Test]
    public function a_repository_name_that_is_not_owner_slash_name_is_refused(): void
    {
        $this->serveKeys();

        // It is about to become a row and a link, so it is checked rather than
        // trusted even after the signature holds.
        $this->assertNull(GitHubIdentity::repository($this->token(['repository' => '../../etc/passwd'])));
        $this->assertNull(GitHubIdentity::repository($this->token(['repository' => 'no-owner'])));
    }

    #[Test]
    public function a_failed_key_fetch_is_not_remembered(): void
    {
        // One bad minute at GitHub, then a good one. Caching the empty answer
        // would turn the first into an hour of refused reports.
        Http::fakeSequence(GitHubIdentity::JWKS)
            ->push('', 503)
            ->push($this->jwks);

        $this->assertNull(GitHubIdentity::repository($this->token()));
        $this->assertSame('dashcoretech/newproject.dashcore.com', GitHubIdentity::repository($this->token()));
    }

    #[Test]
    public function reporting_with_one_puts_the_repository_on_the_board(): void
    {
        $this->serveKeys();

        // The row appears because the repository reported, not because somebody
        // declared it first. That is the whole point of the change.
        $this->assertSame(0, Site::query()->count());

        $this->withToken($this->token())
            ->postJson('/api/reports', [
                'findings' => ['Flux badge' => 2],
                'files_with_findings' => 1,
                'files_checked' => 40,
            ])
            ->assertCreated();

        $site = Site::query()->sole();
        $this->assertSame('dashcoretech/newproject.dashcore.com', $site->repository);
        $this->assertSame(1, $site->reports()->count());
    }

    #[Test]
    public function a_second_report_joins_the_same_row(): void
    {
        $this->serveKeys();

        foreach ([1, 2] as $_) {
            $this->withToken($this->token())
                ->postJson('/api/reports', [
                    'findings' => [],
                    'files_with_findings' => 0,
                    'files_checked' => 40,
                ])
                ->assertCreated();
        }

        $this->assertSame(1, Site::query()->count());
        $this->assertSame(2, Site::query()->sole()->reports()->count());
    }

    #[Test]
    public function the_token_a_site_already_holds_still_works(): void
    {
        $this->serveKeys();

        // Every app in the fleet is on one today. A gate that turns fifteen
        // apps off is not a gate.
        $site = Site::factory()->create();
        $token = $site->issueToken();

        $this->withToken($token)
            ->postJson('/api/reports', [
                'findings' => [],
                'files_with_findings' => 0,
                'files_checked' => 12,
            ])
            ->assertCreated();

        $this->assertSame(1, Site::query()->count());
    }

    /** @param array<string,mixed> $overrides */
    private function token(array $overrides = []): string
    {
        return JWT::encode(array_merge($this->claims(), $overrides), $this->privateKey, 'RS256', 'dashcore-test-key');
    }

    /** @return array<string,mixed> */
    private function claims(): array
    {
        return [
            'iss' => GitHubIdentity::ISSUER,
            'aud' => GitHubIdentity::audience(),
            'repository' => 'dashcoretech/newproject.dashcore.com',
            'ref' => 'refs/heads/master',
            'iat' => time() - 10,
            'exp' => time() + 300,
        ];
    }
}
