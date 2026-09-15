{{--
    The pages from standard/11: the error pages and first run. Rare screens,
    which is why they are the last thing anybody styles and the first thing
    that gives an app away.

    None of them uses the danger colour. A red page tells somebody their data
    is gone, and a wrong address has not.
--}}
@php
    $name = $design->name();
    $initial = mb_substr($name, 0, 1);
    $second = $design->pairing()['second'];
    $titleFont = $second['kind'] !== 'mono' ? 'font-second' : '';
    $brand = '<span class="grid size-7 place-items-center rounded-(--radius-sm) bg-(--ds-accent) text-xs font-medium text-(--ds-on-accent)">'.e($initial).'</span>';
@endphp
<div class="min-h-[720px] bg-(--ds-sunken) p-(--ds-gutter)">
    <div class="mx-auto max-w-[900px] space-y-(--ds-section)">

        {{-- 404. No shell, because the page that failed may be the one that draws it. --}}
        <section>
            <p class="ds-label mb-1.5">404, and the other four take the same layout</p>
            <div class="grid min-h-[320px] place-items-center rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-page) px-(--ds-gutter) py-16">
                <div class="max-w-sm text-center">
                    <div class="mb-6 flex items-center justify-center gap-2.5">
                        {!! $brand !!}
                        <span class="text-sm font-medium text-(--ds-text-strong)">{{ $name }}</span>
                    </div>
                    <h1 class="text-2xl font-semibold leading-[1.25] text-(--ds-text-strong) {{ $titleFont }}">Nothing here</h1> {{-- @ds-audit-ignore 11 gives an error page one short heading, not a page header --}}
                    <p class="mt-2 text-sm leading-relaxed text-(--ds-text-muted)">
                        That address is wrong, or the page has been removed since you last opened it.
                    </p>
                    <div class="mt-6 flex justify-center">
                        <span class="inline-flex h-(--ds-control) items-center rounded-(--radius-lg) border border-(--ds-border-strong) bg-(--ds-surface) px-4 text-sm font-medium text-(--ds-text-strong)">Back to your pets</span>
                    </div>
                    <p class="mt-6 text-xs text-(--ds-text-muted)">404</p>
                </div>
            </div>
        </section>

        {{-- The wording changes and nothing else does. --}}
        <section>
            <p class="ds-label mb-1.5">The other four, wording only</p>
            {{-- A reference table for people reading the standard, so it scrolls
                 below md rather than stacking. 12 allows that where nobody
                 reaches the screen on a phone. --}}
            <div class="overflow-x-auto rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface)">
                <table class="w-full min-w-[34rem] text-sm">
                    <thead>
                        <tr class="border-b border-(--ds-border) text-start">
                            <th class="ds-label px-(--ds-cell-x) py-2.5 text-start font-normal">Code</th>
                            <th class="ds-label px-(--ds-cell-x) py-2.5 text-start font-normal">Heading</th>
                            <th class="ds-label px-(--ds-cell-x) py-2.5 text-start font-normal">What the sentence says</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ([
                            ['419', 'That page expired', 'They sat too long. Ask them to try again; nothing was lost.'],
                            ['429', 'Too many attempts', 'Say how long until they can try again, if the number is known.'],
                            ['500', 'Something broke', 'Ours, not theirs, and already recorded. Never the exception.'],
                            ['503', 'Back shortly', 'Planned work. Roughly how long, if it is known.'],
                        ] as [$code, $heading, $says])
                            <tr class="border-b border-(--ds-border) last:border-0">
                                <td class="px-(--ds-cell-x) py-3 font-mono text-xs tabular-nums text-(--ds-text-muted)">{{ $code }}</td>
                                <td class="px-(--ds-cell-x) py-3 font-medium text-(--ds-text-strong)">{{ $heading }}</td>
                                <td class="px-(--ds-cell-x) py-3 text-(--ds-text-muted)">{{ $says }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- First run. Not an error: the app works and there is nothing in it. --}}
        <section>
            <p class="ds-label mb-1.5">First run, which is not an error</p>
            <div class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-page) p-(--ds-card)">
                {{-- The page header, written here rather than included, because the
                     shared partial carries the shell's own padding. --}}
                <header @class(['flex flex-wrap items-end justify-between gap-4 pb-(--ds-section)', 'border-b border-(--ds-border)' => $motif === 'hairline'])>
                    <div class="min-w-0">
                        @if ($motif === 'eyebrow')
                            <p class="ds-eyebrow mb-1.5">{{ $name }}</p>
                        @endif
                        <h1 class="text-2xl font-semibold leading-[1.25] text-(--ds-text-strong) {{ $titleFont }}">Your pets</h1> {{-- @ds-audit-ignore the first-run screen is drawn here rather than composed from the component --}}
                        <p class="mt-1 text-sm text-(--ds-text-muted)">Everything {{ $name }} holds for you.</p>
                    </div>
                </header>
                <div @class(['mt-(--ds-section) grid min-h-[320px] place-items-center bg-(--ds-surface)', 'rounded-(--radius-lg) border border-(--ds-border)' => $motif !== 'hairline', 'border-y border-(--ds-border)' => $motif === 'hairline'])>
                    <div class="max-w-sm px-6 text-center">
                        <svg class="mx-auto size-6 text-(--ds-text-muted)" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z"/></svg>
                        <p class="mt-3 text-sm font-medium text-(--ds-text-strong)">No pets on your account yet</p>
                        <p class="mt-1 text-sm text-(--ds-text-muted)">The practice adds them after a first visit. Call us if one is missing.</p>
                        <div class="mt-4 flex justify-center">
                            <span class="inline-flex h-(--ds-control) items-center rounded-(--radius-lg) bg-(--ds-accent) px-4 text-sm font-medium text-(--ds-on-accent)">Call the practice</span>
                        </div>
                    </div>
                </div>
            </div>
            <p class="mt-1.5 text-xs text-(--ds-text-muted)">The shell stays, because it is the map. Never a modal on first load.</p>
        </section>

    </div>
</div>
