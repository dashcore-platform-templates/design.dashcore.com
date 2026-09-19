@props([
    'text',
    'label' => 'Copy',
    'done' => 'Copied',
    'size' => null,
    'variant' => 'ghost',
    'icon' => 'clipboard',
])
{{--
    A button that puts a string on the clipboard, and says so for a moment.

    The clipboard work lives here rather than in each caller, because the
    fallback below is the kind of thing that gets fixed in one copy and not the
    other. x-copy-block is the version with the text on screen beside it; this
    is the version for a prompt nobody needs to read to paste.

    The text is held in a hidden node rather than passed as a JS string, so the
    same rule holds as in the block: what is copied is a node in this document,
    and nothing goes through the server to produce it.
--}}
<div x-data="{
    copied: false,
    copy() {
        // textContent, not innerText: the node is display:none, and innerText
        // returns nothing at all for a node that is not being rendered.
        const text = this.$refs.text.textContent;
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
}" {{ $attributes }}>
    <span x-ref="text" class="hidden">{{ $text }}</span>
    <flux:button :size="$size" :variant="$variant" :icon="$icon" x-on:click="copy()">
        <span x-text="copied ? @js($done) : @js($label)">{{ $label }}</span>
    </flux:button>
</div>
