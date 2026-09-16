<?php

namespace App\Actions\Fortify;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

/**
 * Changing a name or an email address.
 *
 * The rules come from the same trait CreateNewUser uses, so an address that
 * could not be invited cannot be moved to either.
 */
class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    use ProfileValidationRules;

    /**
     * @param  array<string,mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, $this->profileRules($user->id))->validateWithBag('updateProfileInformation');

        // A new address is unverified until the person proves they hold it, so
        // the verification is reissued and the old stamp cleared in one write.
        if ($input['email'] !== $user->email && $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);

            return;
        }

        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
        ])->save();
    }

    /**
     * @param  array<string,mixed>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
