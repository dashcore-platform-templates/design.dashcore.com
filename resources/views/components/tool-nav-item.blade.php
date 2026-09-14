@props(['href', 'active' => false, 'icon' => null, 'count' => null])
{{--
    `count` draws the marker standard/04 defines: accent fill, on-accent text,
    text-xs. Never red, because red says something has gone wrong and a pile of
    unread items has not. Pass an Alpine expression as `countExpression` on the
    element instead when the number is only known in the browser.
--}}
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
    @if (filled($count))
        <span class="ms-auto inline-flex min-w-5 shrink-0 items-center justify-center rounded-(--radius-sm) bg-(--ds-accent) px-1.5 py-0.5 text-xs font-medium tabular-nums text-(--ds-on-accent)">{{ $count }}</span>
    @endif
    {{ $marker ?? '' }}
</a>
