<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Take the admins from a client brief.
 *
 * fleets holds what a client told Dashcore for their launch, and hands it to
 * each app of their new fleet with `brief:import --base64=…`. login is the
 * only way in and apps never make accounts on sign-in, so the people who run
 * the fleet need an account here before they first arrive.
 *
 * design has no roles: everybody with an account sees everything. So each
 * admin, owner first, gets a user with a random password nobody is shown and
 * an address marked verified, because login has already proved it. Nobody is
 * emailed.
 *
 * Not `fleet:invite`, which refuses an address it has seen and prints a
 * password. This is safe to run again: an existing account is left exactly
 * as it is.
 */
class ImportClientBriefCommand extends Command
{
    public const FORMAT = 'dashcore.client-brief/1';

    protected $signature = 'brief:import
        {--file= : A client brief (JSON)}
        {--base64= : The same brief, base64-encoded, for a host with nowhere to put a file}';

    protected $description = 'Make accounts for the admins in a client brief that fleets delivers';

    public function handle(): int
    {
        $file = $this->option('file');
        $base64 = $this->option('base64');

        $json = match (true) {
            is_string($file) && $file !== '' => is_readable($file) ? file_get_contents($file) : null,
            is_string($base64) && $base64 !== '' => base64_decode($base64, true),
            default => null,
        };
        $brief = is_string($json) ? json_decode($json, true) : null;

        if (! is_array($brief)) {
            $this->components->error('Give a readable client brief with --file or --base64.');

            return self::FAILURE;
        }

        if (($brief['format'] ?? null) !== self::FORMAT) {
            $this->components->error('Not a client brief (expected format '.self::FORMAT.').');

            return self::FAILURE;
        }

        $admins = collect(is_array($brief['admins'] ?? null) ? $brief['admins'] : [])
            ->filter(fn ($admin): bool => is_array($admin) && is_string($admin['email'] ?? null) && filter_var(trim($admin['email']), FILTER_VALIDATE_EMAIL) !== false)
            ->values()
            // The owner first. An older brief marks nobody, and fleets has
            // always put the owner at the top, so its order stands.
            ->sortBy(fn (array $admin, int $index): array => [($admin['owner'] ?? false) === true ? 0 : 1, $index])
            ->unique(fn (array $admin): string => Str::lower(trim($admin['email'])))
            ->values();

        if ($admins->isEmpty()) {
            $this->line('Nothing for design in this brief.');

            return self::SUCCESS;
        }

        $created = [];
        $already = [];

        foreach ($admins as $admin) {
            // Sign-in matches the lowercased address, so that is what is stored.
            $email = Str::lower(trim($admin['email']));

            if (User::query()->whereRaw('lower(email) = ?', [$email])->exists()) {
                $already[] = $email;

                continue;
            }

            $user = new User([
                'name' => is_string($admin['name'] ?? null) && filled($admin['name']) ? trim($admin['name']) : Str::of($email)->before('@')->replace(['.', '+'], ' ')->headline()->value(),
                'email' => $email,
                // Never shown to anybody: they come in through login.
                'password' => Str::password(64),
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $created[] = $email;
        }

        $this->line(collect([
            $created !== [] ? 'Accounts made: '.implode(', ', $created).'.' : null,
            $already !== [] ? 'Already had an account, left alone: '.implode(', ', $already).'.' : null,
        ])->filter()->implode(' '));

        return self::SUCCESS;
    }
}
