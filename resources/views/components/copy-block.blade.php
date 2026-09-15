@props(['text', 'label' => null])
{{--
    A block of text with a Copy button: the agent prompt, a command list, the
    CLAUDE snippet. Alpine does the clipboard from the rendered text, so what
    is copied is exactly what is shown and nothing goes through the server.
--}}
<div x-data="{
    copied: false,
    copy() {
        const text = this.$refs.text.innerText;
        const done = () => { this.copied = true; setTimeout(() => this.copied = false, 1500) };
        // The async clipboard needs a secure context, which a local http:// Herd site is not.
        if (window.isSecureContext && navigator.clipboard) {
            navigator.clipboard.writeText(text).then(done);
            return;
        }
        const area = document.createElement('textarea');
        area.value = text;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();
        document.execCommand('copy');
        area.remove();
        done();
    },
}">
    @if ($label)
        <p class="ds-label mb-1.5">{{ $label }}</p>
    @endif
    <div class="relative">
        <pre x-ref="text" class="overflow-x-auto whitespace-pre-wrap break-words rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-sunken) px-3 py-2.5 pe-24 font-mono text-xs leading-relaxed text-(--ds-text)">{{ $text }}</pre>
        {{-- Positioned by a wrapper: Flux's own `relative` on the button outranks an added `absolute`. --}}
        <div class="absolute end-1.5 top-1.5">
            <flux:button size="xs" variant="ghost" icon="clipboard" x-on:click="copy()"> {{-- @ds-audit-ignore the copy control inside a code block; at 40px it would be taller than the block it sits in --}}
                <span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
            </flux:button>
        </div>
    </div>
</div>
