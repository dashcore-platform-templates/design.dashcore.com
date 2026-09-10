{{--
    <x-ds::page-header title="Today" description="Tuesday 15 September.">
        <flux:button variant="primary">New item</flux:button>
    </x-ds::page-header>

    The page header region from standard/02: optional eyebrow, title, one-line
    description, actions on the right. Reads the motif and title face from
    config('brand'), written by design:apply, so it matches the mockup.
--}}
@props(['title', 'description' => null, 'eyebrow' => null])
@php
    $motif = config('brand.motif', 'accent-bar');
    $labelClass = $motif === 'eyebrow' ? 'ds-eyebrow' : 'ds-label';
    $titleFont = config('brand.second_kind') !== 'mono' ? 'font-second' : '';
    $upper = config('brand.condensed') ? 'uppercase tracking-[0.02em]' : '';
@endphp
<header {{ $attributes->class(['flex flex-wrap items-end justify-between gap-4 pb-(--ds-section)', 'border-b border-(--ds-border) mb-(--ds-section)' => $motif === 'hairline']) }}>
    <div class="min-w-0">
        @if ($eyebrow || $motif === 'eyebrow')
            <p class="{{ $labelClass }} mb-1.5">{{ $eyebrow ?? config('brand.name') }}</p>
        @endif
        <h1 class="text-2xl font-semibold leading-[1.25] text-(--ds-text-strong) {{ $titleFont }} {{ $upper }}">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-(--ds-text-muted)">{{ $description }}</p>
        @endif
    </div>
    @if (trim($slot) !== '')
        <div class="flex items-center gap-2">{{ $slot }}</div>
    @endif
</header>
