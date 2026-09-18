<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Who can get in, and how somebody new does.
 *
 * Adding a person used to mean opening a terminal on the production host and
 * running `fleet:invite`, which is not something to ask of whoever happens to
 * be onboarding. It also printed a password, so the first password every
 * account had was one somebody else had read.
 *
 * This hands back a link instead. The person sets their own password and
 * nobody ever sees it. A link rather than an email because mail is not wired
 * up here, and because an invite gets pasted into a chat anyway.
 */
new #[Layout('layouts::tool')] #[Title('People')] class extends Component
{
    public string $name = '';

    public string $email = '';

    /** Shown once, straight after inviting. Never read back from anywhere. */
    public ?string $link = null;

    public ?string $linkFor = null;

    public function invite(): void
    {
        abort_unless(Auth::check(), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
        ]);

        // A password nobody will ever use or be told. The link is what gets
        // them in, and it makes them choose one.
        $user = User::create($data + [
            'password' => Str::password(40),
            'email_verified_at' => now(),
        ]);

        $this->show($user);
        $this->reset('name', 'email');
        Flux::modal('invite-someone')->close();
    }

    /** For somebody whose link went stale before they used it. */
    public function newLink(int $userId): void
    {
        abort_unless(Auth::check(), 403);

        $this->show(User::findOrFail($userId));
    }

    public function remove(int $userId): void
    {
        abort_unless(Auth::check(), 403);

        // Nobody locks the last person out, and nobody locks themselves out
        // by clicking the wrong row.
        if ($userId === Auth::id() || User::query()->count() <= 1) {
            return;
        }

        $user = User::findOrFail($userId);
        $name = $user->name;
        $user->delete();

        $this->reset('link', 'linkFor');
        Flux::toast("{$name} can no longer sign in.", variant: 'success');
    }

    /** The one place a link is made, so it is made the same way twice. */
    private function show(User $user): void
    {
        $this->link = route('password.reset', [
            'token' => Password::broker()->createToken($user),
            'email' => $user->email,
        ]);

        $this->linkFor = $user->name;
    }

    public function with(): array
    {
        return [
            'people' => User::query()->orderBy('name')->get(),
            'me' => Auth::id(),
            // Straight from the broker's own config, so the page cannot start
            // claiming a window somebody changed underneath it.
            'expires' => (int) config('auth.passwords.users.expire', 60),
        ];
    }
}; ?>

<div class="px-(--ds-gutter) py-(--ds-section)">
    <x-ds::page-header title="People" class="mb-(--ds-section)">
        <x-slot:description>Everyone who can sign in. There is no sign-up form, so this is the only way in.</x-slot:description>

        <flux:modal.trigger name="invite-someone">
            <flux:button variant="primary" icon="plus">Invite someone</flux:button>
        </flux:modal.trigger>
    </x-ds::page-header>

    {{-- The one moment the link exists. It is not stored, and nothing here can
         show it again, only make another. --}}
    @if ($link)
        <div class="mb-(--ds-section) rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-accent-soft) p-(--ds-card)">
            <x-ds::section-heading level="2">Send this to {{ $linkFor }}</x-ds::section-heading>
            <flux:text class="mt-1">They set their own password with it. Nobody else sees what they choose.</flux:text>

            <div class="mt-4">
                <x-copy-block label="Invite link" :text="$link" />
            </div>

            <p class="mt-2 text-sm text-(--ds-text-muted)">
                Good for {{ $expires }} minutes. If it goes stale, make another from their row.
            </p>
        </div>
    @endif

    <div class="overflow-x-auto rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface)">
        <table class="w-full min-w-[36rem] text-sm">
            <thead>
                <tr class="border-b border-(--ds-border)">
                    <th class="ds-label px-(--ds-cell-x) py-2.5 text-start font-normal">Name</th>
                    <th class="ds-label px-(--ds-cell-x) py-2.5 text-start font-normal">Email</th>
                    <th class="ds-label px-(--ds-cell-x) py-2.5 text-start font-normal">Added</th>
                    <th class="ds-label px-(--ds-cell-x) py-2.5 text-end font-normal"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($people as $person)
                    <tr class="border-b border-(--ds-border) last:border-0" wire:key="person-{{ $person->id }}">
                        <td class="px-(--ds-cell-x) py-3">
                            <span class="font-medium text-(--ds-text-strong)">{{ $person->name }}</span>
                            @if ($person->id === $me)
                                <span class="ms-2 text-xs text-(--ds-text-muted)">you</span>
                            @endif
                        </td>
                        <td class="px-(--ds-cell-x) py-3 text-(--ds-text-muted)">{{ $person->email }}</td>
                        <td class="px-(--ds-cell-x) py-3 text-(--ds-text-muted)">{{ $person->created_at?->diffForHumans() }}</td>
                        <td class="px-(--ds-cell-x) py-3 text-end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" icon="ellipsis-horizontal" aria-label="Actions for {{ $person->name }}" />
                                <flux:menu>
                                    <flux:menu.item wire:click="newLink({{ $person->id }})" icon="link">New invite link</flux:menu.item>
                                    @if ($person->id !== $me && $people->count() > 1)
                                        <flux:menu.separator />
                                        <flux:menu.item wire:click="remove({{ $person->id }})" wire:confirm="Remove {{ $person->name }}? They lose access and their tokens stop working." icon="trash" variant="danger">Remove</flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <flux:modal name="invite-someone" class="md:w-96">
        <form wire:submit="invite" class="space-y-(--ds-section)">
            <div>
                <x-ds::section-heading>Invite someone</x-ds::section-heading>
                <p class="mt-1 text-sm text-(--ds-text-muted)">You get a link to send them. They pick their own password, so no password is ever passed around.</p>
            </div>

            <div class="space-y-(--ds-field-gap)">
                <flux:input wire:model="name" label="Name" placeholder="Travis" />
                <flux:input wire:model="email" label="Email" type="email" placeholder="travis@dashcore.com" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Invite</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
