<?php

namespace App\Models;

use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * An app running the standard.
 *
 * Sites are added here by a person, who then puts the token into that app's
 * CI. A report authenticates as a site rather than claiming to be one, so the
 * table cannot be filled by anything that finds the endpoint.
 *
 * @property int $id
 * @property string $name
 * @property string $repository
 * @property string|null $design_slug
 * @property string $token_hash
 * @property Carbon|null $last_reported_at
 */
class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory;

    /** Where the fleet's repositories live. Only the prefix; the row holds owner/name. */
    public const GITHUB = 'https://github.com';

    protected $fillable = ['name', 'repository', 'design_slug'];

    /** The token itself is never readable, and the hash is never sent anywhere. */
    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['last_reported_at' => 'datetime'];
    }

    /**
     * A site is never without a token hash, even before anybody issues one.
     *
     * Otherwise creating a site means inventing a hash first, which is a
     * chicken and egg nobody should have to solve to add a row. The hash put
     * here is of a token nobody has ever seen, so the site simply cannot
     * report until a real one is issued, which is the right default.
     */
    protected static function booted(): void
    {
        static::creating(function (self $site): void {
            if (($site->token_hash ?? '') === '') {
                $site->token_hash = self::hash(Str::random(40));
            }
        });
    }

    /**
     * A new token, returned once and never again.
     *
     * sha256 rather than bcrypt: a token is 40 random characters, so there is
     * nothing to brute force, and every report would otherwise pay for a slow
     * hash. This is what Sanctum does, for the same reason.
     */
    public function issueToken(): string
    {
        $token = Str::random(40);

        $this->token_hash = self::hash($token);
        $this->save();

        return $token;
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Where this app's secrets are set, so the instructions are a link rather
     * than a route somebody follows by hand.
     *
     * The row holds owner/name, so this is exact rather than assembled from a
     * convention. It used to be built from a hostname by assuming every repo
     * is named after the site it serves, which is true across the fleet today
     * and was never a fact.
     */
    public function secretsUrl(): string
    {
        return self::GITHUB.'/'.$this->repository.'/settings/secrets/actions';
    }

    /** The repository itself, for the link on the board. */
    public function repositoryUrl(): string
    {
        return self::GITHUB.'/'.$this->repository;
    }

    /** The site a token belongs to, or null. Constant time by virtue of the index. */
    public static function byToken(string $token): ?self
    {
        $token = trim($token);

        return $token === '' ? null : self::query()->where('token_hash', self::hash($token))->first();
    }

    /** @return HasMany<SiteReport, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(SiteReport::class);
    }

    /** @return HasMany<SiteReport, $this> */
    public function latestReport(): HasMany
    {
        return $this->reports()->latest('reported_at')->limit(1);
    }

    /**
     * Which repositories use which design, keyed by slug.
     *
     * Read from each site's newest report, because a repo that moved to a
     * different design last week is not still using the old one. It is what
     * the delete modal shows instead of guessing out loud.
     *
     * Only as good as the reports: a repo whose CI never reports here is not
     * in this at all, so an empty list means nobody has said so rather than
     * nobody using it.
     *
     * @return Collection<string, Collection<int, string>>
     */
    public static function usage(): Collection
    {
        /** @var array<string, list<string>> $usage */
        $usage = [];

        foreach (self::query()->with('latestReport')->get() as $site) {
            $slug = $site->latestReport->first()?->design_slug;

            if ($slug === null || $slug === '') {
                continue;
            }

            $usage[$slug][] = $site->repository;
        }

        ksort($usage);

        return collect($usage)->map(function (array $repositories): Collection {
            sort($repositories);

            return collect($repositories);
        });
    }
}
