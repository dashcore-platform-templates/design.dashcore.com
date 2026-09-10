@props(['href', 'active' => false, 'icon' => null])
<a href="{{ $href }}" wire:navigate
   @class([
       'flex h-(--ds-nav-item) items-center gap-2.5 rounded-(--radius-md) px-2 text-sm transition-colors duration-150',
       'text-(--ds-text) hover:bg-(--ds-accent-soft) hover:text-(--ds-text-strong)' => ! $active,
       'bg-(--ds-accent-soft) font-medium text-(--ds-text-strong)' => $active,
   ])>
    @if ($icon)
        <flux:icon :name="$icon" variant="outline" class="size-5 shrink-0 {{ $active ? 'text-(--ds-accent-content)' : 'text-(--ds-text-muted)' }}" />
    @endif
    <span class="truncate">{{ $slot }}</span>
</a>
