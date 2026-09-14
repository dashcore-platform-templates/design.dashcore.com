<?php

use App\Design\Standard;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::tool')] #[Title('The Standard')] class extends Component
{
    public function with(): array
    {
        return [
            'readme' => Standard::readme(),
            'sections' => Standard::sections(),
            'changelog' => Standard::changelog(),
            'version' => Standard::version(),
        ];
    }
}; ?>

<div class="flex">
    <div class="min-w-0 flex-1 px-(--ds-gutter) py-(--ds-section)">
        <header class="mb-(--ds-section) flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="ds-eyebrow mb-1.5">Reference</p>
                <h1 class="text-2xl font-semibold leading-tight text-(--ds-text-strong)">The Dashcore Standard</h1>
                <p class="mt-1 text-sm text-(--ds-text-muted)">Draft zero. Files 01 to 09 are also the generator's instructions, word for word.</p>
            </div>
            {{-- The stamp an app compares its own copy against. Shown here so a
                 person can answer "is that site behind?" without a terminal. --}}
            <div class="text-end">
                <p class="ds-label mb-1.5">Version</p>
                <p class="font-mono text-sm text-(--ds-text-strong)">{{ $version }}</p>
                <a href="#changelog" class="text-xs text-(--ds-accent-content) hover:underline">What changed</a>
            </div>
        </header>

        <article class="ds-prose">{!! Standard::html($readme) !!}</article>

        @foreach ($sections as $section)
            <hr class="my-(--ds-section) border-(--ds-border)">
            <article id="{{ $section['slug'] }}" class="ds-prose scroll-mt-6">{!! Standard::html($section['markdown']) !!}</article>
        @endforeach

        @if (filled($changelog))
            <hr class="my-(--ds-section) border-(--ds-border)">
            <article id="changelog" class="ds-prose scroll-mt-6">{!! Standard::html($changelog) !!}</article>
        @endif
    </div>

    <nav class="sticky top-0 hidden h-screen w-56 shrink-0 overflow-y-auto border-s border-(--ds-border) px-4 py-(--ds-section) xl:block">
        <p class="ds-eyebrow mb-2">Sections</p>
        <ol class="space-y-1 text-sm">
            @foreach ($sections as $section)
                <li><a href="#{{ $section['slug'] }}" class="block truncate rounded-(--radius-sm) px-2 py-1 text-(--ds-text-muted) hover:bg-(--ds-accent-soft) hover:text-(--ds-text-strong)">{{ $section['title'] }}</a></li>
            @endforeach
            @if (filled($changelog))
                <li><a href="#changelog" class="block truncate rounded-(--radius-sm) px-2 py-1 text-(--ds-text-muted) hover:bg-(--ds-accent-soft) hover:text-(--ds-text-strong)">What changed</a></li>
            @endif
        </ol>
    </nav>
</div>
