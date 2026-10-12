<?php

use App\Design\Handoff;
use App\Models\Site;
use Dashcore\DesignKit\Workflow;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Both halves of setting up, and which one has to happen first.
 *
 * They are separate things and a flat list of steps hid that. The token is a
 * fact about a laptop, done once ever, and it is what lets any design be
 * pulled at all. The workflow is a fact about a repository, done once per
 * repository, and it is what puts that repository on the board. Side by side,
 * somebody on their third repo can see the left column is already done.
 *
 * The right column is a spine rather than a list, because the steps are in an
 * order and the last one produces something. A list says "here are three
 * things"; a spine says "then, then, and now this is true".
 *
 * No form anywhere. A repository joins the board the first time it reports,
 * proving which one it is with a token GitHub mints for the job, so nothing is
 * registered here and nothing is stored in the repository.
 *
 * The snippets are the package's own constants, the same ones `design:connect`
 * checks for. A guide telling somebody to write something the checker does not
 * recognise is worse than no guide.
 */
new #[Layout('layouts::tool')] #[Title('Connect')] class extends Component
{
    public function with(): array
    {
        return [
            'connected' => Site::query()->count(),
            'permissions' => Workflow::PERMISSIONS,
            'step' => Workflow::STEP,
            'example' => $this->example(),
            'upgrade' => implode(PHP_EOL, Handoff::updateCommands()),
        ];
    }

    /**
     * The two blocks in a job, so somebody can see where they land.
     *
     * Composed from the constants rather than written out, because an example
     * that drifts from the snippets beside it is worse than no example. The
     * lines around them are the ordinary shape of a Laravel CI job; this one is
     * not meant to be copied, which is why it is not a copy block.
     */
    private function example(): string
    {
        $indent = fn (string $yaml, int $spaces) => collect(explode("\n", $yaml))
            ->map(fn (string $line) => $line === '' ? '' : str_repeat(' ', $spaces).$line)
            ->implode("\n");

        return implode("\n", [
            'jobs:',
            '  ci:',
            '    runs-on: ubuntu-latest',
            $indent(Workflow::PERMISSIONS, 4),
            '    steps:',
            '      - uses: actions/checkout@v5',
            '      - run: composer install --no-interaction',
            '      - run: php artisan test',
            $indent(Workflow::STEP, 6),
        ]);
    }
}; ?>


<div class="px-(--ds-gutter) py-(--ds-section)">
    <x-ds::page-header title="Setting up" class="mb-(--ds-section)">
        <x-slot:description>Your machine once, then a few lines in each repo. Nothing to fill in here.</x-slot:description>

        <flux:button :href="route('fleet')" wire:navigate icon="server-stack" variant="ghost">
            Fleet{{ $connected > 0 ? ' ('.$connected.')' : '' }}
        </flux:button>
    </x-ds::page-header>

    {{-- One column below lg, two from lg, per standard 12. The prerequisite
         reads first in both, because on a phone the columns stack in order. --}}
    <div class="grid gap-(--ds-section) lg:grid-cols-3">
        <div class="lg:col-span-1">
            <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card)">
                <p class="ds-eyebrow">First</p>
                <x-ds::section-heading level="2" class="mt-1">Your machine</x-ds::section-heading>
                {{-- Scope, not reason. Somebody on their third repo needs to
                     know they can skip this, and that is all this line is. --}}
                <flux:text class="mt-1">Once, ever. Without it no design can be pulled.</flux:text>

                <div class="mt-4">
                    <x-copy-block label="In the app's .env" text="DESIGN_TOKEN=dsn_…" />
                </div>
                <p class="mt-2 text-sm text-(--ds-text-muted)">
                    Make one on <flux:link :href="route('account')" wire:navigate>your account</flux:link>, named after this machine. Your machine only, never in CI.
                </p>
            </section>
        </div>

        <div class="lg:col-span-2">
            <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card)">
                <p class="ds-eyebrow">Then, per repository</p>
                <x-ds::section-heading level="2" class="mt-1">Each repository</x-ds::section-heading>
                <flux:text class="mt-1">Needs the token above. This is what puts the repo on the fleet.</flux:text>

                <ol class="mt-6">
                    <x-step number="1">
                        <p class="text-sm font-medium text-(--ds-text-strong)">Update the kit</p>
                        <div class="mt-3">
                            <x-copy-block :text="$upgrade" />
                        </div>
                        {{-- Nothing about versions any more. New rules arrive on
                             an ordinary update and show up as findings, which is
                             information rather than damage: reporting cannot
                             fail a build. --}}
                        <p class="mt-2 text-sm text-(--ds-text-muted)">New rules arrive this way and show up as findings on the fleet. Work them when you choose to.</p>
                    </x-step>

                    <x-step number="2">
                        <p class="text-sm font-medium text-(--ds-text-strong)">Add two blocks to the CI workflow</p>
                        <p class="mt-1 text-sm text-(--ds-text-muted)">In <code class="font-mono text-xs">.github/workflows</code>, on the job that runs your tests.</p>

                        {{-- The labels name a line somebody can find in their
                             own file. "On the job" and "after composer install"
                             were true and useless: both describe a place you
                             have to work out. --}}
                        <div class="mt-3 space-y-4">
                            <x-copy-block label="Beside runs-on" :text="$permissions" />
                            <x-copy-block label="Last in steps" :text="$step" />
                        </div>

                        {{-- Naming any permission turns GitHub's defaults off,
                             so a job asking for id-token alone cannot check
                             itself out. It still runs, which is the expensive
                             part. --}}
                        <p class="mt-2 text-sm text-(--ds-text-muted)">Keep <code class="font-mono text-xs">contents: read</code>, or the job cannot check itself out.</p>

                        {{-- Folded with <details> rather than flux:accordion, which is Pro. --}}
                        <details class="group mt-4 border-y border-(--ds-border)">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 py-3 text-sm font-medium text-(--ds-text-strong) [&::-webkit-details-marker]:hidden">What it looks like when done<flux:icon name="chevron-down" variant="micro" class="size-4 shrink-0 text-(--ds-text-muted) transition-transform group-open:rotate-180" /></summary>
                            <pre class="mb-3 overflow-x-auto rounded-(--radius-md) bg-(--ds-sunken) p-4 font-mono text-xs leading-relaxed text-(--ds-text)">{{ $example }}</pre>
                        </details>
                    </x-step>

                    <x-step number="3">
                        <p class="text-sm font-medium text-(--ds-text-strong)">Check, then push</p>
                        <div class="mt-3">
                            <x-copy-block text="php artisan design:connect" />
                        </div>
                        <p class="mt-2 text-sm text-(--ds-text-muted)">It says what is still missing, then push to the default branch.</p>
                    </x-step>

                    {{-- The end of the spine is what the three steps were for,
                         not a fourth thing to do. No number, no rail after it. --}}
                    <x-step last>
                        <p class="text-sm font-medium text-(--ds-text-strong)">The row appears on the fleet</p>
                        <p class="mt-1 text-sm text-(--ds-text-muted)">
                            After the next run on the default branch. Nothing to confirm here. <flux:link :href="route('fleet')" wire:navigate>Open the fleet</flux:link>.
                        </p>
                    </x-step>
                </ol>
            </section>
        </div>
    </div>
</div>
