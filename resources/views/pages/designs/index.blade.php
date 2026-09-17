<?php

use App\Design\Design;
use App\Models\Site;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::tool')] #[Title('Designs')] class extends Component
{
    /** The design the delete modal is asking about. Null when it is closed. */
    public ?string $pending = null;

    #[Url]
    public bool $showArchived = false;

    /**
     * Off the board, pack still serving. No modal: it is reversible, and a
     * confirmation on something undone in one click is only friction.
     */
    public function archive(string $slug): void
    {
        abort_unless(Auth::check(), 403);

        $design = Design::load($slug);

        if (! $design->archivable()) {
            return;
        }

        $design->archive();

        $this->pending = null;
        Flux::modal('forget-design')->close();
        Flux::toast("{$design->name()} is archived. Its pack still serves.", variant: 'success');
    }

    public function restore(string $slug): void
    {
        abort_unless(Auth::check(), 403);

        $design = Design::load($slug);
        $design->restore();

        Flux::toast("{$design->name()} is back on the board.", variant: 'success');
    }

    /**
     * One modal for the whole page rather than one per card. The card says
     * which design, the modal repeats it back with what uses it.
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

        // The same answer the modal gives, held here as well, because a public
        // Livewire method can be called past the page. Archiving is the way to
        // retire something an app is still pulling.
        if (Site::usage()->has($design->slug)) {
            return;
        }

        $name = $design->name();
        $design->delete();

        $this->pending = null;
        Flux::modal('forget-design')->close();
        Flux::toast("{$name} is gone.", variant: 'success');
    }

    public function with(): array
    {
        $usage = Site::usage();

        return [
            'designs' => Design::all(),
            'archived' => Design::archived(),
            'usage' => $usage,
            // Read for the modal rather than held in a property, so what is on
            // screen is what is in the database right now.
            'going' => $this->pending === null ? null : Design::load($this->pending),
            'goingUsedBy' => $this->pending === null ? collect() : $usage->get($this->pending, collect()),
        ];
    }
}; ?>

<div class="px-(--ds-gutter) py-(--ds-section)">
    <x-ds::page-header title="Designs" class="mb-(--ds-section)">
        <x-slot:description>One per project. Open one to change its decisions and export the pack.</x-slot:description>
        @if ($archived->isNotEmpty())
            <flux:button wire:click="$toggle('showArchived')" variant="{{ $showArchived ? 'filled' : 'ghost' }}" icon="archive-box">
                {{ $showArchived ? 'Hide' : 'Show' }} archived ({{ $archived->count() }})
            </flux:button>
        @endif
        <flux:button :href="route('designs.create')" wire:navigate variant="primary" icon="plus">New design</flux:button>
    </x-ds::page-header>

    <div class="grid gap-(--ds-section) md:grid-cols-2 xl:grid-cols-3">
        @foreach ($designs as $design)
            <x-design-card :design="$design" :usedBy="$usage->get($design->slug, collect())" />
        @endforeach
    </div>

    @if ($showArchived && $archived->isNotEmpty())
            <div class="mt-(--ds-section)">
                <x-ds::section-heading level="2">Archived</x-ds::section-heading>
                {{-- Said once here rather than on every card. The whole reason to
                     archive rather than delete is that nothing downstream
                     notices. --}}
                <flux:text class="mt-1 mb-(--ds-section)">Off the board, still serving. Any app that pulls one of these keeps getting it.</flux:text>

                <div class="grid gap-(--ds-section) md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($archived as $design)
                        <x-design-card :design="$design" :usedBy="$usage->get($design->slug, collect())" />
                    @endforeach
                </div>
            </div>
    @endif

    {{-- What it costs, taken from the fleet rather than guessed at. Every repo
         reports the design it uses, so the modal can name them. --}}
    <flux:modal name="forget-design" class="md:w-[30rem]" wire:close="$set('pending', null)">
        <div class="space-y-6">
            <div>
                <x-ds::section-heading level="2">Delete {{ $going?->name() }}?</x-ds::section-heading>
                <flux:text class="mt-1">The brief, the seven decisions and the reasons behind them all go, and there is no undo.</flux:text>
            </div>

            @if ($goingUsedBy->isNotEmpty())
                <flux:callout icon="exclamation-triangle" color="amber">
                    <flux:callout.heading>Used by {{ $goingUsedBy->count() }} {{ \Illuminate\Support\Str::plural('app', $goingUsedBy->count()) }}</flux:callout.heading>
                    <flux:callout.text>
                        <span class="block space-y-0.5">
                            @foreach ($goingUsedBy as $repository)
                                <span class="block font-mono text-xs">{{ $repository }}</span>
                            @endforeach
                        </span>
                        <span class="mt-2 block">Each one pulls this slug on every update. Archive it instead and the pack keeps serving.</span>
                    </flux:callout.text>
                </flux:callout>
            @elseif ($going)
                <flux:callout icon="information-circle">
                    <flux:callout.heading>Nothing reports using this</flux:callout.heading>
                    <flux:callout.text>
                        {{-- The board only knows what has been reported to it,
                             which is why the heading says "nothing reports
                             using this" rather than "nothing uses this". --}}
                        No repository on the fleet board named <code class="font-mono text-xs">{{ $going->slug }}</code> in its last report. An app whose CI never reports would not show here.
                    </flux:callout.text>
                </flux:callout>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Keep it</flux:button>
                </flux:modal.close>
                @if ($goingUsedBy->isNotEmpty())
                    <flux:button wire:click="archive('{{ $going?->slug }}')" variant="primary" icon="archive-box">Archive instead</flux:button>
                @else
                    <flux:button wire:click="forget" variant="danger">Delete</flux:button>
                @endif
            </div>
        </div>
    </flux:modal>
</div>
