<?php

use App\Design\Artboard;
use App\Design\Blocks;
use App\Design\Design;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The block library, one card per category.
 *
 * A design decides how an app feels. These are the screens it does not decide:
 * the shell, the error pages, settings, the landing page, email. Every app
 * built its own from the standard's prose, which is how the account menu ended
 * up in three different corners of the fleet.
 *
 * Categories rather than one long list, and the reason is arithmetic. A block
 * draws as a whole document in a frame of its own, about 31KB of it, so a flat
 * page of every block costs more the more there are. This page costs the same
 * whether a category holds one block or forty, because it draws one face each
 * and the rest only load when a category is opened.
 */
new #[Layout('layouts::tool')] #[Title('Blocks')] class extends Component
{
    #[Url]
    public string $register = 'light';

    public function mount(): void
    {
        $this->register = $this->register === 'dark' ? 'dark' : 'light';
    }

    public function with(): array
    {
        // The tool's own design. Every reader here is already looking at it,
        // so it is the one picture that needs no explaining.
        return [
            'design' => Design::load(Design::OWN),
            'categories' => Blocks::categories(),
        ];
    }
}; ?>

<div class="px-(--ds-gutter) py-(--ds-section)">
    <x-ds::page-header title="Blocks" class="mb-(--ds-section)">
        <x-slot:description>The screens a design does not decide. Open one, then paste its prompt into Claude Code in the app you are building.</x-slot:description>

        <flux:radio.group wire:model.live="register" variant="segmented" size="sm">
            <flux:radio value="light" icon="sun" />
            <flux:radio value="dark" icon="moon" />
        </flux:radio.group>
    </x-ds::page-header>

    <div class="mb-(--ds-section) max-w-prose space-y-3">
        <flux:text>A design sets the colour, type and spacing. It does not set that every app puts the account menu in the same corner, or that a 404 reads the same in fifteen products. Those are screens, and each app has been building its own.</flux:text>
        <flux:text>Everything here is drawn in this tool's own design, which is the one you are looking at. Open a design and the same blocks are drawn in that one.</flux:text>
    </div>

    <div class="grid gap-(--ds-section) sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($categories as $key => $category)
            @php
                $frame = Artboard::VIEWPORTS['laptop'];
                $count = count($category['blocks']);
            @endphp

            <a href="{{ route('blocks.show', ['category' => $key, 'register' => $register]) }}" wire:navigate
               class="group flex flex-col overflow-hidden rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) transition-colors duration-150 hover:border-(--ds-border-strong)"
               wire:key="category-{{ $key }}">
                {{--
                    One face per category, drawn rather than photographed, so
                    the card cannot go stale against the block behind it.

                    A frame rather than an image, for the reason the design page
                    uses one: only an iframe has a viewport of its own, and a
                    transform-scaled div keeps measuring the browser window, so
                    the frame would render the desktop layout squeezed and look
                    checked when it is not.
                --}}
                <div class="relative overflow-hidden border-b border-(--ds-border) bg-(--ds-sunken)"
                     x-data="{
                        scale: 0.25,
                        fit() { this.scale = ($el.clientWidth || {{ $frame['width'] }}) / {{ $frame['width'] }} },
                     }"
                     x-init="fit(); new ResizeObserver(() => fit()).observe($el)"
                     x-bind:style="{ height: `${Math.round({{ $frame['height'] }} * scale)}px` }"
                     style="height: {{ (int) round($frame['height'] * 0.25) }}px">
                    <iframe wire:key="face-{{ $key }}-{{ $register }}"
                            title="{{ $category['label'] }}, drawn in this tool's design"
                            srcdoc="{{ Artboard::document(Blocks::draw($category['face'], $design, $register), $design->tokens(), $register) }}"
                            tabindex="-1"
                            aria-hidden="true"
                            loading="lazy"
                            class="pointer-events-none absolute start-0 top-0 origin-top-left border-0"
                            {{-- An object rather than a string, which is the
                                 difference between merging and replacing.
                                 x-bind:style given a string overwrites the
                                 whole attribute, so the width and height below
                                 vanished the moment Alpine booted and the
                                 frame fell back to the browser's 300 by 150
                                 default: a thumbnail in the corner of an empty
                                 card. An object sets one property at a time
                                 and leaves the rest alone. --}}
                            style="width: {{ $frame['width'] }}px; height: {{ $frame['height'] }}px"
                            x-bind:style="{ transform: `scale(${scale})` }"></iframe>
                </div>

                <div class="flex flex-1 items-start justify-between gap-3 p-(--ds-card)">
                    <div class="min-w-0">
                        <x-ds::section-heading level="2">{{ $category['label'] }}</x-ds::section-heading>
                        <flux:text class="mt-1">{{ $category['what'] }}</flux:text>
                    </div>
                    {{-- Not a status, so no status colour. It is how many
                         things are behind the card. --}}
                    <span class="shrink-0 rounded-(--radius-sm) border border-(--ds-border) px-1.5 py-0.5 text-xs tabular-nums text-(--ds-text-muted)">{{ $count }}</span>
                </div>
            </a>
        @endforeach
    </div>
</div>
