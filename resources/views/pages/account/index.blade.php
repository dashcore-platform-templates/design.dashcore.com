<?php

use App\Models\PersonalToken;
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

    public string $tokenName = '';

    /** Shown once, straight after issuing. Never read back from anywhere. */
    public ?string $issued = null;

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

    /**
     * A token for one machine, shown once.
     *
     * CI proves which repository it is to GitHub and carries nothing. This is
     * for the things a person runs, where there is nothing to prove it to:
     * `design:apply` and `design:update` pulling a pack from here.
     */
    public function issueToken(): void
    {
        // The route already carries auth, so this is the second gate: a public
        // Livewire method is callable past the markup that offers it.
        abort_unless(Auth::check(), 403);

        $this->resetValidation();

        $this->validate([
            // Named after the machine it will live on, because a list of
            // identical rows is a list nobody can act on.
            'tokenName' => ['required', 'string', 'max:60'],
        ]);

        $this->issued = PersonalToken::issueFor($this->user(), $this->tokenName);
        $this->reset('tokenName');

        Flux::modal('new-token')->close();
    }

    public function revokeToken(int $tokenId): void
    {
        abort_unless(Auth::check(), 403);

        // Scoped to this person's own, so an id from somewhere else is a row
        // that does not exist rather than somebody else's token.
        $this->user()->personalTokens()->whereKey($tokenId)->delete();

        $this->reset('issued');
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    public function with(): array
    {
        return [
            'user' => $this->user(),
            'tokens' => $this->user()->personalTokens()->get(),
        ];
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

        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card)">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <x-ds::section-heading level="2">Tokens</x-ds::section-heading>
                    {{-- Says what it is for. A token nobody can explain is a
                         token nobody dares revoke. --}}
                    <flux:text class="mt-1">What your terminal uses to pull a design. CI needs none of these: it proves which repository it is to GitHub every run.</flux:text>
                </div>
                <flux:modal.trigger name="new-token">
                    <flux:button icon="plus">New token</flux:button>
                </flux:modal.trigger>
            </div>

            @if ($issued)
                {{-- The one moment it exists anywhere but a hash. --}}
                <div class="mt-6 rounded-(--radius-md) border border-(--ds-border) bg-(--ds-accent-soft) p-(--ds-card)">
                    <p class="text-sm font-medium text-(--ds-text-strong)">Shown once</p>
                    <p class="mt-1 text-sm text-(--ds-text-muted)">Put it in the app's <code class="font-mono text-xs">.env</code> as <code class="font-mono text-xs">DESIGN_TOKEN</code>. Nothing here can show it to you again.</p>
                    <div class="mt-3">
                        <x-copy-block label="DESIGN_TOKEN" :text="$issued" />
                    </div>
                </div>
            @endif

            <div class="mt-6 space-y-4">
                @forelse ($tokens as $token)
                    <div class="flex items-center justify-between gap-3 border-b border-(--ds-border) pb-3 last:border-0 last:pb-0" wire:key="token-{{ $token->id }}">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-(--ds-text-strong)">{{ $token->name }}</p>
                            <p class="text-sm text-(--ds-text-muted)">
                                Made {{ $token->created_at?->diffForHumans() }}@if ($token->last_used_at), last used {{ $token->last_used_at->diffForHumans() }}@else, never used @endif
                            </p>
                        </div>
                        <flux:button wire:click="revokeToken({{ $token->id }})" wire:confirm="Revoke {{ $token->name }}? Anything using it stops being able to pull a design." variant="ghost" icon="trash" aria-label="Revoke {{ $token->name }}" />
                    </div>
                @empty
                    <flux:text>None yet.</flux:text>
                @endforelse
            </div>
        </section>
    </div>

    <flux:modal name="new-token" class="md:w-96">
        <form wire:submit="issueToken" class="space-y-(--ds-section)">
            <div>
                <x-ds::section-heading>New token</x-ds::section-heading>
                <p class="mt-1 text-sm text-(--ds-text-muted)">One per machine, so a laptop can be cut off without taking the rest with it.</p>
            </div>

            <flux:input wire:model="tokenName" label="Where it will live" placeholder="Work laptop" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Issue</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
