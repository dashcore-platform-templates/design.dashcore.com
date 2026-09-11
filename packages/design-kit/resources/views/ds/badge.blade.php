{{--
    <x-ds::badge status="success">Done</x-ds::badge>

    The status badge from standard/06. Reads the --ds-* status roles, so it is
    the standard's green rather than Tailwind's.

    Why this exists rather than an override of flux:badge: Flux paints its
    badges from Tailwind's palette across six steps per colour, so matching the
    standard would mean repainting green, amber, red and blue for the whole app,
    including every tint those steps are used for elsewhere. One small component
    is the smaller change. Use it for every status badge; flux:badge is not used
    for status.
--}}
@props(['status' => 'info'])
@php
    $status = in_array($status, ['success', 'warning', 'danger', 'info'], true) ? $status : 'info';
@endphp
<span {{ $attributes->class('inline-flex items-center rounded-(--radius-sm) px-1.5 py-0.5 text-xs font-medium whitespace-nowrap') }}
      style="background: var(--ds-{{ $status }}-soft); color: var(--ds-{{ $status }});">
    {{ $slot }}
</span>
