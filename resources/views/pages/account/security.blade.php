<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The second factor and the passkeys on this account.
 *
 * Split off /account because the route carries `password.confirm`. Everything
 * here either weakens or replaces the way somebody proves who they are, so a
 * session left open on a shared machine should not be enough to reach it.
 *
 * Fortify's HTTP endpoints for all of this already existed and nothing linked
 * to them. This page calls the same actions those endpoints call, so there is
 * one implementation rather than two.
 */
new #[Layout('layouts::tool')] #[Title('Security')] class extends Component
{
    public string $code = '';

    /** Shown once, straight after confirming or regenerating. */
    public bool $showingRecoveryCodes = false;

    public function enableTwoFactor(EnableTwoFactorAuthentication $enable): void
    {
        $enable($this->user());

        $this->reset('code', 'showingRecoveryCodes');
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirm): void
    {
        // A wrong code leaves its message in the bag, and Livewire only clears
        // the bag when validation runs. Without this, a second attempt that
        // works still shows the first one failing.
        $this->resetValidation();

        $confirm($this->user(), $this->code);

        $this->reset('code');
        $this->showingRecoveryCodes = true;

        Flux::toast('Two-factor is on. Save the recovery codes.', variant: 'success');
    }

    /**
     * Also the cancel button during setup. An unconfirmed secret is a secret
     * nobody has scanned, so throwing it away and starting over are the same.
     */
    public function disableTwoFactor(DisableTwoFactorAuthentication $disable): void
    {
        $disable($this->user());

        $this->reset('code', 'showingRecoveryCodes');
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generate): void
    {
        $generate($this->user());

        $this->showingRecoveryCodes = true;

        Flux::toast('New codes issued. The old ones no longer work.', variant: 'success');
    }

    public function forgetPasskey(int $passkeyId): void
    {
        $this->user()->passkeys()->whereKey($passkeyId)->delete();
    }

    /** Called by x-passkey-registration once the browser has stored the key. */
    public function loadPasskeys(): void
    {
        // Nothing to do. with() re-reads the list on the render this triggers.
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    public function with(): array
    {
        $user = $this->user();

        return [
            'user' => $user,
            'pending' => filled($user->two_factor_secret) && ! $user->hasEnabledTwoFactorAuthentication(),
            'enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'passkeys' => $user->passkeys()->latest()->get(),
        ];
    }
}; ?>

<div class="px-(--ds-gutter) py-(--ds-section)">
    <x-ds::page-header title="Security" class="mb-(--ds-section)">
        <x-slot:description>A second factor and the passkeys that can sign in as you.</x-slot:description>

        <flux:button :href="route('account')" wire:navigate icon="arrow-left" variant="ghost">Account</flux:button>
    </x-ds::page-header>

    <div class="max-w-2xl space-y-(--ds-section)">
        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card)">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <x-ds::section-heading level="2">Two-factor</x-ds::section-heading>
                    <flux:text class="mt-1">A six-digit code from an authenticator app, on top of the password.</flux:text>
                </div>
                <x-ds::badge :status="$enabled ? 'success' : null">{{ $enabled ? 'On' : 'Off' }}</x-ds::badge>
            </div>

            @if (! $enabled && ! $pending)
                <div class="mt-6">
                    <flux:button wire:click="enableTwoFactor" variant="primary">Turn on</flux:button>
                </div>
            @endif

            @if ($pending)
                <div class="mt-6 space-y-4">
                    <flux:text>Scan this with your authenticator app, then type the code it shows.</flux:text>

                    {{-- On white whatever the register is. Fortify draws the code
                         in a fixed dark colour, and a scanner needs the contrast
                         far more than the panel needs to match the surface. --}}
                    <div class="inline-block rounded-(--radius-md) bg-white p-4">
                        {!! $user->twoFactorQrCodeSvg() !!}
                    </div>

                    <form wire:submit="confirmTwoFactor" class="flex flex-wrap items-end gap-3">
                        <flux:input wire:model="code" label="Code" type="text" inputmode="numeric" autocomplete="one-time-code" class="max-w-40" required />
                        <flux:button type="submit" variant="primary">Confirm</flux:button>
                        <flux:button wire:click="disableTwoFactor" variant="ghost" type="button">Cancel</flux:button>
                    </form>
                </div>
            @endif

            @if ($enabled)
                <div class="mt-6 space-y-4">
                    @if ($showingRecoveryCodes)
                        {{-- The one moment somebody can copy these. They are stored
                             encrypted and never shown again unless they are
                             regenerated, which invalidates the old set. --}}
                        <div>
                            <flux:text class="mb-2">Store these somewhere safe. Each one signs you in once if you lose the app.</flux:text>
                            <x-copy-block label="Recovery codes" :text="implode(PHP_EOL, $user->recoveryCodes())" />
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-2">
                        <flux:button wire:click="regenerateRecoveryCodes" variant="filled">{{ $showingRecoveryCodes ? 'Issue new codes' : 'Show recovery codes' }}</flux:button>
                        <flux:button wire:click="disableTwoFactor" wire:confirm="Turn off two-factor for this account?" variant="danger">Turn off</flux:button>
                    </div>
                </div>
            @endif
        </section>

        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card)">
            <x-ds::section-heading level="2">Passkeys</x-ds::section-heading>
            <flux:text class="mt-1">Sign in with the fingerprint, face or PIN the device already uses. No password, no code.</flux:text>

            <div class="mt-6 space-y-4">
                @forelse ($passkeys as $passkey)
                    <div class="flex items-center justify-between gap-3 border-b border-(--ds-border) pb-3 last:border-0 last:pb-0" wire:key="passkey-{{ $passkey->id }}">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-(--ds-text-strong)">{{ $passkey->name }}</p>
                            <p class="text-sm text-(--ds-text-muted)">
                                Added {{ $passkey->created_at?->diffForHumans() }}@if ($passkey->last_used_at), last used {{ $passkey->last_used_at->diffForHumans() }}@endif
                            </p>
                        </div>
                        <flux:button wire:click="forgetPasskey({{ $passkey->id }})" wire:confirm="Remove {{ $passkey->name }}? It will no longer sign you in." variant="ghost" icon="trash" aria-label="Remove {{ $passkey->name }}" />
                    </div>
                @empty
                    <flux:text>None yet.</flux:text>
                @endforelse

                <x-passkey-registration />
            </div>
        </section>
    </div>
</div>
