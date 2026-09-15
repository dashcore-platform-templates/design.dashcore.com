<?php

namespace App\Models;

use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
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
 * @property string $host
 * @property string|null $design_slug
 * @property string $token_hash
 * @property Carbon|null $last_reported_at
 */
class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory;

    protected $fillable = ['name', 'host', 'design_slug'];

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
}
