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
                    <span class="grid size-6 place-items-center rounded-(--radius-sm) bg-(--ds-accent) font-second text-xs font-medium text-(--ds-on-accent)">D</span>
                    <span class="text-sm font-medium text-(--ds-text-strong)">Dashcore Design</span>
                </a>
                <nav class="flex flex-1 flex-col gap-4 px-2 py-4 text-sm">
                    <div>
                        <p class="ds-eyebrow px-2 pb-1.5">Reference</p>
                        <x-tool-nav-item :href="route('standard')" :active="request()->routeIs('standard')" icon="book-open">The Standard</x-tool-nav-item>
                        {{--
                            How many changes this browser has not looked at. There
                            is no login here, so the count is per browser and lives
                            in localStorage. Every read and write is wrapped,
                            because a private window throws rather than returning
                            nothing, and a nav that explodes is worse than a nav
                            with no badge. The marker itself is the one 04 defines:
                            accent fill, on-accent text, never red.
                        --}}
                        <div x-data="{
                            total: @js(\App\Design\Standard::changelogCount()),
                            unseen: 0,
                            key: 'ds-changelog-seen',
                            init() {
                                this.sync();
                                document.addEventListener('livewire:navigated', () => this.sync());
                            },
                            sync() {
                                if (window.location.pathname === @js(route('changelog', absolute: false))) {
                                    this.write(this.total);
                                    this.unseen = 0;
                                    return;
                                }
                                this.unseen = Math.max(0, this.total - this.read());
                            },
                            read() {
                                // On failure claim everything is read, so a browser
                                // that blocks storage shows no badge rather than a
                                // permanent one it can never clear.
                                try { return parseInt(localStorage.getItem(this.key) || '0', 10) || 0; } catch (e) { return this.total; }
                            },
                            write(value) {
                                try { localStorage.setItem(this.key, String(value)); } catch (e) {}
                            },
                        }">
                            <x-tool-nav-item :href="route('changelog')" :active="request()->routeIs('changelog')" icon="sparkles">
                                What changed
                                <x-slot:marker>
                                    <span x-cloak x-show="unseen > 0" x-text="unseen"
                                          class="ms-auto inline-flex min-w-5 shrink-0 items-center justify-center rounded-(--radius-sm) bg-(--ds-accent) px-1.5 py-0.5 text-xs font-medium tabular-nums text-(--ds-on-accent)"></span>
                                </x-slot:marker>
                            </x-tool-nav-item>
                        </div>
                    </div>
                    <div>
                        <p class="ds-eyebrow px-2 pb-1.5">Designs</p>
                        @foreach (\App\Design\Design::all() as $design)
                            <x-tool-nav-item :href="route('designs.show', $design->slug)" :active="request()->routeIs('designs.show') && request()->route('slug') === $design->slug" icon="swatch">{{ $design->name() }}</x-tool-nav-item>
                        @endforeach
                        <x-tool-nav-item :href="route('designs.index')" :active="request()->routeIs('designs.index')" icon="squares-2x2">All designs</x-tool-nav-item>
                        <x-tool-nav-item :href="route('designs.create')" :active="request()->routeIs('designs.create')" icon="plus">New design</x-tool-nav-item>
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
