<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * php artisan fleet:invite "Travis" travis@dashcore.com
 *
 * The only way to make an account. Registration is off, because the fleet
 * page lists every Dashcore product and how far off the standard each one is,
 * and a tool that anybody can sign up to is not the place for that.
 *
 * The password is generated and printed once. Whoever receives it changes it,
 * and nobody has to invent one or send one that was invented for them.
 */
class FleetInviteCommand extends Command
{
    protected $signature = 'fleet:invite {name : Their name} {email : Their email address}';

    protected $description = 'Make an account for somebody who needs the fleet view';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $email = (string) $this->argument('email');

        $validator = Validator::make(['name' => $name, 'email' => $email], [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        // Long enough that it does not need to be memorable, because it is not
        // meant to be kept.
        $password = Str::password(24);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $this->components->info("Account made for {$user->email}.");
        $this->line('');
        $this->components->twoColumnDetail('<fg=cyan>Password</>', $password);
        $this->line('');
        // Generated rather than invented, and printed into somebody else's
        // terminal, so /account leads with telling them to change it.
        $this->line('  <fg=gray>Shown once. Send it to them; they change it at '.route('account').'.</>');

        return self::SUCCESS;
    }

    /** Kept so the password rules stay one thing rather than two. */
    public static function passwordRules(): Password
    {
        return Password::default();
    }
}
