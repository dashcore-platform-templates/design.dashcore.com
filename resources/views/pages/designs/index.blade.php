<?php

use App\Design\Design;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::tool')] #[Title('Designs')] class extends Component
{
    /** The design the modal is asking about. Null when it is closed. */
    public ?string $pending = null;

    /**
     * One modal for the whole page rather than one per card. The card says
     * which design, the modal repeats it back, and there is a single place
     * where the wording lives.
     */
    public function ask(string $slug): void
    {
        abort_unless(Auth::check(), 403);

        $this->pending = Design::load($slug)->deletable() ? $slug : null;

        if ($this->pending !== null) {
            Flux::modal('forget-design')->show();
        }
    }

    public function forget(): void
    {
        abort_unless(Auth::check(), 403);

        if ($this->pending === null) {
            return;
        }

        $design = Design::load($this->pending);
        $name = $design->name();
        $design->delete();

        $this->pending = null;
        Flux::modal('forget-design')->close();
        Flux::toast("{$name} is gone.", variant: 'success');
    }

    public function with(): array
    {
        return [
            'designs' => Design::all(),
            // Read for the modal rather than held in a property, so the name
            // and slug on screen are the ones in the database right now.
            'going' => $this->pending === null ? null : Design::load($this->pending),
        ];
    }
}; ?>

<div class="px-(--ds-gutter) py-(--ds-section)">
    <x-ds::page-header title="Designs" class="mb-(--ds-section)">
        <x-slot:description>One per project. Open one to change its decisions and export the pack.</x-slot:description>
        @auth
            <flux:button :href="route('designs.create')" wire:navigate variant="primary" icon="plus">New design</flux:button>
        @endauth
    </x-ds::page-header>

    <div class="grid gap-(--ds-section) md:grid-cols-2 xl:grid-cols-3">
        @foreach ($designs as $design)
            @php $t = $design->tokens(); $p = $design->pairing(); @endphp
            {{-- The delete button sits beside the link rather than inside it.
                 A button nested in an anchor is not valid markup, and the two
                 click targets would fight. --}}
            <div class="relative" wire:key="design-{{ $design->slug }}">
                <a href="{{ route('designs.show', $design->slug) }}" wire:navigate class="group block overflow-hidden rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) transition-colors duration-150 hover:border-(--ds-border-strong)">
                    <div class="flex h-2">
                        <span class="w-1/3" style="background: {{ $t['light']['accent'] }}"></span>
                        @foreach ([200, 400, 600, 800] as $step)
                            <span class="flex-1" style="background: {{ $t['neutral'][$step] }}"></span>
                        @endforeach
                    </div>
                    <div class="p-(--ds-card)">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="truncate text-lg font-semibold leading-tight text-(--ds-text-strong)">{{ $design->name() }}</h2>
                                <p class="mt-1 line-clamp-2 text-sm text-(--ds-text-muted)">{{ $design->brief['product'] ?? '' }}</p>
                            </div>
                            <span class="shrink-0 rounded-(--radius-sm) px-1.5 py-0.5 text-xs font-medium {{ $t['contrast']['passes'] ? 'bg-(--ds-success-soft) text-(--ds-success)' : 'bg-(--ds-danger-soft) text-(--ds-danger)' }}">{{ $t['contrast']['passes'] ? 'Contrast ok' : 'Contrast fails' }}</span>
                        </div>
                        {{-- Room at the end of the last row for the button below.
                             48px rather than the 40px the button measures: 40 is
                             not on 05 scale, and the next step up leaves a gap. --}}
                        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 pe-12 text-sm">
                            <div><dt class="ds-label">Shell</dt><dd class="text-(--ds-text-strong)">{{ $design->brief['shell'] ?? 'sidebar' }}</dd></div>
                            <div><dt class="ds-label">Motif</dt><dd class="text-(--ds-text-strong)">{{ $design->decision('motif') }}</dd></div>
                            <div><dt class="ds-label">Type</dt><dd class="truncate text-(--ds-text-strong)">{{ $p['sans']['family'] }} + {{ $p['second']['family'] }}</dd></div>
                            <div><dt class="ds-label">Neutral, radius, density</dt><dd class="text-(--ds-text-strong)">{{ $design->decision('temperature') }}, {{ $design->decision('radius') }}, {{ $design->decision('density') }}</dd></div>
                        </dl>
                    </div>
                </a>

                {{-- Not offered for the design the tool is wearing, because
                     deleting it takes every page down, sign-in included. --}}
                @auth
                    @if ($design->deletable())
                        <flux:button
                            wire:click="ask('{{ $design->slug }}')"
                            variant="subtle"
                            icon="trash"
                            class="absolute bottom-(--ds-card) end-(--ds-card)"
                            aria-label="Delete {{ $design->name() }}"
                        />
                    @endif
                @endauth
            </div>
        @endforeach
    </div>

    {{-- What it costs is said here rather than in a shrug of a confirm box. A
         design is not only a row: every applied repo pulls its pack by slug,
         and those pulls start failing the moment it goes. --}}
    <flux:modal name="forget-design" class="md:w-[28rem]" wire:close="$set('pending', null)">
        <div class="space-y-6">
            <div>
                <x-ds::section-heading level="2">Delete {{ $going?->name() }}?</x-ds::section-heading>
                <flux:text class="mt-1">
                    The brief, the seven decisions and the reasons behind them all go, and there is no undo.
                </flux:text>
            </div>

            @if ($going)
                <flux:callout icon="exclamation-triangle" color="amber">
                    <flux:callout.heading>Any app wearing it loses its pack</flux:callout.heading>
                    <flux:callout.text>
                        A repo that ran <code class="font-mono text-xs">design:apply {{ $going->slug }}</code> pulls from this slug on every update. Those pulls start returning 404. The design already in that repo keeps working; it just cannot be refreshed.
                    </flux:callout.text>
                </flux:callout>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Keep it</flux:button>
                </flux:modal.close>
                <flux:button wire:click="forget" variant="danger">Delete</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
