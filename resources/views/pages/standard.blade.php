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
            'version' => Standard::version(),
        ];
    }
}; ?>

<div class="flex">
    <div class="min-w-0 flex-1 px-(--ds-gutter) py-(--ds-section)">
        <x-ds::page-header
            eyebrow="Reference"
            title="The Dashcore Standard"
            description="Draft zero. Files 01 to 09 are also the generator's instructions, word for word."
            class="mb-(--ds-section)"
        >
            {{-- The stamp an app compares its own copy against. Shown here so a
                 person can answer "is that site behind?" without a terminal. --}}
            <div class="text-end">
                <p class="ds-label mb-1.5">Version</p>
                <p class="font-mono text-sm text-(--ds-text-strong)">{{ $version }}</p>
                <a href="{{ route('changelog') }}" wire:navigate class="text-xs text-(--ds-accent-content) hover:underline">What changed</a>
            </div>
        </x-ds::page-header>

        <article class="ds-prose">{!! Standard::html($readme) !!}</article>

        @foreach ($sections as $section)
            <hr class="my-(--ds-section) border-(--ds-border)">
            <article id="{{ $section['slug'] }}" class="ds-prose scroll-mt-6">{!! Standard::html($section['markdown']) !!}</article>
        @endforeach
    </div>

    <nav class="sticky top-0 hidden h-screen w-56 shrink-0 overflow-y-auto border-s border-(--ds-border) px-4 py-(--ds-section) xl:block">
        <p class="ds-eyebrow mb-2">Sections</p>
        <ol class="space-y-1 text-sm">
            @foreach ($sections as $section)
                <li><a href="#{{ $section['slug'] }}" class="block truncate rounded-(--radius-sm) px-2 py-1 text-(--ds-text-muted) hover:bg-(--ds-accent-soft) hover:text-(--ds-text-strong)">{{ $section['title'] }}</a></li>
            @endforeach
        </ol>
    </nav>
</div>
