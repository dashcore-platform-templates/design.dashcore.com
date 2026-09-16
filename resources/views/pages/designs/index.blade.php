<?php

use App\Design\Design;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::tool')] #[Title('Designs')] class extends Component
{
    public function with(): array
    {
        return ['designs' => Design::all()];
    }
}; ?>

<div class="px-(--ds-gutter) py-(--ds-section)">
    <x-ds::page-header title="Designs" class="mb-(--ds-section)">
        <x-slot:description>One per project. Open one to change its decisions and export the pack.</x-slot:description>
        @auth
            <flux:button :href="route('designs.create')" wire:navigate variant="primary" icon="plus">New design</flux:button>
        @endauth
    </x-ds::page-header>

    <div class="grid gap-(--ds-section) md:grid-cols-2 xl:grid-cols-3">
        @foreach ($designs as $design)
            @php $t = $design->tokens(); $p = $design->pairing(); @endphp
            <a href="{{ route('designs.show', $design->slug) }}" wire:navigate class="group block overflow-hidden rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) transition-colors duration-150 hover:border-(--ds-border-strong)">
                <div class="flex h-2">
                    <span class="w-1/3" style="background: {{ $t['light']['accent'] }}"></span>
                    @foreach ([200, 400, 600, 800] as $step)
                        <span class="flex-1" style="background: {{ $t['neutral'][$step] }}"></span>
                    @endforeach
                </div>
                <div class="p-(--ds-card)">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-semibold leading-tight text-(--ds-text-strong)">{{ $design->name() }}</h2>
                            <p class="mt-1 line-clamp-2 text-sm text-(--ds-text-muted)">{{ $design->brief['product'] ?? '' }}</p>
                        </div>
                        <span class="shrink-0 rounded-(--radius-sm) px-1.5 py-0.5 text-xs font-medium {{ $t['contrast']['passes'] ? 'bg-(--ds-success-soft) text-(--ds-success)' : 'bg-(--ds-danger-soft) text-(--ds-danger)' }}">{{ $t['contrast']['passes'] ? 'Contrast ok' : 'Contrast fails' }}</span>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <div><dt class="ds-label">Shell</dt><dd class="text-(--ds-text-strong)">{{ $design->brief['shell'] ?? 'sidebar' }}</dd></div>
                        <div><dt class="ds-label">Motif</dt><dd class="text-(--ds-text-strong)">{{ $design->decision('motif') }}</dd></div>
                        <div><dt class="ds-label">Type</dt><dd class="truncate text-(--ds-text-strong)">{{ $p['sans']['family'] }} + {{ $p['second']['family'] }}</dd></div>
                        <div><dt class="ds-label">Neutral, radius, density</dt><dd class="text-(--ds-text-strong)">{{ $design->decision('temperature') }}, {{ $design->decision('radius') }}, {{ $design->decision('density') }}</dd></div>
                    </dl>
                </div>
            </a>
        @endforeach
    </div>
</div>
