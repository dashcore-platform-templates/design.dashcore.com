{{--
    <x-ds::empty icon="inbox" title="Nothing new since yesterday." description="New items appear here as they come in.">
        <flux:button>Import a list</flux:button>
    </x-ds::empty>

    The empty state from standard/06: one Heroicon at 24px in muted text, one
    line saying what belongs here, one line saying how to add it, one default
    button. Never an illustration.
--}}
@props(['icon' => 'inbox', 'title', 'description' => null])
@php $motif = config('brand.motif', 'accent-bar'); @endphp
<div {{ $attributes->class(['grid min-h-[320px] place-items-center bg-(--ds-surface)', 'rounded-(--radius-lg) border border-(--ds-border)' => $motif !== 'hairline', 'border-y border-(--ds-border)' => $motif === 'hairline']) }}>
    <div class="max-w-sm px-6 text-center">
        <flux:icon :name="$icon" variant="outline" class="mx-auto size-6 text-(--ds-text-muted)" />
        <p class="mt-3 text-sm font-medium text-(--ds-text-strong)">{{ $title }}</p>
        @if ($description)
            <p class="mt-1 text-sm text-(--ds-text-muted)">{{ $description }}</p>
        @endif
        @if (trim($slot) !== '')
            <div class="mt-4 flex justify-center">{{ $slot }}</div>
        @endif
    </div>
</div>
