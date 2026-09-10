@php
    // The tool wears the Dashcore default design. Its tokens are emitted on
    // :root so the chrome is the first thing built to the standard.
    $own = \App\Design\Design::load('dashcore');
    $ownTokens = $own->tokens();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ filled($title ?? null) ? $title.' · '.config('app.name') : config('app.name') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="stylesheet" href="{{ $ownTokens['fonts']['stylesheet'] }}">
        @stack('fonts')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>{!! \App\Design\Css::scoped(':root', $ownTokens) !!}</style>
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-(--ds-page) text-(--ds-text) font-sans antialiased">
        <div class="flex min-h-screen">
            <aside class="hidden w-56 shrink-0 flex-col border-e border-(--ds-border) bg-(--ds-sunken) lg:flex dark:bg-(--ds-surface)">
                <a href="{{ route('designs.index') }}" class="flex h-(--ds-brand) items-center gap-2.5 border-b border-(--ds-border) px-4">
                    <span class="grid size-6 place-items-center rounded-(--radius-sm) bg-(--ds-accent) font-second text-[11px] font-medium text-(--ds-on-accent)">D</span>
                    <span class="text-sm font-medium text-(--ds-text-strong)">Dashcore Design</span>
                </a>
                <nav class="flex flex-1 flex-col gap-4 px-2 py-4 text-sm">
                    <div>
                        <p class="ds-eyebrow px-2 pb-1.5">Reference</p>
                        <x-tool-nav-item :href="route('standard')" :active="request()->routeIs('standard')" icon="book-open">The Standard</x-tool-nav-item>
                    </div>
                    <div>
                        <p class="ds-eyebrow px-2 pb-1.5">Designs</p>
                        @foreach (\App\Design\Design::all() as $design)
                            <x-tool-nav-item :href="route('designs.show', $design->slug)" :active="request()->routeIs('designs.show') && request()->route('slug') === $design->slug" icon="swatch">{{ $design->name() }}</x-tool-nav-item>
                        @endforeach
                        <x-tool-nav-item :href="route('designs.index')" :active="request()->routeIs('designs.index')" icon="squares-2x2">All designs</x-tool-nav-item>
                    </div>
                </nav>
                <div class="border-t border-(--ds-border) px-4 py-3">
                    <flux:radio.group x-data variant="segmented" size="sm" x-model="$flux.appearance">
                        <flux:radio value="light" icon="sun" />
                        <flux:radio value="dark" icon="moon" />
                        <flux:radio value="system" icon="computer-desktop" />
                    </flux:radio.group>
                </div>
            </aside>
            <main class="min-w-0 flex-1">
                {{ $slot }}
            </main>
        </div>
        @fluxScripts
    </body>
</html>
