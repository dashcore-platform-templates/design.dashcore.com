<?php

use App\Design\Standard;
use App\Models\Site;
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

    public function add(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'host' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9.-]+$/', Rule::unique('sites', 'host')],
        ]);

        $site = Site::create($data);

        $this->issued = $site->issueToken();
        $this->issuedFor = $site->id;
        $this->reset('name', 'host');
    }

    public function rotate(int $siteId): void
    {
        $site = Site::findOrFail($siteId);

        $this->issued = $site->issueToken();
        $this->issuedFor = $site->id;
    }

    public function forget(int $siteId): void
    {
        Site::findOrFail($siteId)->delete();
        $this->reset('issued', 'issuedFor');
    }

    public function with(): array
    {
        return [
            'sites' => Site::query()->with('latestReport')->orderBy('name')->get(),
            'current' => Standard::version(),
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
    </header>

    {{-- A token is shown once and then never again, so it gets a region of its
         own rather than a line somebody scrolls past. --}}
    @if ($issued)
        <div class="mb-(--ds-section) rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-accent-soft) p-(--ds-card)">
            <p class="text-sm font-medium text-(--ds-text-strong)">Copy this token now. It is not shown again.</p>
            <p class="mt-1 text-sm text-(--ds-text-muted)">Set it as <code class="font-mono text-xs">DESIGN_TOKEN</code> in that app's CI, beside <code class="font-mono text-xs">DESIGN_SOURCE</code>. It belongs in CI rather than in the repo.</p>
            <code class="mt-3 block overflow-x-auto rounded-(--radius-sm) border border-(--ds-border) bg-(--ds-surface) px-3 py-2 font-mono text-sm text-(--ds-text-strong)">{{ $issued }}</code>
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
                                description="Add one below, then put its token in that app's CI as DESIGN_TOKEN."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form wire:submit="add" class="mt-(--ds-section) max-w-xl">
        <x-ds::section-heading>Add a site</x-ds::section-heading>
        <p class="mt-1 text-sm text-(--ds-text-muted)">A site is added here first and given a token. A report authenticates as a site rather than claiming to be one, so nothing can add itself by finding the endpoint.</p>

        <div class="mt-(--ds-section) grid gap-(--ds-field-gap) md:grid-cols-2">
            <flux:input wire:model="name" label="Name" placeholder="ExecutiveOS" />
            <flux:input wire:model="host" label="Host" placeholder="executiveos.dashcore.com" />
        </div>

        <flux:button type="submit" variant="primary" class="mt-(--ds-section)">Add and issue a token</flux:button>
    </form>
</div>
