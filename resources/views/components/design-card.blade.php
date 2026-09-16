@props(['design', 'usedBy'])
{{--
    One design on the board.

    Pulled out of the index page because archived designs are the same card in
    a second list, and two copies of it would drift the first time one changed.

    `usedBy` is the repositories whose newest report names this design. It is
    on the card as well as in the delete modal, because "which of these is
    actually in use" is a question somebody has before they reach for a menu.
--}}
@php
    $tokens = $design->tokens();
    $pairing = $design->pairing();
@endphp

{{-- The menu sits beside the link rather than inside it. A button nested in an
     anchor is not valid markup, and the two click targets would fight. --}}
<div class="relative" wire:key="design-{{ $design->slug }}">
    <a href="{{ route('designs.show', $design->slug) }}" wire:navigate @class([
        'group block overflow-hidden rounded-(--radius-lg) border bg-(--ds-surface) transition-colors duration-150',
        'border-(--ds-border) hover:border-(--ds-border-strong)' => ! $design->isArchived(),
        // Archived cards read as set aside rather than broken, so the border
        // dashes and nothing goes red.
        'border-dashed border-(--ds-border-strong)' => $design->isArchived(),
    ])>
        <div class="flex h-2">
            <span class="w-1/3" style="background: {{ $tokens['light']['accent'] }}"></span>
            @foreach ([200, 400, 600, 800] as $step)
                <span class="flex-1" style="background: {{ $tokens['neutral'][$step] }}"></span>
            @endforeach
        </div>
        <div class="p-(--ds-card)">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-semibold leading-tight text-(--ds-text-strong)">{{ $design->name() }}</h2>
                    <p class="mt-1 line-clamp-2 text-sm text-(--ds-text-muted)">{{ $design->brief['product'] ?? '' }}</p>
                </div>
                <div class="flex shrink-0 flex-col items-end gap-1">
                    <span class="rounded-(--radius-sm) px-1.5 py-0.5 text-xs font-medium {{ $tokens['contrast']['passes'] ? 'bg-(--ds-success-soft) text-(--ds-success)' : 'bg-(--ds-danger-soft) text-(--ds-danger)' }}">{{ $tokens['contrast']['passes'] ? 'Contrast ok' : 'Contrast fails' }}</span>
                    @if ($design->isArchived())
                        <x-ds::badge>Archived</x-ds::badge>
                    @endif
                </div>
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <div><dt class="ds-label">Shell</dt><dd class="text-(--ds-text-strong)">{{ $design->brief['shell'] ?? 'sidebar' }}</dd></div>
                <div><dt class="ds-label">Motif</dt><dd class="text-(--ds-text-strong)">{{ $design->decision('motif') }}</dd></div>
                <div><dt class="ds-label">Type</dt><dd class="truncate text-(--ds-text-strong)">{{ $pairing['sans']['family'] }} + {{ $pairing['second']['family'] }}</dd></div>
                <div><dt class="ds-label">Neutral, radius, density</dt><dd class="text-(--ds-text-strong)">{{ $design->decision('temperature') }}, {{ $design->decision('radius') }}, {{ $design->decision('density') }}</dd></div>
            </dl>

            {{-- 48px of clearance for the menu below. 40px is what the button
                 measures and is not on 05's scale; the next step up leaves a
                 gap rather than a collision. --}}
            <p class="mt-4 min-h-6 pe-12 text-sm text-(--ds-text-muted)">
                @if ($usedBy->isNotEmpty())
                    Used by {{ $usedBy->count() }} {{ \Illuminate\Support\Str::plural('app', $usedBy->count()) }}
                @elseif ($design->isOwn())
                    Used by this tool
                @endif
            </p>
        </div>
    </a>

    <flux:dropdown position="bottom" align="end" class="absolute bottom-(--ds-card) end-(--ds-card)">
            <flux:button variant="subtle" icon="ellipsis-horizontal" aria-label="Actions for {{ $design->name() }}" />

            <flux:menu>
                @if ($design->isArchived())
                    <flux:menu.item wire:click="restore('{{ $design->slug }}')" icon="arrow-uturn-left">Put back on the board</flux:menu.item>
                @elseif ($design->archivable())
                    <flux:menu.item wire:click="archive('{{ $design->slug }}')" icon="archive-box">Archive</flux:menu.item>
                @endif

                {{-- Always offered, never silently missing. Whether it can go
                     through is the modal's answer to give, with the list of
                     apps in front of whoever is deciding. --}}
                @if ($design->deletable())
                    <flux:menu.item wire:click="ask('{{ $design->slug }}')" icon="trash" variant="danger">Delete</flux:menu.item>
                @endif
            </flux:menu>
    </flux:dropdown>
</div>
