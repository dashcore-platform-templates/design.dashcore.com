@props(['label', 'why' => null, 'model' => null])
{{--
    One of the seven decisions: a label, the control, and the one-line reason.
    The reason is editable in place when a model is given, because a reason
    written for one value goes stale the moment someone picks another.
--}}
<section>
    <p class="ds-label mb-1.5">{{ $label }}</p>
    {{ $slot }}
    @if ($model)
        <label class="mt-1.5 block">
            <span class="sr-only">Why</span>
            <textarea wire:model.live.debounce.600ms="{{ $model }}" rows="2" placeholder="Why this choice, in one sentence."
                style="field-sizing: content;"
                class="block w-full resize-none overflow-hidden rounded-(--radius-sm) border border-transparent bg-transparent px-1 py-0.5 text-xs leading-snug text-(--ds-text-muted) hover:border-(--ds-border) focus:border-(--ds-border-strong) focus:text-(--ds-text) focus:outline-none"></textarea>
        </label>
    @elseif ($why)
        <p class="mt-1.5 text-xs leading-snug text-(--ds-text-muted)">{{ $why }}</p>
    @endif
</section>
