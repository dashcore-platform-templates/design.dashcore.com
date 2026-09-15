<?php

use App\Design\Handoff;
use App\Design\Standard;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * What changed in the standard, and how an app already on it picks the change
 * up. The two belong together: reading that a rule moved is only useful beside
 * the command that acts on it.
 *
 * Kept off the Standard page on purpose. That page is reference, read while
 * building a screen. This one is read when something has moved.
 */
new #[Layout('layouts::tool')] #[Title('What changed')] class extends Component
{
    public function with(): array
    {
        return [
            'changelog' => Standard::changelog(),
            'version' => Standard::version(),
            'count' => Standard::changelogCount(),
            'commands' => implode(PHP_EOL, Handoff::updateCommands()),
            'prompt' => Handoff::updatePrompt(),
        ];
    }
}; ?>

<div class="flex">
    <div class="min-w-0 flex-1 px-(--ds-gutter) py-(--ds-section)">
        <x-ds::page-header
            eyebrow="Reference"
            title="What changed"
            :description="$count.' '.Str::plural('change', $count).' to the standard so far. An app compares its own stamp against this one.'"
            class="mb-(--ds-section)"
        >
            <div class="text-end">
                <p class="ds-label mb-1.5">Version</p>
                <p class="font-mono text-sm text-(--ds-text-strong)">{{ $version }}</p>
            </div>
        </x-ds::page-header>

        <section class="mb-(--ds-section) rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card)">
            <p class="ds-label mb-1.5">Picking a change up</p>
            <p class="mb-4 max-w-2xl text-sm text-(--ds-text-muted)">
                Setting an app up happens once, so do not repeat it. An app whose <code>design/STANDARD.md</code> shows a version other than
                <span class="font-mono text-(--ds-text-strong)">{{ $version }}</span> is behind. Replace <code>&lt;slug&gt;</code> with that app's design, or open the design and use
                the panel there, which fills it in.
            </p>
            <div class="grid gap-4 lg:grid-cols-2">
                <x-copy-block label="Prompt for the agent" :text="$prompt" />
                <x-copy-block label="Or by hand, in the target repo" :text="$commands" />
            </div>
        </section>

        <article class="ds-prose">{!! Standard::html($changelog) !!}</article>
    </div>
</div>
