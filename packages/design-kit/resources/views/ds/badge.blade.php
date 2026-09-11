{{--
    <x-ds::badge status="success">Done</x-ds::badge>
    <x-ds::badge>Admin</x-ds::badge>

    The badge from standard/06. With one of the four statuses it takes that
    status's soft background and text. Without one it is neutral, which is what
    a category, a role, a plan, a type, or a status the four colours do not
    cover takes. Colour on a badge means status and nothing else.

    An unrecognised status is neutral rather than a colour picked at random.
    "Rejected" and "Not configured" are real states with no colour in the
    standard, and quietly painting them blue would say something untrue.

    Why this exists rather than an override of flux:badge: Flux paints its
    badges from Tailwind's palette across six steps per colour, so matching the
    standard would mean repainting green, amber, red and blue for the whole app,
    including every tint those steps are used for elsewhere. One small component
    is the smaller change.
--}}
@props(['status' => null])
@php
    $status = in_array($status, ['success', 'warning', 'danger', 'info'], true) ? $status : null;

    // Neutral sits on sunken with the muted step that 04 measures against it.
    // Plain text-muted is the 500 step and does not clear a quiet surface.
    $style = $status
        ? "background: var(--ds-{$status}-soft); color: var(--ds-{$status});"
        : 'background: var(--ds-sunken); color: var(--ds-text-muted-on-sunken);';
@endphp
<span {{ $attributes->class('inline-flex items-center rounded-(--radius-sm) px-1.5 py-0.5 text-xs font-medium whitespace-nowrap') }}
      style="{{ $style }}">
    {{ $slot }}
</span>
