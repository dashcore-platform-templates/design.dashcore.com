@php
    $second = $design->pairing()['second'];
    $statFont = $second['kind'] === 'display' || $second['kind'] === 'mono' ? 'font-second' : '';
    $upper = $second['condensed'] ? 'uppercase tracking-[0.02em]' : '';
    $stats = [
        ['Open items', '148', '+12 since yesterday', 'up'],
        ['Needs attention', '9', '3 waiting on you', 'warn'],
        ['Closed today', '6', 'Within the usual range', null],
        ['Due this week', '23', 'Across all teams', null],
    ];
    $rows = [
        ['RF-2041', 'Mara Ellison', 'Sales', '09:00', 'Done', 'success'],
        ['RF-2042', 'Devin Okafor', 'Operations', '09:15', 'Needs attention', 'warning'],
        ['RF-2043', 'Priya Natarajan', 'Sales', '09:30', 'Scheduled', 'info'],
        ['RF-2044', 'Tomas Reyes', 'Finance', '09:30', 'Cancelled', 'danger'],
        ['RF-2045', 'Ana Lindqvist', 'Operations', '10:00', 'Scheduled', 'info'],
        ['RF-2046', 'Samuel Adeyemi', 'Sales', '10:15', 'Done', 'success'],
    ];
    $selectedRow = 1;
    $mono = $second['kind'] === 'mono' ? 'font-second' : 'font-mono';
    $cardClass = $motif === 'hairline' ? 'bg-(--ds-surface)' : 'rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface)';
@endphp

@include('components.block.parts.page-header', ['title' => 'Today', 'description' => 'Tuesday 15 September. Everything since yesterday.', 'eyebrow' => null, 'motif' => $motif, 'eyebrowClass' => $eyebrowClass, 'design' => $design, 'slot' => new \Illuminate\Support\HtmlString('
    <button class="inline-flex h-(--ds-control) items-center gap-2 rounded-(--radius-lg) border border-(--ds-border-strong) bg-(--ds-surface) px-3 text-sm font-medium text-(--ds-text)">Export</button>
    <button class="inline-flex h-(--ds-control) items-center gap-2 rounded-(--radius-lg) bg-(--ds-accent) px-3 text-sm font-medium text-(--ds-on-accent)">New item</button>
')])

<div class="space-y-(--ds-section) px-(--ds-gutter) pb-(--ds-section) {{ $motif === 'hairline' ? 'pt-(--ds-section)' : '' }}">

    {{-- Stat row: x-ds-stat, surface treatment by motif (06, 07). --}}
    <div @class([
        'grid grid-cols-2 lg:grid-cols-4',
        'gap-px overflow-hidden border border-(--ds-border) bg-(--ds-border)' => $motif === 'hairline',
        'gap-(--ds-section)' => $motif !== 'hairline',
    ])>
        @foreach ($stats as $i => [$label, $value, $sub, $tone])
            @php
                $filled = $motif === 'filled-stat';
                $tile = match (true) {
                    $motif === 'hairline' => 'bg-(--ds-surface)',
                    $filled && $i === 0 => 'rounded-(--radius-lg) bg-(--ds-accent) text-(--ds-on-accent)',
                    $filled => 'rounded-(--radius-lg) bg-(--ds-accent-soft)',
                    default => 'rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface)',
                };
                $onFill = $filled && $i === 0;
            @endphp
            <div class="p-(--ds-stat) {{ $tile }}">
                <p class="{{ $eyebrowClass }} {{ $onFill ? '!text-(--ds-on-accent) opacity-80' : '' }}">{{ $label }}</p>
                <p class="mt-2 text-3xl font-semibold leading-none tabular-nums {{ $statFont }} {{ $upper }} {{ $onFill ? 'text-(--ds-on-accent)' : 'text-(--ds-text-strong)' }}">{{ $value }}</p>
                <p @class(['mt-2 text-xs', 'text-(--ds-on-accent) opacity-80' => $onFill, 'text-(--ds-success)' => ! $onFill && $tone === 'up', 'text-(--ds-warning)' => ! $onFill && $tone === 'warn', 'text-(--ds-text-muted)' => ! $onFill && ! $tone])>
                    @if ($tone === 'up' && ! $onFill)<flux:icon name="arrow-trending-up" variant="mini" class="-mt-0.5 me-1 inline size-4" />@endif{{ $sub }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-(--ds-section) lg:grid-cols-12">
        {{-- Table: head on sunken, 12px labels; body on surface; first column strong; numbers tabular (06). --}}
        <div class="lg:col-span-8 {{ $cardClass }} overflow-hidden {{ $motif === 'hairline' ? 'border-y border-(--ds-border)' : '' }}">
            <div class="flex items-center justify-between gap-3 border-b border-(--ds-border) px-(--ds-card) py-3">
                <h2 class="text-lg font-semibold leading-tight text-(--ds-text-strong)">Recent activity</h2>
                <div class="flex overflow-hidden rounded-(--radius-md) border border-(--ds-border) text-xs">
                    @foreach (['All', 'Sales', 'Operations', 'Finance'] as $j => $tab)
                        <span class="px-2.5 py-1 {{ $j === 0 ? 'bg-(--ds-sunken) font-medium text-(--ds-text-strong)' : 'text-(--ds-text-muted)' }} {{ $j > 0 ? 'border-s border-(--ds-border)' : '' }}">{{ $tab }}</span>
                    @endforeach
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-(--ds-sunken)">
                            @foreach (['Ref', 'Owner', 'Team', 'Time', 'Status'] as $h)
                                <th class="{{ $eyebrowClass }} !text-(--ds-text-muted-on-sunken) px-(--ds-cell-x) py-2 text-start font-medium {{ $h === 'Time' ? 'text-end' : '' }}">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $i => [$ref, $owner, $team, $time, $status, $tone])
                            @php $selected = $i === $selectedRow; @endphp
                            <tr @class([
                                'border-t border-(--ds-border)',
                                'shadow-[inset_2px_0_0_var(--ds-accent)]' => $selected && $motif === 'accent-bar',
                                'bg-(--ds-accent-soft)' => $selected && $motif !== 'accent-bar',
                            ])>
                                <td class="h-(--ds-row) px-(--ds-cell-x) {{ $mono }} text-xs text-(--ds-text-muted)">{{ $ref }}</td>
                                <td class="h-(--ds-row) px-(--ds-cell-x) font-medium text-(--ds-text-strong)">{{ $owner }}</td>
                                <td class="h-(--ds-row) px-(--ds-cell-x) text-(--ds-text)">{{ $team }}</td>
                                <td class="h-(--ds-row) px-(--ds-cell-x) text-end tabular-nums text-(--ds-text)">{{ $time }}</td>
                                <td class="h-(--ds-row) px-(--ds-cell-x)">
                                    <span class="inline-flex items-center rounded-(--radius-sm) px-1.5 py-0.5 text-xs font-medium" style="background: var(--ds-{{ $tone }}-soft); color: var(--ds-{{ $tone }});">{{ $status }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between border-t border-(--ds-border) px-(--ds-card) py-2 text-xs text-(--ds-text-muted)">
                <span>Showing 6 of 148</span>
                <span class="flex gap-1">
                    <span class="grid size-7 place-items-center rounded-(--radius-md) border border-(--ds-border)">1</span>
                    <span class="grid size-7 place-items-center rounded-(--radius-md) border border-transparent">2</span>
                    <span class="grid size-7 place-items-center rounded-(--radius-md) border border-transparent">3</span>
                </span>
            </div>
        </div>

        {{-- Side panel: a small list and a callout. --}}
        <div class="space-y-(--ds-section) lg:col-span-4">
            <div class="{{ $cardClass }} p-(--ds-card) {{ $motif === 'hairline' ? 'border-y border-(--ds-border)' : '' }}">
                <p class="{{ $eyebrowClass }}">By team</p>
                <ul class="mt-3 space-y-3">
                    @foreach ([['Sales', 64, 72], ['Operations', 48, 60], ['Finance', 36, 52]] as [$team, $n, $of])
                        <li>
                            <div class="flex items-baseline justify-between text-sm">
                                <span class="text-(--ds-text)">{{ $team }}</span>
                                <span class="tabular-nums text-(--ds-text-strong) {{ $mono }} text-xs">{{ $n }} / {{ $of }}</span>
                            </div>
                            <div class="mt-1.5 h-1 overflow-hidden rounded-full bg-(--ds-sunken)">
                                <div class="h-full bg-(--ds-accent)" style="width: {{ round($n / $of * 100) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="flex gap-3 rounded-(--radius-lg) p-(--ds-card)" style="background: var(--ds-warning-soft);">
                <flux:icon name="exclamation-triangle" variant="outline" class="mt-0.5 size-5 shrink-0" style="color: var(--ds-warning)" />
                <div class="text-sm">
                    <p class="font-medium" style="color: var(--ds-warning)">Two items have no owner</p>
                    <p class="mt-0.5 text-(--ds-text)">They will slip past this week unless someone picks them up.</p>
                    <p class="mt-2 text-sm font-medium" style="color: var(--ds-warning)">Assign an owner</p>
                </div>
            </div>
        </div>
    </div>
</div>
