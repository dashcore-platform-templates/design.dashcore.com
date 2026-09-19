@props(['text', 'label' => null])
{{--
    A block of text with a Copy button: the agent prompt, a command list, the
    CLAUDE snippet. The same string is rendered here and handed to the button,
    so what is copied is exactly what is shown and nothing goes through the
    server. x-copy-button owns the clipboard, including the fallback for a
    page that is not on a secure origin.
--}}
<div>
    @if ($label)
        <p class="ds-label mb-1.5">{{ $label }}</p>
    @endif
    <div class="relative">
        <pre class="overflow-x-auto whitespace-pre-wrap break-words rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-sunken) px-3 py-2.5 pe-24 font-mono text-xs leading-relaxed text-(--ds-text)">{{ $text }}</pre>
        {{-- Positioned by a wrapper: Flux's own `relative` on the button outranks an added `absolute`. --}}
        <x-copy-button :text="$text" size="xs" class="absolute end-1.5 top-1.5" />
    </div>
</div>
