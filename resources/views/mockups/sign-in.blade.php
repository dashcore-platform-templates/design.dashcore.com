{{-- Two-panel sign-in from 02. Left, the brand and the generated copy. Right, the form. --}}
@php
    $copy = $design->decisions['copy'] ?? [];
    $name = $design->name();
    $second = $design->pairing()['second'];
    $headlineFont = $second['kind'] !== 'mono' ? 'font-second' : '';
    $upper = $second['condensed'] ? 'uppercase tracking-[0.02em]' : '';
    $input = 'block h-(--ds-control) w-full rounded-(--radius-lg) border border-(--ds-border-strong) bg-(--ds-surface) px-3 text-(--ds-text) text-[length:var(--ds-prose)] placeholder:text-(--ds-text-muted)';
@endphp
<div class="grid min-h-[720px] lg:grid-cols-5">
    <section class="relative flex flex-col justify-between bg-(--ds-sunken) p-(--ds-gutter) lg:col-span-2 dark:bg-(--ds-surface)">
        <div class="flex items-center gap-2.5">
            <span class="grid size-8 place-items-center rounded-(--radius-sm) bg-(--ds-accent) text-sm font-medium text-(--ds-on-accent)">{{ mb_substr($name, 0, 1) }}</span>
            <span class="text-sm font-medium text-(--ds-text-strong)">{{ $name }}</span>
        </div>
        <div class="my-16">
            @if (! empty($copy['eyebrow']))
                <p class="{{ $eyebrowClass ?? ($motif === 'eyebrow' ? 'ds-eyebrow' : 'ds-label') }} mb-3">{{ $copy['eyebrow'] }}</p>
            @endif
            <h1 class="text-4xl font-semibold leading-[1.1] text-(--ds-text-strong) {{ $headlineFont }} {{ $upper }}">{{ $copy['headline'] ?? 'Welcome back.' }}</h1>
            {{-- 12: below lg the brand panel carries the wordmark and headline
                 only. A phone sign-in is a form, and everything above it is delay. --}}
            <p class="mt-4 hidden max-w-prose text-[length:var(--ds-prose)] text-(--ds-text-muted) lg:block">{{ $copy['tagline'] ?? '' }}</p>
        </div>
        <p class="hidden text-xs text-(--ds-text-muted) lg:block">{{ $copy['footer'] ?? '© '.date('Y').' '.$name }}</p>
    </section>

    <section class="flex items-center justify-center bg-(--ds-page) p-(--ds-gutter) lg:col-span-3">
        <form class="w-full max-w-sm space-y-(--ds-field-gap)">
            <div>
                <h2 class="text-lg font-semibold leading-tight text-(--ds-text-strong)">Sign in</h2>
                <p class="mt-1 text-sm text-(--ds-text-muted)">Use the email your administrator invited.</p>
            </div>
            <label class="block">
                <span class="ds-label block text-(--ds-text)">Email</span>
                <input class="{{ $input }} mt-(--ds-label-gap)" type="email" placeholder="{{ 'you@'.str($name)->slug()->limit(12, '').'.com' }}">
            </label>
            <label class="block">
                <span class="flex items-center justify-between"><span class="ds-label text-(--ds-text)">Password</span><span class="text-xs text-(--ds-accent-content)">Forgot?</span></span>
                <input class="{{ $input }} mt-(--ds-label-gap)" type="password" value="password">
            </label>
            <label class="flex items-center gap-2 text-sm text-(--ds-text)">
                <span class="grid size-4 place-items-center rounded-(--radius-sm) bg-(--ds-accent)"><flux:icon name="check" variant="micro" class="size-3 text-(--ds-on-accent)" /></span>
                Keep me signed in
            </label>
            <button type="button" class="inline-flex h-(--ds-control) w-full items-center justify-center rounded-(--radius-lg) bg-(--ds-accent) text-sm font-medium text-(--ds-on-accent)">Sign in</button>
            <p class="text-center text-xs text-(--ds-text-muted)">Trouble signing in? <span class="text-(--ds-accent-content)">Ask your administrator</span></p>
        </form>
    </section>
</div>
