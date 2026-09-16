<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * What a person's terminal carries.
 *
 * `design:apply` and `design:update` pull a pack from here, and they run on a
 * laptop where GitHub cannot vouch for anything. This is the credential for
 * that: issued to a person, named after the machine it lives on, revocable on
 * its own.
 *
 * Deliberately not a repository's credential. A repository proves itself to
 * GitHub on every CI run and holds nothing, which is the whole point of the
 * change that brought this in.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $token_hash
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['name'])]
#[Hidden(['token_hash'])]
class PersonalToken extends Model
{
    /**
     * Said out loud in the value, so one found in a log or a pasted file reads
     * as a credential rather than as forty characters of noise. It is also
     * what lets a wrong value be refused before it costs a query.
     */
    public const PREFIX = 'dsn_';

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A new token for somebody, returned once and never again.
     *
     * The row keeps a sha256 of it, so nothing here can show a token back to
     * whoever lost it, only replace it. sha256 rather than bcrypt for the same
     * reason a site's token uses it: there are forty random characters to
     * guess, and every pull would otherwise pay for a slow hash.
     */
    public static function issueFor(User $user, string $name): string
    {
        $token = self::PREFIX.Str::random(40);

        $row = new self(['name' => $name]);
        $row->user_id = $user->id;
        // Not fillable, so it is set rather than passed: a token_hash arriving
        // in an array of attributes is a token_hash somebody could send.
        $row->token_hash = self::hash($token);
        $row->save();

        return $token;
    }

    /**
     * The token behind a value, or null, with its use stamped.
     *
     * Constant time by virtue of the index, and the same answer for a missing
     * token and a wrong one.
     */
    public static function byToken(string $token): ?self
    {
        $token = trim($token);

        if (! str_starts_with($token, self::PREFIX)) {
            return null;
        }

        $found = self::query()->where('token_hash', self::hash($token))->first();

        $found?->stampUse();

        return $found;
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * At most once a minute. Every pull would otherwise be a write, and the
     * question this answers is "when did this last do anything", which does
     * not need to be answered to the second.
     */
    private function stampUse(): void
    {
        if ($this->last_used_at !== null && $this->last_used_at->gt(now()->subMinute())) {
            return;
        }

        $this->forceFill(['last_used_at' => now()])->save();
    }
}
