@props(['number' => null, 'last' => false])
{{--
    One node on a spine: a numbered circle, a rail down to the next one, and
    whatever the step says.

    A component rather than a class string in the page, because Tailwind reads
    source files for class names and does not find them inside a PHP variable.
    The first version built the rail from `before:` utilities held in an @php
    block and none of them compiled, so the spine rendered as four unconnected
    circles and the page still passed every test.

    The rail is a real element for the same reason: one less thing that has to
    survive the extractor.

    Without a number it is the outcome rather than a step, so it takes the
    success roles and a tick, and nothing hangs below it.
--}}
<li @class(['relative ps-12', 'pb-(--ds-section)' => ! $last])>
    @unless ($last)
        <span class="absolute start-4 top-8 bottom-0 w-px bg-(--ds-border)" aria-hidden="true"></span>
    @endunless

    <span @class([
        'absolute start-0 top-0 grid size-8 place-items-center rounded-full text-sm font-medium',
        'bg-(--ds-accent-soft) text-(--ds-accent-content)' => filled($number),
        'bg-(--ds-success-soft) text-(--ds-success)' => ! filled($number),
    ])>
        @if (filled($number))
            {{ $number }}
        @else
            <flux:icon name="check" class="size-4" />
        @endif
    </span>

    {{ $slot }}
</li>
