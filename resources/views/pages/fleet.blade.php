<?php

use App\Design\Standard;
use App\Models\Site;
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
 * Read-only. A repository joins this board by being connected on /connect,
 * which is where its token is issued and where it is taken away again. This
 * page says what the fleet looks like; it is not where the fleet is edited.
 */
new #[Layout('layouts::tool')] #[Title('Fleet')] class extends Component
{
    public function with(): array
    {
        return [
            'sites' => Site::query()->with('latestReport')->orderBy('name')->get(),
            'current' => Standard::version(),
        ];
    }
}; ?>

<div class="px-(--ds-gutter) py-(--ds-section)">
    <x-ds::page-header title="Fleet" class="mb-(--ds-section)">
        <x-slot:description>Each app's default branch, as its CI last saw it. The standard reads <code class="font-mono text-xs">{{ $current }}</code> today.</x-slot:description>

        {{-- The board is read-only. Connecting is where a repository joins
             it, and where its token is issued. --}}
        <flux:button :href="route('connect')" wire:navigate icon="link" variant="ghost">Connect a repository</flux:button>
    </x-ds::page-header>

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
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-(--ds-cell-x) py-16">
                            {{-- Names the page that fixes it rather than a
                                 button that is no longer here. --}}
                            <x-ds::empty
                                size="compact"
                                icon="server-stack"
                                title="Nothing has reported yet."
                                description="A repository appears here once it is connected and its CI has run. Connect one from the button above."
                            />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
