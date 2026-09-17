<?php

use App\Design\Handoff;
use App\Models\Site;
use Dashcore\DesignKit\Workflow;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * How a repository reaches the fleet board.
 *
 * A guide and nothing else. There is no form because there is nothing to
 * register: a repository joins the board the first time it reports, and it
 * reports by proving which one it is with a token GitHub mints for the job.
 * Nothing is stored in the repository, so nothing is issued here.
 *
 * Kept to four steps, each one a title, a snippet and at most one line. An
 * earlier version explained the mechanism at every step, which made a page
 * somebody follows into a page somebody reads. The reasoning lives in these
 * comments and in GitHubIdentity; the page says what to do.
 *
 * The snippets are the package's own constants, which are the same ones
 * `design:connect` checks for. A guide telling somebody to write something the
 * checker does not recognise is worse than no guide.
 */
new #[Layout('layouts::tool')] #[Title('Connect')] class extends Component
{
    public function with(): array
    {
        return [
            'connected' => Site::query()->count(),
            'permissions' => Workflow::PERMISSIONS,
            'step' => Workflow::STEP,
            'upgrade' => implode(PHP_EOL, Handoff::updateCommands()),
        ];
    }
}; ?>

<div class="px-(--ds-gutter) py-(--ds-section)">
    <x-ds::page-header title="Connect a repository" class="mb-(--ds-section)">
        <x-slot:description>Four steps in the repo. Nothing to fill in here.</x-slot:description>

        <flux:button :href="route('fleet')" wire:navigate icon="server-stack" variant="ghost">
            Fleet{{ $connected > 0 ? ' ('.$connected.')' : '' }}
        </flux:button>
    </x-ds::page-header>

    <div class="max-w-2xl space-y-(--ds-section)">
        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card)">
            <x-ds::section-heading level="2">1. Update the kit</x-ds::section-heading>
            <div class="mt-3">
                <x-copy-block :text="$upgrade" />
            </div>
            {{-- `composer update` stays inside the pinned major, so it will not
                 get there. Said as an instruction rather than as the reason. --}}
            <p class="mt-2 text-sm text-(--ds-text-muted)">Skip if already on {{ Handoff::MAJOR }}. Plain <code class="font-mono text-xs">composer update</code> will not get there.</p>
        </section>

        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card)">
            <x-ds::section-heading level="2">2. Add two blocks to the CI workflow</x-ds::section-heading>
            <p class="mt-1 text-sm text-(--ds-text-muted)">On the job that already installs the app.</p>

            <div class="mt-3 space-y-4">
                <x-copy-block label="On the job" :text="$permissions" />
                <x-copy-block label="After composer install" :text="$step" />
            </div>

            {{-- Naming any permission turns GitHub's defaults off, so a job
                 asking for id-token alone cannot check itself out. It still
                 runs, which is what makes it expensive to find. --}}
            <p class="mt-2 text-sm text-(--ds-text-muted)">Keep <code class="font-mono text-xs">contents: read</code>, or the job cannot check itself out.</p>
        </section>

        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card)">
            <x-ds::section-heading level="2">3. Add your token</x-ds::section-heading>
            <div class="mt-3">
                <x-copy-block label="In the app's .env" text="DESIGN_TOKEN=dsn_…" />
            </div>
            {{-- A person's credential, for the commands a person runs. CI needs
                 none of it: GitHub vouches for the job. --}}
            <p class="mt-2 text-sm text-(--ds-text-muted)">
                Make one on <flux:link :href="route('account')" wire:navigate>your account</flux:link>. Your machine only, never in CI.
            </p>
        </section>

        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card)">
            <x-ds::section-heading level="2">4. Check, then push</x-ds::section-heading>
            <div class="mt-3">
                <x-copy-block text="php artisan design:connect" />
            </div>
            <p class="mt-2 text-sm text-(--ds-text-muted)">
                It says what is still missing. Push to the default branch and the row shows up on the <flux:link :href="route('fleet')" wire:navigate>fleet</flux:link>.
            </p>
        </section>
    </div>
</div>
