<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Your name, your address and your password.
 *
 * Both forms hand off to the Fortify actions rather than writing the user
 * here, so the rules an invite is checked against are the rules a change is
 * checked against. Anything that needs the password re-typed lives on
 * /account/security instead, which is a separate route so that page can carry
 * the confirmation middleware without putting it in front of a name change.
 */
new #[Layout('layouts::tool')] #[Title('Account')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = $this->user();

        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function saveProfile(UpdatesUserProfileInformation $updater): void
    {
        // Livewire only clears the error bag when validation runs, and the rules
        // live in the Fortify action. Without this, a failed attempt keeps its
        // message under the field after the next one works.
        $this->resetValidation();

        $updater->update($this->user(), [
            'name' => $this->name,
            'email' => $this->email,
        ]);

        Flux::toast('Profile saved.', variant: 'success');
    }

    public function savePassword(UpdatesUserPasswords $updater): void
    {
        $this->resetValidation();

        $updater->update($this->user(), [
            'current_password' => $this->current_password,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        // Every field, not just the two new ones. A password left in a
        // component's state is a password sitting in the page payload.
        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast('Password changed.', variant: 'success');
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    public function with(): array
    {
        return ['user' => $this->user()];
    }
}; ?>

<div class="px-(--ds-gutter) py-(--ds-section)">
    <x-ds::page-header title="Account" class="mb-(--ds-section)">
        <x-slot:description>Signed in as <span class="font-medium text-(--ds-text-strong)">{{ $user->email }}</span>.</x-slot:description>

        <flux:button :href="route('account.security')" wire:navigate icon="shield-check" variant="ghost">Security</flux:button>
    </x-ds::page-header>

    <div class="max-w-2xl space-y-(--ds-section)">
        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card)">
            <x-ds::section-heading level="2">Profile</x-ds::section-heading>
            <flux:text class="mt-1">The name on invites and the address every notification from here goes to.</flux:text>

            <form wire:submit="saveProfile" class="mt-6 space-y-4">
                <flux:input wire:model="name" label="Name" type="text" autocomplete="name" required />
                <flux:input wire:model="email" label="Email" type="email" autocomplete="email" required />

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">Save</flux:button>
                </div>
            </form>
        </section>

        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card)">
            <x-ds::section-heading level="2">Password</x-ds::section-heading>
            {{-- Said outright, because every account here starts with a password
                 `fleet:invite` printed into somebody else's terminal. --}}
            <flux:text class="mt-1">If yours came from an invite, somebody else has seen it. Change it once and that stops being true.</flux:text>

            <form wire:submit="savePassword" class="mt-6 space-y-4">
                <flux:input wire:model="current_password" label="Current password" type="password" autocomplete="current-password" viewable required />
                <flux:input wire:model="password" label="New password" type="password" autocomplete="new-password" viewable required />
                <flux:input wire:model="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" viewable required />

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">Change password</flux:button>
                </div>
            </form>
        </section>
    </div>
</div>
