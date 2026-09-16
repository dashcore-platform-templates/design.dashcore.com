<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

/**
 * Changing a password, which every account here has to do once.
 *
 * fleet:invite generates one and prints it, so the first password a person has
 * is one somebody else has seen. This is where that stops being true.
 */
class UpdateUserPassword implements UpdatesUserPasswords
{
    /**
     * @param  array<string,mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            // The current one, because a borrowed session should not be able to
            // lock the owner out of their own account.
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ], [
            'current_password.current_password' => __('That is not your current password.'),
        ])->validateWithBag('updatePassword');

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
