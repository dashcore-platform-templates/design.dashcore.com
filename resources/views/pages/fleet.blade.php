<?php

use App\Design\Standard;
use App\Models\Site;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Every app running the standard, and how far off it each one is.
 *
 * The rows are what sites reported about themselves through
 * `design:audit --report`. The tool never reads anybody's repo: it holds
 * counts by rule and two version stamps, and that is enough to answer the
 * two questions worth asking, which are how far off a site is and whether it
 * is getting better.
 *
 * Behind sign-in, because this is a list of every Dashcore product and its
 * failings. The design pages stay public, because kits pull their pack from
 * them without a session.
 */
new #[Layout('layouts::tool')] #[Title('Fleet')] class extends Component
{
    public string $name = '';

    public string $repository = '';

    /** Shown once, straight after issuing. Never read back from anywhere. */
    public ?string $issued = null;

    public ?int $issuedFor = null;

    /** Named in the instructions, so they say which repository to go to. */
    public ?string $issuedRepository = null;

    /** Straight to the page the secrets go on, rather than a route to follow. */
    public ?string $issuedSecretsUrl = null;

    public function add(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            // owner/name, because that is what a repository is called and what
            // the links on this page are built from.
            'repository' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._-]+\/[A-Za-z0-9._-]+$/', Rule::unique('sites', 'repository')],
        ]);

        $site = Site::create($data);

        $this->show($site);
        $this->reset('name', 'repository');
        Flux::modal('add-site')->close();
    }

    public function rotate(int $siteId): void
    {
        $site = Site::findOrFail($siteId);

        $this->show($site);
    }

    /** The one place a token is put on screen, so it is put there the same way twice. */
    private function show(Site $site): void
    {
        $this->issued = $site->issueToken();
        $this->issuedFor = $site->id;
        $this->issuedRepository = $site->repository;
        $this->issuedSecretsUrl = $site->secretsUrl();
    }

    public function forget(int $siteId): void
    {
        Site::findOrFail($siteId)->delete();
        $this->reset('issued', 'issuedFor', 'issuedRepository', 'issuedSecretsUrl');
    }

    public function with(): array
    {
        // Built here rather than in the markup. It contains ${{ }}, which is
        // GitHub's own syntax and Blade's as well, and @verbatim would stop the
        // copy-block component compiling along with it.
        // The `if` matters. A row here says what a repository's default branch
        // looks like, and without this a pull request build would overwrite it
        // with a branch nobody has merged.
        $ciStep = <<<'YAML'
              - name: Report to design.dashcore.com
                if: github.ref_name == github.event.repository.default_branch
                env:
                  DESIGN_SOURCE: ${{ secrets.DESIGN_SOURCE }}
                  DESIGN_TOKEN: ${{ secrets.DESIGN_TOKEN }}
                run: php artisan design:audit --report
            YAML;

        return [
            'sites' => Site::query()->with('latestReport')->orderBy('name')->get(),
            'current' => Standard::version(),
            'ciStep' => $ciStep,
            'upgrade' => implode(PHP_EOL, \App\Design\Handoff::updateCommands()),
            'connectPrompt' => \App\Design\Handoff::connectPrompt($this->issuedRepository ?? '<owner/name>', rtrim(config('app.url'), '/')),
        ];
    }
}; ?>

<div class="px-(--ds-gutter) py-(--ds-section)">
    <x-ds::page-header title="Fleet" class="mb-(--ds-section)">
        <x-slot:description>Each app's default branch, as its CI last saw it. The standard reads <code class="font-mono text-xs">{{ $current }}</code> today.</x-slot:description>

        {{-- Adding a site happens once per app and then never again, so it is
             behind a button. A permanent form under the table would be the
             largest thing on a page that exists to be read at a glance. --}}
        <flux:modal.trigger name="add-site">
            <flux:button variant="primary" icon="plus">Add site</flux:button>
        </flux:modal.trigger>
    </x-ds::page-header>

    {{-- A token is shown once and then never again, so this is the one moment
         somebody has everything they need in front of them. It carries the
         whole recipe rather than a sentence saying to go and find it. --}}
    @if ($issued)
        @php $repo = $issuedRepository ?? 'the app'; @endphp
        <div class="mb-(--ds-section) space-y-(--ds-section) rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-accent-soft) p-(--ds-card)">
            <div>
                <x-ds::section-heading level="2">Connect {{ $repo }}</x-ds::section-heading>
                <flux:text class="mt-1">Paste it into Claude Code in that repo. The agent does the repo work and stops at the secrets.</flux:text>
                {{-- Said before the prompt rather than only enforced inside it.
                     The prompt does stop on an app with no design, but by then
                     somebody has opened an agent to find out something the page
                     could have told them. --}}
                <p class="mt-2 text-sm text-(--ds-text-muted)">
                    It needs a design applied already. If this app has never had one,
                    <flux:link :href="route('designs.index')" wire:navigate>start there</flux:link> instead.
                </p>
            </div>

            <x-copy-block label="Agent prompt" :text="$connectPrompt" />

            {{-- Beside the prompt rather than inside it. The agent is told to
                 stop here and never to ask for the token, so the one step it
                 cannot do sits next to the one it can. --}}
            <div>
                <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm font-medium text-(--ds-text-strong)">
                    <span>Secrets</span>
                    @if ($issuedSecretsUrl)
                        <flux:link href="{{ $issuedSecretsUrl }}" target="_blank" class="inline-flex items-center gap-1 font-normal">
                            Open that page
                            <flux:icon name="arrow-top-right-on-square" class="size-3.5" />
                        </flux:link>
                    @endif
                </p>
                <p class="mt-1 text-sm text-(--ds-text-muted)">Settings → Secrets and variables → Actions on that repo. The token is shown once.</p>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <x-copy-block label="DESIGN_TOKEN" :text="$issued" />
                    <x-copy-block label="DESIGN_SOURCE" :text="rtrim(config('app.url'), '/')" />
                </div>
            </div>

            <flux:accordion>
                <flux:accordion.item>
                    <flux:accordion.heading>By hand</flux:accordion.heading>
                    <flux:accordion.content>
                        <div class="space-y-4 pt-1">
                            <div>
                                <p class="text-xs leading-snug text-(--ds-text-muted)">Skip if already on {{ \App\Design\Handoff::MAJOR }}. <code>composer update</code> will not get there: it stays inside the major the app is pinned to.</p>
                                <x-copy-block label="Commands" :text="$upgrade" />
                            </div>
                            <div>
                                <p class="text-xs leading-snug text-(--ds-text-muted)">In the workflow that already installs the app, after <code>composer install</code>. The <code>if</code> stops a pull request overwriting the row.</p>
                                <x-copy-block label="In .github/workflows" :text="$ciStep" />
                            </div>
                            <p class="text-xs leading-snug text-(--ds-text-muted)">The row fills in on the first run after the secrets exist. If it does not, the job says which one is wrong.</p>
                        </div>
                    </flux:accordion.content>
                </flux:accordion.item>
            </flux:accordion>
        </div>
    @endif

    <div class="overflow-x-auto rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface)">
        <table class="w-full min-w-[54rem] text-sm">
            <thead>
                <tr class="border-b border-(--ds-border)">
                    <th class="ds-label px-(--ds-cell-x) py-2.5 text-start font-normal">Repository</th>
                    <th class="ds-label px-(--ds-cell-x) py-2.5 text-start font-normal">Design</th>
                    <th class="ds-label px-(--ds-cell-x) py-2.5 text-start font-normal">Standard</th>
                    <th class="ds-label px-(--ds-cell-x) py-2.5 text-start font-normal">Package</th>
                    <th class="ds-label px-(--ds-cell-x) py-2.5 text-end font-normal">Findings</th>
                    <th class="ds-label px-(--ds-cell-x) py-2.5 text-start font-normal">Last reported</th>
                    <th class="ds-label px-(--ds-cell-x) py-2.5 text-end font-normal"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sites as $site)
                    @php $report = $site->latestReport->first(); @endphp
                    <tr class="border-b border-(--ds-border) last:border-0" wire:key="site-{{ $site->id }}">
                        <td class="px-(--ds-cell-x) py-3">
                            <span class="font-medium text-(--ds-text-strong)">{{ $site->name }}</span>
                            <a href="{{ $site->repositoryUrl() }}" target="_blank" class="mt-0.5 flex items-center gap-1 font-mono text-xs text-(--ds-text-muted) hover:text-(--ds-text)">
                                {{-- Says github.com outright. A bare owner/name
                                     reads as a hostname, which is exactly the
                                     confusion this column used to carry. --}}
                                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="size-3.5 shrink-0"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z"/></svg>
                                <span>github.com/{{ $site->repository }}</span>
                            </a>
                        </td>
                        <td class="px-(--ds-cell-x) py-3 text-(--ds-text-muted)">{{ $site->design_slug ?? '—' }}</td>
                        <td class="px-(--ds-cell-x) py-3">
                            @if ($report === null)
                                <x-ds::badge>Never reported</x-ds::badge>
                            @elseif (! $report->hasDesign())
                                {{-- A repo can be added here before anyone sets
                                     it up. Not started is not drift, so it is
                                     neutral rather than a warning, and it links
                                     to the next move rather than being a row
                                     that reports a problem and stops there. --}}
                                <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <x-ds::badge>Not applied</x-ds::badge>
                                    <flux:link :href="route('designs.index')" wire:navigate class="text-xs">Set it up</flux:link>
                                </span>
                            @elseif ($report->isCurrent())
                                <x-ds::badge status="success">Current</x-ds::badge>
                            @else
                                {{-- Behind is not a failure, and half the entries in the
                                     changelog are relaxations with nothing to do. --}}
                                <x-ds::badge status="warning">{{ $report->standard_version ?? 'Unstamped' }}</x-ds::badge>
                            @endif
                        </td>
                        <td class="px-(--ds-cell-x) py-3 font-mono text-xs text-(--ds-text-muted)">{{ $report?->package_version ?? '—' }}</td>
                        <td class="px-(--ds-cell-x) py-3 text-end">
                            @if ($report === null)
                                <span class="text-(--ds-text-muted)">—</span>
                            @elseif ($report->total_findings === 0)
                                {{-- Zero is an answer and reads better as a word than
                                     as a nought sitting under numbers in the hundreds. --}}
                                <span class="text-(--ds-text-muted)">None</span>
                            @else
                                {{-- "22 in 8" was a riddle: in eight what. The unit goes
                                     in, and the rules behind the number are on hover,
                                     because the count alone never says what to fix.

                                     A count also means nothing without the package that
                                     produced it, which is the column beside this one. --}}
                                <span
                                    class="cursor-help"
                                    title="{{ collect($report->findings)->map(fn (int $n, string $rule) => "{$rule}: {$n}")->implode("\n") }}"
                                >
                                    <span class="font-medium tabular-nums text-(--ds-text-strong)">{{ $report->total_findings }}</span>
                                    <span class="ms-1 text-xs text-(--ds-text-muted)">in {{ $report->files_with_findings }} {{ Str::plural('file', $report->files_with_findings) }}</span>
                                </span>
                            @endif
                        </td>
                        <td class="px-(--ds-cell-x) py-3 text-(--ds-text-muted)">{{ $site->last_reported_at?->diffForHumans() ?? 'never' }}</td>
                        <td class="px-(--ds-cell-x) py-3 text-end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item wire:click="rotate({{ $site->id }})" icon="key">New token</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item wire:click="forget({{ $site->id }})" wire:confirm="Remove {{ $site->repository }} and every report it has sent?" icon="trash" variant="danger">Remove</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-(--ds-cell-x) py-16">
                            <x-ds::empty
                                size="compact"
                                icon="server-stack"
                                title="No sites yet."
                                description="Add one from the button above, then put its token in that app's CI."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="add-site" class="md:w-96">
        <form wire:submit="add" class="space-y-(--ds-section)">
            <div>
                <x-ds::section-heading>Add site</x-ds::section-heading>
                <p class="mt-1 text-sm text-(--ds-text-muted)">It is given a token, which goes in that app's CI. A report authenticates as a site rather than claiming to be one, so nothing can add itself by finding the endpoint.</p>
            </div>

            <div class="space-y-(--ds-field-gap)">
                <flux:input wire:model="name" label="Name" placeholder="ExecutiveOS" />
                <flux:input wire:model="repository" label="Repository" placeholder="dashcoretech/executiveos.dashcore.com" description="On GitHub, as owner/name. This is where its CI reports from." />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Add site</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
