<?php

use App\Design\Artboard;
use App\Design\Blocks;
use App\Design\Design;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The blocks, each one drawn and most of them a pasted prompt.
 *
 * A design decides how an app feels. These are the screens it does not decide:
 * the shell, the error pages, settings, the landing page, email. Every app
 * built its own from the standard's prose, which is how the account menu ended
 * up in three different corners of the fleet.
 *
 * The page owns none of this. A block is a component of its own under
 * resources/views/components/block, and a design page draws the same one; this
 * is the page that hands it over.
 *
 * The prompts name no design, because the repo already holds one in design/ and
 * a prompt naming a slug would be wrong the moment that app's design changed.
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
        $design = Design::load(Design::OWN);

        return [
            'design' => $design,
            'blocks' => Blocks::ALL,
            // Only the ones somebody builds. Asking for a prompt for the other
            // three throws, which is the right answer and the wrong place for it.
            'prompts' => collect(Blocks::buildable())->map(fn (array $b, string $key) => Blocks::prompt($key))->all(),
        ];
    }
}; ?>

<div class="px-(--ds-gutter) py-(--ds-section)">
    <x-ds::page-header title="Blocks" class="mb-(--ds-section)">
        <x-slot:description>The screens a design does not decide. Paste one into Claude Code in the app you are building.</x-slot:description>

        <flux:radio.group wire:model.live="register" variant="segmented" size="sm">
            <flux:radio value="light" icon="sun" />
            <flux:radio value="dark" icon="moon" />
        </flux:radio.group>
    </x-ds::page-header>

    <div class="mb-(--ds-section) max-w-prose space-y-3">
        <flux:text>A design sets the colour, type and spacing. It does not set that every app puts the account menu in the same corner, or that a 404 reads the same in fifteen products. Those are screens, and each app has been building its own.</flux:text>
        <flux:text>Each prompt builds one screen in the repo you run it in, on that app's design. The repo owns what it gets, so an app that needs to differ can. Drawn here in this tool's own design, which is the one you are looking at.</flux:text>
        <flux:text>Run the setup first. These assume <code>design/</code> is already in the repo.</flux:text>
    </div>

    <div class="grid gap-(--ds-section) xl:grid-cols-2">
        @foreach ($blocks as $key => $block)
            @php
                // The block's own component, which is the same one a design
                // page draws. The picture and the prompt cannot end up
                // describing two different screens.
                $markup = Blocks::draw($key, $design, $register);
                $frame = Artboard::VIEWPORTS['laptop'];
            @endphp

            <div class="flex flex-col overflow-hidden rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface)" wire:key="block-{{ $key }}">
                {{--
                    A whole document in an iframe, scaled down, for the reason
                    the design page uses one: only an iframe has a viewport of
                    its own, and a transform-scaled div keeps measuring the
                    browser window, so the frame would render the desktop
                    layout squeezed and look checked when it is not.

                    Laptop rather than desktop, because at card width the
                    difference is a gutter nobody can see and the smaller frame
                    scales to something readable.
                --}}
                <div class="relative overflow-hidden border-b border-(--ds-border) bg-(--ds-sunken)"
                     x-data="{
                        scale: 0.3,
                        fit() { this.scale = ($el.clientWidth || {{ $frame['width'] }}) / {{ $frame['width'] }} },
                     }"
                     x-init="fit(); new ResizeObserver(() => fit()).observe($el)"
                     x-bind:style="`height: ${Math.round({{ $frame['height'] }} * scale)}px`"
                     style="height: {{ (int) round($frame['height'] * 0.3) }}px">
                    <iframe wire:key="draw-{{ $key }}-{{ $register }}"
                            title="{{ $block['label'] }}, drawn in this tool's design"
                            srcdoc="{{ Artboard::document($markup, $design->tokens(), $register) }}"
                            tabindex="-1"
                            aria-hidden="true"
                            {{-- Eight whole documents is eight parses on load,
                                 and only two are on screen. The bytes still
                                 ship in srcdoc; this only defers the work. --}}
                            loading="lazy"
                            class="pointer-events-none absolute start-0 top-0 origin-top-left border-0"
                            style="width: {{ $frame['width'] }}px; height: {{ $frame['height'] }}px"
                            x-bind:style="`transform: scale(${scale})`"></iframe>
                </div>

                <div class="flex flex-1 flex-col p-(--ds-card)">
                    <x-ds::section-heading level="2">{{ $block['label'] }}</x-ds::section-heading>
                    <flux:text class="mt-1">{{ $block['what'] }}</flux:text>

                    <p class="mt-3 text-sm text-(--ds-text-muted)">
                        <span class="ds-label">Decided by</span>
                        {{ $block['rules'] }}
                    </p>

                    {{-- On the card, not only inside the prompt, so somebody
                         choosing what to build next can see which one is going
                         to ask them questions the standard cannot answer. --}}
                    @if ($block['gap'])
                        <p class="mt-3 rounded-(--radius-md) bg-(--ds-warning-soft) px-3 py-2 text-sm text-(--ds-text-muted-on-sunken)">
                            <span class="font-medium text-(--ds-warning)">Not settled yet.</span>
                            {{ $block['gap'] }}
                        </p>
                    @endif

                    {{-- Three of these are drawn here and built by nobody. The
                         reason is on the card, because a missing button reads
                         as something forgotten. --}}
                    @unless ($block['prompt'])
                        <p class="mt-3 text-sm text-(--ds-text-muted)">{{ $block['note'] }}</p>
                    @endunless

                    <div class="mt-auto flex flex-wrap items-center gap-2 pt-4">
                        @if ($block['prompt'])
                            <x-copy-button :text="$prompts[$key]" label="Copy prompt" done="Copied" variant="primary" />

                            {{-- The prompt is readable before it is pasted.
                                 Pasting one into a repo unread is the thing
                                 that goes wrong, so it is a click away rather
                                 than hidden. --}}
                            <flux:modal.trigger name="read-{{ $key }}">
                                <flux:button variant="ghost">Read it</flux:button>
                            </flux:modal.trigger>
                        @endif

                        <flux:button :href="route('designs.show', ['slug' => $design->slug, 'screen' => $key, 'register' => $register])" wire:navigate variant="ghost" icon="arrows-pointing-out">Full size</flux:button>
                    </div>
                </div>
            </div>

            @if ($block['prompt'])
                <flux:modal name="read-{{ $key }}" class="md:w-[44rem]" wire:key="read-modal-{{ $key }}">
                    <div class="space-y-4">
                        <div>
                            <x-ds::section-heading level="2">{{ $block['label'] }}</x-ds::section-heading>
                            <flux:text class="mt-1">Paste this into Claude Code in the repo you are building.</flux:text>
                        </div>
                        <x-copy-block :text="$prompts[$key]" />
                    </div>
                </flux:modal>
            @endif
        @endforeach
    </div>
</div>
