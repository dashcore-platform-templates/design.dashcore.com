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

    public string $host = '';

    /** Shown once, straight after issuing. Never read back from anywhere. */
    public ?string $issued = null;

    public ?int $issuedFor = null;

    /** Named in the instructions, so they say which repository to go to. */
    public ?string $issuedHost = null;

    /** Straight to the page the secrets go on, rather than a route to follow. */
    public ?string $issuedSecretsUrl = null;

    public function add(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'host' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9.-]+$/', Rule::unique('sites', 'host')],
        ]);

        $site = Site::create($data);

        $this->show($site);
        $this->reset('name', 'host');
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
        $this->issuedHost = $site->host;
        $this->issuedSecretsUrl = $site->secretsUrl();
    }

    public function forget(int $siteId): void
    {
        Site::findOrFail($siteId)->delete();
        $this->reset('issued', 'issuedFor', 'issuedHost', 'issuedSecretsUrl');
    }

    public function with(): array
    {
        // Built here rather than in the markup. It contains ${{ }}, which is
        // GitHub's own syntax and Blade's as well, and @verbatim would stop the
        // copy-block component compiling along with it.
        $ciStep = <<<'YAML'
              - name: Report to design.dashcore.com
                env:
                  DESIGN_SOURCE: ${{ secrets.DESIGN_SOURCE }}
                  DESIGN_TOKEN: ${{ secrets.DESIGN_TOKEN }}
                run: php artisan design:audit --report
            YAML;

        return [
            'sites' => Site::query()->with('latestReport')->orderBy('name')->get(),
            'current' => Standard::version(),
            'ciStep' => $ciStep,
        ];
    }
}; ?>

<div class="px-(--ds-gutter) py-(--ds-section)">
    <header class="mb-(--ds-section) flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="ds-eyebrow mb-1.5">Fleet</p>
            <h1 class="text-2xl font-semibold leading-tight text-(--ds-text-strong)">Who is on the standard</h1>
            <p class="mt-1 text-sm text-(--ds-text-muted)">
                What each app last reported about itself. The standard reads
                <code class="font-mono text-xs">{{ $current }}</code> today.
            </p>
        </div>

        {{-- Adding a site happens once per app and then never again, so it is
             behind a button. A permanent form under the table would be the
             largest thing on a page that exists to be read at a glance. --}}
        <flux:modal.trigger name="add-site">
            <flux:button variant="primary" icon="plus">Add a site</flux:button>
        </flux:modal.trigger>
    </header>

    {{-- A token is shown once and then never again, so this is the one moment
         somebody has everything they need in front of them. It carries the
         whole recipe rather than a sentence saying to go and find it. --}}
    @if ($issued)
        @php $host = $issuedHost ?? 'the app'; @endphp
        <div class="mb-(--ds-section) space-y-(--ds-section) rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-accent-soft) p-(--ds-card)">
            <div>
                <x-ds::section-heading level="2">Set {{ $host }} up to report</x-ds::section-heading>
                <p class="mt-1 text-sm text-(--ds-text-muted)">Three steps, once per app. The token is shown here and never again.</p>
            </div>

            <div>
                <p class="text-sm font-medium text-(--ds-text-strong)">1. Add two secrets to that repository</p>
                <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-(--ds-text-muted)">
                    <span>On GitHub: <span class="text-(--ds-text)">Settings → Secrets and variables → Actions → New repository secret</span>. Twice, one for each.</span>
                    @if ($issuedSecretsUrl)
                        <flux:link href="{{ $issuedSecretsUrl }}" target="_blank" class="inline-flex items-center gap-1">
                            Open that page
                            <flux:icon name="arrow-top-right-on-square" class="size-3.5" />
                        </flux:link>
                    @endif
                </p>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    <x-copy-block label="DESIGN_TOKEN" :text="$issued" />
                    <x-copy-block label="DESIGN_SOURCE" :text="rtrim(config('app.url'), '/')" />
                </div>
            </div>

            <div>
                <p class="text-sm font-medium text-(--ds-text-strong)">2. Add a step to the workflow that already installs the app</p>
                <p class="mt-1 text-sm text-(--ds-text-muted)">After <code class="font-mono text-xs">composer install</code>, beside the tests. It never fails the build.</p>
                <x-copy-block label="In .github/workflows" :text="$ciStep" />
            </div>

            <div>
                <p class="text-sm font-medium text-(--ds-text-strong)">3. Push</p>
                <p class="mt-1 text-sm text-(--ds-text-muted)">The row above fills in on the next run. If it does not, the job's output says which of the two secrets is missing or wrong.</p>
            </div>
        </div>
    @endif

    <div class="overflow-x-auto rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface)">
        <table class="w-full min-w-[54rem] text-sm">
            <thead>
                <tr class="border-b border-(--ds-border)">
                    <th class="ds-label px-(--ds-cell-x) py-2.5 text-start font-normal">Site</th>
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
                            <span class="mt-0.5 block font-mono text-xs text-(--ds-text-muted)">{{ $site->host }}</span>
                        </td>
                        <td class="px-(--ds-cell-x) py-3 text-(--ds-text-muted)">{{ $site->design_slug ?? '—' }}</td>
                        <td class="px-(--ds-cell-x) py-3">
                            @if ($report === null)
                                <x-ds::badge>Never reported</x-ds::badge>
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
                            @else
                                {{-- A count means nothing without the package that produced
                                     it, which is the column beside this one. --}}
                                <span class="font-medium tabular-nums {{ $report->total_findings === 0 ? 'text-(--ds-text-muted)' : 'text-(--ds-text-strong)' }}">{{ $report->total_findings }}</span>
                                @if ($report->files_with_findings > 0)
                                    <span class="ms-1 text-xs text-(--ds-text-muted)">in {{ $report->files_with_findings }}</span>
                                @endif
                            @endif
                        </td>
                        <td class="px-(--ds-cell-x) py-3 text-(--ds-text-muted)">{{ $site->last_reported_at?->diffForHumans() ?? 'never' }}</td>
                        <td class="px-(--ds-cell-x) py-3 text-end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="ghost" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item wire:click="rotate({{ $site->id }})" icon="key">Issue a new token</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item wire:click="forget({{ $site->id }})" wire:confirm="Remove {{ $site->host }} and every report it has sent?" icon="trash" variant="danger">Remove</flux:menu.item>
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
                <x-ds::section-heading>Add a site</x-ds::section-heading>
                <p class="mt-1 text-sm text-(--ds-text-muted)">It is given a token, which goes in that app's CI. A report authenticates as a site rather than claiming to be one, so nothing can add itself by finding the endpoint.</p>
            </div>

            <div class="space-y-(--ds-field-gap)">
                <flux:input wire:model="name" label="Name" placeholder="ExecutiveOS" />
                <flux:input wire:model="host" label="Host" placeholder="executiveos.dashcore.com" description="The repository is assumed to match, under dashcoretech." />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Add and issue a token</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
