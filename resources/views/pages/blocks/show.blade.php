<?php

use App\Design\Artboard;
use App\Design\Blocks;
use App\Design\Design;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * One category, and every block in it at full size.
 *
 * The index draws one face per category and costs the same however many blocks
 * there are. This is the page that pays: it draws every block in the category,
 * which is affordable because you asked for this category and not the other
 * seven.
 *
 * Each block carries its own width and its own prompt. The width is per block
 * rather than per page, because a landing page and an email are judged at
 * different sizes and a page-wide switch would make one of them wrong.
 */
new #[Layout('layouts::tool')] class extends Component
{
    public string $category;

    #[Url]
    public string $register = 'light';

    /** @var array<string,string> block key => viewport name */
    public array $widths = [];

    public function mount(string $category): void
    {
        abort_unless(Blocks::hasCategory($category) && Blocks::inCategory($category) !== [], 404);

        $this->category = $category;
        $this->register = $this->register === 'dark' ? 'dark' : 'light';
    }

    /** The width one block is drawn at, checked against the list it came from. */
    public function widthFor(string $block): string
    {
        $asked = $this->widths[$block] ?? null;

        return is_string($asked) && array_key_exists($asked, Artboard::VIEWPORTS) ? $asked : 'laptop';
    }

    public function setWidth(string $block, string $viewport): void
    {
        if (Blocks::has($block) && array_key_exists($viewport, Artboard::VIEWPORTS)) {
            $this->widths[$block] = $viewport;
        }
    }

    public function with(): array
    {
        $blocks = Blocks::inCategory($this->category);

        return [
            'design' => Design::load(Design::OWN),
            'meta' => Blocks::CATEGORIES[$this->category],
            'blocks' => $blocks,
            'prompts' => collect($blocks)
                ->filter(fn (array $b) => $b['prompt'] === true)
                ->map(fn (array $b, string $key) => Blocks::prompt($key))
                ->all(),
        ];
    }
}; ?>

<div class="px-(--ds-gutter) py-(--ds-section)">
    @section('title', $meta['label'])

    <div class="mb-(--ds-section)">
        <flux:button :href="route('blocks.index', ['register' => $register])" wire:navigate size="sm" variant="ghost" icon="arrow-left" class="mb-3">All blocks</flux:button>

        <x-ds::page-header :title="$meta['label']">
            <x-slot:description>{{ $meta['what'] }}</x-slot:description>

            <flux:radio.group wire:model.live="register" variant="segmented" size="sm">
                <flux:radio value="light" icon="sun" />
                <flux:radio value="dark" icon="moon" />
            </flux:radio.group>
        </x-ds::page-header>
    </div>

    <div class="space-y-(--ds-section)">
        @foreach ($blocks as $key => $block)
            @php
                $width = $this->widthFor($key);
                $frame = Artboard::VIEWPORTS[$width];
            @endphp

            <div class="overflow-hidden rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface)" wire:key="block-{{ $key }}">
                <div class="border-b border-(--ds-border) p-(--ds-card)">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <x-ds::section-heading level="2">{{ $block['label'] }}</x-ds::section-heading>
                            <flux:text class="mt-1 max-w-prose">{{ $block['what'] }}</flux:text>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            {{-- Per block, not per page. A landing page and an
                                 email are judged at different widths, and one
                                 switch for the page would make one of them
                                 wrong. --}}
                            <flux:dropdown position="bottom" align="end">
                                <flux:button size="sm" variant="ghost" icon="computer-desktop" icon:trailing="chevron-down">{{ $frame['label'] }}</flux:button> {{-- @ds-audit-ignore a control in this tool's own chrome, beside a frame nobody reaches on a phone; 05's floor is about touch --}}
                                <flux:menu>
                                    @foreach (Artboard::VIEWPORTS as $name => $size)
                                        <flux:menu.item wire:click="setWidth('{{ $key }}', '{{ $name }}')">
                                            <span class="flex w-full items-center justify-between gap-6">
                                                <span>{{ $size['label'] }}</span>
                                                <span class="text-xs tabular-nums text-(--ds-text-muted)">{{ $size['width'] }}</span>
                                            </span>
                                        </flux:menu.item>
                                    @endforeach
                                </flux:menu>
                            </flux:dropdown>

                            @if ($block['prompt'])
                                <flux:modal.trigger name="read-{{ $key }}">
                                    <flux:button size="sm" variant="ghost">Read it</flux:button> {{-- @ds-audit-ignore a control in this tool's own chrome, beside a frame nobody reaches on a phone; 05's floor is about touch --}}
                                </flux:modal.trigger>
                                <x-copy-button :text="$prompts[$key]" label="Copy prompt" done="Copied" variant="primary" size="sm" />
                            @endif

                            <flux:button :href="route('designs.show', ['slug' => $design->slug, 'screen' => $key, 'register' => $register])" wire:navigate size="sm" variant="ghost" icon="swatch">On a design</flux:button> {{-- @ds-audit-ignore a control in this tool's own chrome, beside a frame nobody reaches on a phone; 05's floor is about touch --}}
                        </div>
                    </div>

                    <p class="mt-3 text-sm text-(--ds-text-muted)">
                        <span class="ds-label">Decided by</span>
                        {{ $block['rules'] }}
                    </p>

                    {{-- Beside the block, not only inside the prompt, so
                         somebody deciding what to build next can see which one
                         is going to ask them questions the standard cannot
                         answer. --}}
                    @if ($block['gap'])
                        <p class="mt-3 rounded-(--radius-md) bg-(--ds-warning-soft) px-3 py-2 text-sm text-(--ds-text-muted-on-sunken)">
                            <span class="font-medium text-(--ds-warning)">Not settled yet.</span>
                            {{ $block['gap'] }}
                        </p>
                    @endif

                    {{-- A missing Copy prompt reads as something forgotten
                         unless the card says why it is missing. --}}
                    @unless ($block['prompt'])
                        <p class="mt-3 text-sm text-(--ds-text-muted)">{{ $block['note'] }}</p>
                    @endunless
                </div>

                {{--
                    The block at the chosen width, fitted to the card. A frame
                    rather than a scaled div: only an iframe has a viewport of
                    its own, and a scaled div keeps measuring the browser
                    window, so a 390px block would render the desktop layout
                    squeezed and look checked when it is not.
                --}}
                <div class="bg-(--ds-sunken) p-(--ds-card)">
                    <div class="relative overflow-hidden"
                         wire:key="frame-{{ $key }}-{{ $width }}"
                         x-data="{
                            scale: 1,
                            fit() { this.scale = Math.min(1, ($el.clientWidth || {{ $frame['width'] }}) / {{ $frame['width'] }}) },
                         }"
                         x-init="fit(); new ResizeObserver(() => fit()).observe($el)"
                         x-bind:style="{ height: `${Math.round({{ $frame['height'] }} * scale)}px` }"
                         style="height: {{ $frame['height'] }}px">
                        <iframe wire:key="draw-{{ $key }}-{{ $width }}-{{ $register }}"
                                title="{{ $block['label'] }} at {{ $frame['label'] }} width"
                                srcdoc="{{ Artboard::document(Blocks::draw($key, $design, $register), $design->tokens(), $register) }}"
                                loading="lazy"
                                class="absolute start-0 top-0 origin-top-left rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-page)"
                                {{-- An object rather than a string: a string
                                     overwrites the whole style attribute and
                                     would take the width and height below with
                                     it. An object sets one property and leaves
                                     the rest alone. --}}
                                style="width: {{ $frame['width'] }}px; height: {{ $frame['height'] }}px"
                                x-bind:style="{ transform: `scale(${scale})` }"></iframe>
                    </div>
                </div>
            </div>

            @if ($block['prompt'])
                <flux:modal name="read-{{ $key }}" class="md:w-[44rem]" wire:key="read-{{ $key }}">
                    <div class="space-y-4">
                        <div>
                            <x-ds::section-heading level="2">{{ $block['label'] }}</x-ds::section-heading>
                            {{-- Readable before it is pasted. Pasting a prompt
                                 into a repo unread is the thing that goes
                                 wrong. --}}
                            <flux:text class="mt-1">Paste this into Claude Code in the repo you are building.</flux:text>
                        </div>
                        <x-copy-block :text="$prompts[$key]" />
                    </div>
                </flux:modal>
            @endif
        @endforeach
    </div>
</div>
