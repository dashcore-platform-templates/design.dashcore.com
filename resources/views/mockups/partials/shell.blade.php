{{--
    The app shell from standard/02-shell.md, as a static mockup. Two variants,
    chosen by the brief. Everything inside reads only --ds-* roles, --font-*
    and --radius-*, so the same markup renders any design.

    Receives: $design, $motif, $shell, $t (tokens), plus a $slot-like $content
    via @include of the page body ($page).
--}}
@php
    $name = $design->name();
    $eyebrow = $design->decisions['copy']['eyebrow'] ?? null;
    $nav = [
        ['Overview', 'home', true],
        ['Activity', 'clock', false],
        ['People', 'users', false],
        ['Reports', 'chart-bar', false],
        ['Settings', 'cog-6-tooth', false],
    ];
    $activeClass = match ($motif) {
        'accent-bar' => 'border-s-2 border-(--ds-accent) text-(--ds-text-strong) font-medium',
        'filled-stat' => 'bg-(--ds-accent-soft) text-(--ds-accent-content) font-medium rounded-(--radius-md)',
        default => 'bg-(--ds-sunken) text-(--ds-text-strong) font-medium rounded-(--radius-md)',
    };
    $idleClass = 'text-(--ds-text) hover:text-(--ds-text-strong) '.($motif === 'accent-bar' ? 'border-s-2 border-transparent' : 'rounded-(--radius-md)');
    $eyebrowClass = $motif === 'eyebrow' ? 'ds-eyebrow' : 'ds-label';
@endphp

@if ($shell === 'top-nav')
    <div class="flex min-h-[720px] flex-col">
        {{-- 12: the items stay inline down to md. Below that they move into a
             navmenu behind a toggle, and the brand block stays. --}}
        <header class="sticky top-0 flex h-14 items-center gap-4 border-b border-(--ds-border) bg-(--ds-surface) px-(--ds-gutter) md:gap-6">
            <div class="flex items-center gap-2.5">
                <span class="grid size-7 place-items-center rounded-(--radius-sm) bg-(--ds-accent) text-xs font-medium text-(--ds-on-accent)">{{ mb_substr($name, 0, 1) }}</span>
                <span class="text-sm font-medium text-(--ds-text-strong)">{{ $name }}</span>
            </div>
            <nav class="hidden items-center gap-1 text-sm md:flex">
                @foreach ($nav as [$label, $icon, $active])
                    <span class="flex h-(--ds-control) items-center gap-2 px-3 {{ $active ? ($motif === 'accent-bar' ? 'border-b-2 border-(--ds-accent) text-(--ds-text-strong) font-medium' : $activeClass) : 'text-(--ds-text) rounded-(--radius-md)' }}">{{ $label }}</span>
                @endforeach
            </nav>
            <span class="flex-1"></span>
            <flux:icon name="bell" variant="outline" class="size-5 text-(--ds-text-muted)" />
            <span class="grid size-8 place-items-center rounded-full bg-(--ds-sunken) text-xs font-medium text-(--ds-text)">JK</span>
            {{-- The navmenu toggle, which only exists below md. --}}
            <span class="grid size-(--ds-control) place-items-center rounded-(--radius-md) text-(--ds-text) md:hidden">
                <flux:icon name="bars-3" variant="outline" class="size-5" />
            </span>
        </header>
        <main class="mx-auto w-full max-w-[80rem] flex-1">
            @include($page, ['t' => $t, 'design' => $design, 'motif' => $motif, 'shell' => $shell, 'eyebrowClass' => $eyebrowClass])
        </main>
    </div>
@else
    <div class="flex min-h-[720px] flex-col lg:flex-row">
        {{-- 12: the sidebar is a drawer below lg, opened from a header that only
             exists at that width. It is never narrowed and never simply hidden,
             because a nav that disappears leaves somebody with no map. A mockup
             cannot open a drawer, so what is shown here is the closed state. --}}
        <header class="flex h-14 shrink-0 items-center gap-3 border-b border-(--ds-border) bg-(--ds-surface) px-(--ds-card) lg:hidden">
            <span class="grid size-(--ds-control) place-items-center rounded-(--radius-md) text-(--ds-text)">
                <flux:icon name="bars-3" variant="outline" class="size-5" />
            </span>
            <span class="grid size-7 place-items-center rounded-(--radius-sm) bg-(--ds-accent) text-xs font-medium text-(--ds-on-accent)">{{ mb_substr($name, 0, 1) }}</span>
            <span class="truncate text-sm font-medium text-(--ds-text-strong)">{{ $name }}</span>
            <span class="flex-1"></span>
            <span class="grid size-8 place-items-center rounded-full bg-(--ds-sunken) text-xs font-medium text-(--ds-text)">JK</span>
        </header>

        <aside class="hidden w-64 shrink-0 flex-col border-e border-(--ds-border) bg-(--ds-sunken) lg:flex dark:bg-(--ds-surface)">
            <div class="flex h-(--ds-brand) items-center gap-2.5 border-b border-(--ds-border) px-4">
                <span class="grid size-7 place-items-center rounded-(--radius-sm) bg-(--ds-accent) text-xs font-medium text-(--ds-on-accent)">{{ mb_substr($name, 0, 1) }}</span>
                <div class="min-w-0 leading-tight">
                    <p class="truncate text-sm font-medium text-(--ds-text-strong)">{{ $name }}</p>
                    @if ($eyebrow && $eyebrow !== $name)
                        <p class="truncate text-xs text-(--ds-text-muted)">{{ $eyebrow }}</p>
                    @endif
                </div>
            </div>
            <nav class="flex-1 px-2 py-4">
                <p class="{{ $eyebrowClass }} mb-1.5 px-3">Workspace</p>
                <ul class="space-y-0.5 text-sm">
                    @foreach ($nav as [$label, $icon, $active])
                        <li class="flex h-(--ds-nav-item) items-center gap-2.5 px-3 {{ $active ? $activeClass : $idleClass }}">
                            <flux:icon :name="$icon" variant="outline" class="size-5 shrink-0 {{ $active && $motif !== 'filled-stat' ? 'text-(--ds-accent-content)' : ($active ? '' : 'text-(--ds-text-muted)') }}" />
                            {{ $label }}
                        </li>
                    @endforeach
                </ul>
            </nav>
            <div class="flex items-center gap-2.5 border-t border-(--ds-border) px-4 py-3">
                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-(--ds-surface) text-xs font-medium text-(--ds-text) dark:bg-(--ds-sunken)">JK</span>
                <div class="min-w-0 leading-tight">
                    <p class="truncate text-sm font-medium text-(--ds-text-strong)">John K.</p>
                    <p class="truncate text-xs text-(--ds-text-muted)">Administrator</p>
                </div>
                <flux:icon name="chevron-up-down" variant="mini" class="ms-auto size-4 text-(--ds-text-muted)" />
            </div>
        </aside>
        <main class="min-w-0 flex-1">
            @include($page, ['t' => $t, 'design' => $design, 'motif' => $motif, 'shell' => $shell, 'eyebrowClass' => $eyebrowClass])
        </main>
    </div>
@endif
