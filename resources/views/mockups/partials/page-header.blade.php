{{-- The page header region from 02: eyebrow, title, one-line description, actions. The custom component x-ds-page-header in a kit. --}}
@props(['eyebrow' => null, 'title', 'description' => null, 'motif', 'eyebrowClass', 'design'])
@php $second = $design->pairing()['second']; $titleFont = $second['kind'] !== 'mono' ? 'font-second' : ''; $upper = $second['condensed'] ? 'uppercase tracking-[0.02em]' : ''; @endphp
<header @class(['flex flex-wrap items-end justify-between gap-4 px-(--ds-gutter) pt-(--ds-section) pb-(--ds-section)', 'border-b border-(--ds-border)' => $motif === 'hairline'])>
    <div class="min-w-0">
        @if ($eyebrow || $motif === 'eyebrow')
            <p class="{{ $eyebrowClass }} mb-1.5">{{ $eyebrow ?? $design->name() }}</p>
        @endif
        <h1 class="text-2xl font-semibold leading-[1.25] text-(--ds-text-strong) {{ $titleFont }} {{ $upper }}">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-(--ds-text-muted)">{{ $description }}</p>
        @endif
    </div>
    <div class="flex items-center gap-2">
        {{ $slot }}
    </div>
</header>
