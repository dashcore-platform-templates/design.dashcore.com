<?php

use App\Design\Blocks;
use App\Design\Design;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The foundation screens, one pasted prompt each.
 *
 * A design decides how an app feels. These are the screens it does not decide:
 * the shell, the error pages, settings, the landing page, email. Every app
 * built its own from the standard's prose, which is how the account menu ended
 * up in three different corners of the fleet.
 *
 * Not tied to a design, because a repo already holds one in design/. A prompt
 * naming a slug would be wrong the moment that app's design changed.
 */
new #[Layout('layouts::tool')] #[Title('Blocks')] class extends Component
{
    public function with(): array
    {
        return [
            'blocks' => Blocks::ALL,
            'prompts' => collect(Blocks::ALL)->map(fn (array $b, string $key) => Blocks::prompt($key))->all(),
            // The preview lives on a design, and the tool's own is the one
            // every reader here can already see.
            'previewOn' => Design::OWN,
        ];
    }
}; ?>

<div class="px-(--ds-gutter) py-(--ds-section)">
    <x-ds::page-header title="Blocks" class="mb-(--ds-section)">
        <x-slot:description>The screens a design does not decide. Paste one into Claude Code in the app you are building.</x-slot:description>
    </x-ds::page-header>

    <div class="mb-(--ds-section) max-w-prose space-y-3">
        <flux:text>A design sets the colour, type and spacing. It does not set that every app puts the account menu in the same corner, or that a 404 reads the same in fifteen products. Those are screens, and each app has been building its own.</flux:text>
        <flux:text>Each prompt builds one screen in the repo you run it in, on that app's design. The repo owns what it gets, so an app that needs to differ can.</flux:text>
        <flux:text>Run the setup first. These assume <code>design/</code> is already in the repo.</flux:text>
    </div>

    <div class="space-y-(--ds-section)">
        @foreach ($blocks as $key => $block)
            <div class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card)" wire:key="block-{{ $key }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <x-ds::section-heading level="2">{{ $block['label'] }}</x-ds::section-heading>
                        <flux:text class="mt-1">{{ $block['what'] }}</flux:text>
                    </div>
                    @if ($block['screen'])
                        <flux:button :href="route('designs.show', ['slug' => $previewOn, 'screen' => $block['screen']])" wire:navigate size="sm" variant="ghost" icon="eye">Preview</flux:button>
                    @endif
                </div>

                <dl class="mt-4 grid gap-x-(--ds-section) gap-y-2 text-sm sm:grid-cols-2">
                    <div><dt class="ds-label">Decided by</dt><dd class="text-(--ds-text)">{{ $block['rules'] }}</dd></div>
                    <div><dt class="ds-label">Writes</dt><dd class="text-(--ds-text)">{{ $block['builds'] }}</dd></div>
                </dl>

                {{-- Said on the card, not only inside the prompt, so somebody
                     choosing what to build next can see which one is going to
                     ask them questions the standard cannot answer. --}}
                @if ($block['gap'])
                    <p class="mt-3 rounded-(--radius-md) bg-(--ds-warning-soft) px-3 py-2 text-sm text-(--ds-text-muted-on-sunken)">
                        <span class="font-medium text-(--ds-warning)">Not settled yet.</span>
                        {{ $block['gap'] }}
                    </p>
                @endif

                <div class="mt-4">
                    <x-copy-block label="Prompt" :text="$prompts[$key]" />
                </div>
            </div>
        @endforeach
    </div>

    {{-- Its absence would otherwise read as something forgotten. --}}
    <div class="mt-(--ds-section) rounded-(--radius-lg) border border-dashed border-(--ds-border-strong) p-(--ds-card)">
        <x-ds::section-heading level="2">Sign-in is already built</x-ds::section-heading>
        <flux:text class="mt-1 max-w-prose">
            It has no prompt because it needs none. Its copy is one of the seven decisions, and <code>design:apply</code> points this app's auth layout at the one in the package, so it arrives with the setup.
        </flux:text>
        <flux:button :href="route('designs.show', ['slug' => $previewOn, 'screen' => 'sign-in'])" wire:navigate size="sm" variant="ghost" icon="eye" class="mt-3">Preview</flux:button>
    </div>
</div>
