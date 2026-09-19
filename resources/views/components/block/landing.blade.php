@props(['design', 'register' => 'light'])
{{-- Everything a block draws from, derived in one place. Three callers used to
     build this by hand and one of them read the motif off a different object. --}}
@php ['t' => $t, 'motif' => $motif, 'shell' => $shell] = \App\Design\Blocks::context($design); @endphp
{{--
    The pages from standard/13: the landing page and the legal page. The
    standard used to exempt these rather than govern them, so this screen is
    the first time a public page has been drawn to a rule at all.

    The two things worth looking at are what is missing. There is nothing above
    the hero heading, and the proof section shows a frame of the product rather
    than three icon boxes in a row. Both are bans in 13, and both are what a
    generator reaches for when nobody has said otherwise.

    Marked @ds-public so it answers to 13 rather than to the app rules: the
    two headings here are heroes, not page titles. Everything else still
    applies, which is why the off-scale padding was a finding.
--}}
{{-- @ds-public --}}
@php
    $name = $design->name();
    $initial = mb_substr($name, 0, 1);
    $second = $design->pairing()['second'];
    $titleFont = $second['kind'] !== 'mono' ? 'font-second' : '';

    $decisions = $design->toArray()['decisions'] ?? [];
    $headline = $decisions['copy']['value']['headline'] ?? $decisions['copy']['headline'] ?? 'Today, in one place.';

    // The brief's own sentence about the product. 13 asks for copy that would
    // not survive swapping the product name, and this is the one string in the
    // design file that already passes that test.
    $product = $design->toArray()['brief']['product'] ?? '';
    $sentence = trim(strtok($product, '.'));
    $sentence = $sentence !== '' ? $sentence.'.' : 'What the team opens every morning.';

    $brand = '<span class="grid size-7 place-items-center rounded-(--radius-sm) bg-(--ds-accent) text-xs font-medium text-(--ds-on-accent)">'.e($initial).'</span>';
@endphp
<div class="min-h-[720px] bg-(--ds-sunken) p-(--ds-gutter)">
    <div class="mx-auto max-w-[900px] space-y-(--ds-section)">

        <section>
            <p class="ds-label mb-1.5">The landing page</p>
            <div class="overflow-hidden rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-page)">

                {{-- 1. The header. Not the app shell: four links at most and one action. --}}
                <header class="flex items-center justify-between gap-4 border-b border-(--ds-border) px-(--ds-gutter) py-4">
                    <div class="flex items-center gap-2.5">
                        {!! $brand !!}
                        <span class="text-sm font-medium text-(--ds-text-strong)">{{ $name }}</span>
                    </div>
                    {{-- 12: the nav collapses below md, the same width the app top nav does. --}}
                    <nav class="hidden items-center gap-6 text-sm text-(--ds-text-muted) md:flex">
                        <span>What it does</span>
                        <span>Pricing</span>
                        <span>Contact</span>
                    </nav>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-(--ds-control) items-center rounded-(--radius-lg) bg-(--ds-accent) px-4 text-sm font-medium text-(--ds-on-accent)">Sign in</span>
                        <span class="grid size-9 place-items-center rounded-(--radius-lg) border border-(--ds-border-strong) text-(--ds-text-muted) md:hidden">
                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                        </span>
                    </div>
                </header>

                {{-- 2. The hero. Nothing above the heading: no badge, no pill, no eyebrow. --}}
                <div class="px-(--ds-gutter) py-12 text-center md:py-16">
                    <h1 class="mx-auto max-w-2xl text-4xl font-semibold leading-[1.1] text-(--ds-text-strong) md:text-5xl {{ $titleFont }}">{{ $headline }}</h1>
                    <p class="mx-auto mt-4 max-w-xl text-lg leading-relaxed text-(--ds-text-muted)">{{ $sentence }}</p>
                    <div class="mt-8 flex justify-center">
                        <span class="inline-flex h-(--ds-control) items-center rounded-(--radius-lg) bg-(--ds-accent) px-6 text-sm font-medium text-(--ds-on-accent)">Get started</span>
                    </div>
                </div>

                {{-- 3. What it does. A frame of the real product, not an illustration
                     of an idea, and claims as sentences rather than icon boxes. --}}
                <div class="border-t border-(--ds-border) bg-(--ds-surface) px-(--ds-gutter) py-12 md:py-16">
                    <h2 class="text-3xl font-semibold leading-[1.2] text-(--ds-text-strong) {{ $titleFont }}">What it does</h2>

                    <div class="mt-8 grid gap-8 md:grid-cols-2 md:items-center">
                        {{-- The product, abstracted. 13 asks for a screenshot of the
                             thing rather than a drawing of the idea of the thing. --}}
                        <div class="overflow-hidden rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-page) shadow-sm">
                            <div class="flex items-center gap-2 border-b border-(--ds-border) bg-(--ds-sunken) px-3 py-2">
                                <span class="size-2 rounded-full bg-(--ds-border-strong)"></span>
                                <span class="size-2 rounded-full bg-(--ds-border-strong)"></span>
                                <span class="size-2 rounded-full bg-(--ds-border-strong)"></span>
                            </div>
                            <div class="space-y-3 p-4">
                                <div class="grid grid-cols-3 gap-2">
                                    @foreach (['148', '9', '32m'] as $value)
                                        <div class="rounded-(--radius-sm) border border-(--ds-border) bg-(--ds-surface) p-2">
                                            <p class="text-xs text-(--ds-text-muted)">&nbsp;</p>
                                            <p class="text-sm font-semibold tabular-nums text-(--ds-text-strong)">{{ $value }}</p>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="space-y-1.5">
                                    @foreach ([1, 2, 3, 4] as $row)
                                        <div class="flex items-center gap-2 rounded-(--radius-sm) px-2 py-1.5 {{ $row === 2 ? 'bg-(--ds-accent-soft)' : '' }}">
                                            <span class="size-4 rounded-full bg-(--ds-border)"></span>
                                            <span class="h-1.5 flex-1 rounded-full bg-(--ds-border)"></span>
                                            <span class="h-1.5 w-8 rounded-full bg-(--ds-border)"></span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            @foreach ([
                                ['One list, not four', 'A change made at the front desk is on everyone else’s screen before they look up. Nothing gets re-typed into a second system.'],
                                ['Trouble shows without being chased', 'Anything running late shows as running late, on the list, rather than in somebody’s head until they are asked.'],
                                ['It opens on today', 'No dashboard to interpret first. The screen that loads is the one with the work on it.'],
                            ] as [$claim, $detail])
                                <div>
                                    <p class="text-base font-medium text-(--ds-text-strong)">{{ $claim }}</p>
                                    <p class="mt-1 text-base leading-relaxed text-(--ds-text-muted)">{{ $detail }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- 4. The same action again, because it is the same action. --}}
                <div class="border-t border-(--ds-border) px-(--ds-gutter) py-12 text-center">
                    <h2 class="text-3xl font-semibold leading-[1.2] text-(--ds-text-strong) {{ $titleFont }}">Start with one team</h2>
                    <div class="mt-6 flex justify-center">
                        <span class="inline-flex h-(--ds-control) items-center rounded-(--radius-lg) bg-(--ds-accent) px-6 text-sm font-medium text-(--ds-on-accent)">Get started</span>
                    </div>
                </div>

                {{-- 5. The footer. Who owns it, how to reach them, where the legals are. --}}
                <footer class="flex flex-col gap-4 border-t border-(--ds-border) bg-(--ds-sunken) px-(--ds-gutter) py-6 text-sm text-(--ds-text-muted) md:flex-row md:items-center md:justify-between">
                    <span>© {{ date('Y') }} {{ $name }}</span>
                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        <span>Terms</span>
                        <span>Privacy</span>
                        <span>Status</span>
                        <span>Contact</span>
                    </div>
                </footer>
            </div>
            <p class="mt-1.5 text-xs text-(--ds-text-muted)">Nothing above the heading, no gradient, no three icon boxes. The hero is 48px and drops to 36px below md. The heading is the design’s own, and the sentence under it is the brief’s; the three claims are per product.</p>
        </section>

        {{-- The legal page: one column at prose width, no hero, no landing header. --}}
        <section>
            <p class="ds-label mb-1.5">Terms, privacy and anything else long</p>
            <div class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-page) px-(--ds-gutter) py-12">
                <div class="mx-auto max-w-prose">
                    <div class="mb-8 flex items-center gap-2.5">
                        {!! $brand !!}
                        <span class="text-sm font-medium text-(--ds-text-strong)">{{ $name }}</span>
                    </div>
                    <h1 class="text-2xl font-semibold leading-[1.25] text-(--ds-text-strong) {{ $titleFont }}">Terms of service</h1>
                    <p class="mt-1 text-sm text-(--ds-text-muted)">Last updated {{ date('j F Y') }}</p>

                    <h2 class="mt-8 text-base font-semibold text-(--ds-text-strong)">1. Who this agreement is with</h2>
                    <p class="mt-2 text-base leading-relaxed text-(--ds-text-muted)">
                        These terms are between the organisation named on the account and {{ $name }}. They cover this software and nothing else the organisation buys.
                    </p>

                    <h2 class="mt-6 text-base font-semibold text-(--ds-text-strong)">2. What the account may be used for</h2>
                    <p class="mt-2 text-base leading-relaxed text-(--ds-text-muted)">
                        One organisation, and the staff it employs. Sharing a sign-in between people is the one thing that will get an account closed, because an audit trail with two people on one name is not an audit trail.
                    </p>
                </div>
            </div>
            <p class="mt-1.5 text-xs text-(--ds-text-muted)">Prose width, body face, no hero. The brand mark and a way back is the whole header.</p>
        </section>

    </div>
</div>
