<?php

use App\Design\Artboard;
use App\Design\Design;
use App\Design\Screens;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Two designs, the same screen, side by side.
 *
 * A design page shows one design and lets you change it. Deciding between two
 * directions is a different job: nothing is edited here, and the only thing
 * that moves is which pair you are looking at. Flipping between two tabs does
 * not do it, because the difference between two neutral ramps is not something
 * anybody holds in their head for the length of a tab switch.
 *
 * One width at a time rather than the design page's grid of four. Two frames
 * already fill the canvas, and a four-up of two designs is eight frames too
 * small to read.
 */
new #[Layout('layouts::tool')] #[Title('Compare')] class extends Component
{
    /**
     * The pair this opens on: the two directions on the table right now.
     *
     * An editorial choice rather than a rule, and meant to be edited when the
     * question changes. Northline is a client's design standing in as the
     * brown direction, which is why its frames carry a client's name; the
     * decisions are what is being looked at, not the wordmark.
     *
     * Falls back to the house design and its sibling when either is gone, so
     * archiving one does not leave the page opening on nothing.
     */
    private const OPENS_ON = ['northline', 'dashcore-marketing'];

    #[Url]
    public string $a = '';

    #[Url]
    public string $b = '';

    #[Url]
    public string $screen = Screens::DEFAULT;

    #[Url]
    public string $register = 'light';

    #[Url]
    public string $viewport = 'desktop';

    public function mount(): void
    {
        $slugs = $this->slugs();
        [$opensA, $opensB] = $this->opening($slugs);

        // Everything here arrives from the URL, and two of them reach a Blade
        // include or a database read, so each is checked against its list
        // rather than trusted.
        $this->a = in_array($this->a, $slugs, true) ? $this->a : $opensA;

        if (! in_array($this->b, $slugs, true) || $this->b === $this->a) {
            // Only re-derived when the URL did not give a usable second side.
            // A link naming one design should open it beside its sibling, not
            // beside whatever this page opens on cold.
            $this->b = $this->a === $opensA ? $opensB : ($this->secondChoice($slugs, $this->a) ?? '');
        }

        $this->screen = Screens::resolve($this->screen);
        $this->register = $this->register === 'dark' ? 'dark' : 'light';
        $this->viewport = array_key_exists($this->viewport, Artboard::VIEWPORTS) ? $this->viewport : 'desktop';
    }

    /**
     * Keep the two sides different. Choosing the design already on the other
     * side swaps them, which is what somebody meant by it; the alternative is
     * a page comparing something with itself.
     *
     * `updating` rather than `updated`, because this needs the value the side
     * is about to lose and Livewire does not keep it afterwards.
     */
    public function updatingA(string $value): void
    {
        if ($value === $this->b) {
            $this->b = $this->a;
        }
    }

    public function updatingB(string $value): void
    {
        if ($value === $this->a) {
            $this->a = $this->b;
        }
    }

    public function swap(): void
    {
        [$this->a, $this->b] = [$this->b, $this->a];
    }

    /** @return list<string> */
    private function slugs(): array
    {
        return Design::all()->pluck('slug')->all();
    }

    /**
     * The pair to open on, with the fallback when the named two are not both
     * on the board.
     *
     * @param  list<string>  $slugs
     * @return array{0:string,1:string}
     */
    private function opening(array $slugs): array
    {
        $named = array_values(array_filter(
            self::OPENS_ON,
            fn (string $slug) => in_array($slug, $slugs, true),
        ));

        if (count($named) === 2) {
            return [$named[0], $named[1]];
        }

        $first = $this->firstChoice($slugs) ?? '';

        return [$first, $this->secondChoice($slugs, $first) ?? ''];
    }

    /** @param  list<string>  $slugs */
    private function firstChoice(array $slugs): ?string
    {
        return in_array(Design::OWN, $slugs, true) ? Design::OWN : ($slugs[0] ?? null);
    }

    /**
     * What to hold against the first one.
     *
     * A second direction for the same client, when there is one. Two designs
     * sharing a client are two answers to one question, which is the pair
     * somebody opening this page came to look at; the next slug in the list is
     * only the next slug in the list.
     *
     * @param  list<string>  $slugs
     */
    private function secondChoice(array $slugs, string $taken): ?string
    {
        $others = array_values(array_filter($slugs, fn (string $slug) => $slug !== $taken));

        if ($others === []) {
            return null;
        }

        $client = Design::exists($taken) ? Design::load($taken)->name() : null;

        foreach ($others as $slug) {
            if ($client !== null && Design::load($slug)->name() === $client) {
                return $slug;
            }
        }

        return $others[0];
    }

    public function with(): array
    {
        return [
            'designs' => Design::all(),
            'frame' => Artboard::VIEWPORTS[$this->viewport],
            'sides' => collect([$this->a, $this->b])
                ->filter(fn (string $slug) => $slug !== '' && Design::exists($slug))
                ->map(fn (string $slug) => Design::load($slug))
                ->values(),
        ];
    }
}; ?>

<div class="flex min-h-screen min-w-0 flex-col bg-(--ds-sunken)">
    {{-- Both designs' fonts, because both are drawn at once. Each frame loads
         its own as well; these are for the labels above them. --}}
    @foreach ($sides as $side)
        @push('fonts')
            <link rel="stylesheet" href="{{ $side->tokens()['fonts']['stylesheet'] }}">
        @endpush
    @endforeach

    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-(--ds-border) bg-(--ds-surface) px-(--ds-gutter) py-3">
        <div class="flex min-w-0 items-center gap-2">
            {{-- The two pickers say which designs, so a visible title would be
                 saying it twice on a bar that is already full. --}}
            <h1 class="sr-only">Compare designs</h1> {{-- @ds-audit-ignore not a rendered page title: a screen-reader label on a canvas screen with no page header region, the same shape as the design page beside it --}}
            <flux:select wire:model.live="a" size="sm" class="max-w-52">
                @foreach ($designs as $design)
                    <flux:select.option :value="$design->slug">{{ $design->slug }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:button wire:click="swap" size="sm" variant="ghost" icon="arrows-right-left" aria-label="Swap sides" /> {{-- @ds-audit-ignore a control in this tool's own chrome, on a two-frame desktop screen nobody reaches on a phone; 05's floor is about touch --}}
            <flux:select wire:model.live="b" size="sm" class="max-w-52">
                @foreach ($designs as $design)
                    <flux:select.option :value="$design->slug">{{ $design->slug }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:select wire:model.live="screen" size="sm" class="max-w-40">
                @foreach (Screens::ALL as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="viewport" size="sm" class="max-w-44">
                @foreach (Artboard::VIEWPORTS as $name => $size)
                    <flux:select.option :value="$name">{{ $size['label'] }} · {{ $size['width'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:radio.group wire:model.live="register" variant="segmented" size="sm">
                <flux:radio value="light" icon="sun" />
                <flux:radio value="dark" icon="moon" />
            </flux:radio.group>
        </div>
    </div>

    @if ($sides->count() < 2)
        <div class="flex flex-1 items-center justify-center p-(--ds-gutter)">
            <div class="max-w-md text-center">
                <x-ds::section-heading level="2">Nothing to compare yet</x-ds::section-heading>
                <flux:text class="mt-1">This needs two designs on the board, and there is one.</flux:text>
                <flux:button :href="route('designs.create')" wire:navigate variant="primary" icon="plus" class="mt-4">New design</flux:button>
            </div>
        </div>
    @else
        <div class="flex-1 p-(--ds-gutter)">
            {{--
                Both frames at one width, fitted together rather than each on its
                own. Two designs at two scales are not comparable: the smaller
                one reads as tighter for no reason but its scale.
            --}}
            @php
                $gap = 32;
                $total = $frame['width'] * 2 + $gap;
            @endphp

            <div wire:key="compare-{{ $a }}-{{ $b }}-{{ $viewport }}"
                 x-data="{
                    scale: 1,
                    total: {{ $total }},
                    fit() {
                        const available = $el.clientWidth || this.total;
                        this.scale = Math.min(1, available / this.total);
                    },
                 }"
                 x-init="fit(); new ResizeObserver(() => fit()).observe($el)">
                {{-- Said out loud, because a gap measured off a reduced frame is
                     a gap measured wrong. --}}
                <p class="mb-3 text-xs text-(--ds-text-muted)"
                   x-show="scale < 0.999"
                   x-text="`Both fitted to ${Math.round(scale * 100)}%`"></p>

                <div class="flex origin-top-left items-start gap-8" x-bind:style="`transform: scale(${scale})`">
                    @foreach ($sides as $side)
                        @php
                            $tokens = $side->tokens();
                            $markup = view('mockups.'.$screen, [
                                't' => $tokens,
                                'design' => $side,
                                'motif' => $side->decision('motif'),
                                'shell' => $side->brief['shell'] ?? 'sidebar',
                                'register' => $register,
                            ])->render();
                        @endphp
                        <div class="shrink-0" style="width: {{ $frame['width'] }}px">
                            <div class="mb-2 flex items-center justify-between gap-4">
                                <span class="flex min-w-0 items-center gap-2">
                                    <span class="size-3 shrink-0 rounded-(--radius-sm) border border-(--ds-border)" style="background: {{ $tokens[$register]['accent'] }}"></span>
                                    <a href="{{ route('designs.show', $side->slug) }}" wire:navigate class="truncate text-sm font-medium text-(--ds-text-strong) hover:underline">{{ $side->slug }}</a>
                                </span>
                                {{-- The decisions that are not the colour, because
                                     the colour is the one difference anybody sees
                                     without being told. --}}
                                <span class="truncate text-xs text-(--ds-text-muted)">{{ $tokens['fonts']['sans']['family'] }} · {{ $side->decision('temperature') }} · {{ $side->decision('radius') }} · {{ $side->decision('density') }} · {{ $side->decision('motif') }} · {{ $side->brief['shell'] ?? 'sidebar' }}</span>
                            </div>
                            <iframe wire:key="frame-{{ $side->slug }}-{{ $screen }}-{{ $register }}-{{ $viewport }}"
                                    title="{{ $side->slug }}, {{ $frame['label'] }}"
                                    srcdoc="{{ Artboard::document($markup, $tokens, $register) }}"
                                    class="block w-full rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-page) shadow-lg"
                                    style="height: {{ $frame['height'] }}px"></iframe>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
