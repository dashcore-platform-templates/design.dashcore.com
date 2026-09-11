<?php

use App\Design\Css;
use App\Design\Design;
use App\Design\Generation\DesignGenerator;
use App\Design\Generation\GenerationFailed;
use App\Design\Handoff;
use App\Design\Neutrals;
use App\Design\Pack;
use App\Design\Pairings;
use App\Design\Resolver;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The design page. Left, the seven decisions as controls. Right, the four
 * mockups rendered live from the resolved tokens. Nothing here knows how a
 * decision becomes a colour; that is the resolver's job.
 */
new #[Layout('layouts::tool')] class extends Component
{
    public string $slug;

    public array $brief = [];

    public array $decisions = [];

    #[Url]
    public string $screen = 'dashboard';

    #[Url]
    public string $register = 'light';

    public ?array $generator = null;

    public bool $dirty = false;

    public ?string $notice = null;

    public function mount(string $slug): void
    {
        abort_unless(Design::exists($slug), 404);
        $this->load();
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'decisions.') || str_starts_with($property, 'brief.')) {
            $this->dirty = true;
            $this->notice = null;
        }
    }

    public function save(): void
    {
        $this->design()->save();
        $this->dirty = false;
        $this->notice = 'Saved to resources/designs/'.$this->slug.'.json';
    }

    public function revert(): void
    {
        $this->load();
        $this->notice = 'Reverted to the file.';
    }

    /** Run the brief through the generator again. Replaces the decisions in the session, not the file, until Save. */
    public function regenerate(DesignGenerator $generator): void
    {
        try {
            $fresh = $generator->generate($this->brief, $this->slug);
        } catch (GenerationFailed $e) {
            $this->notice = $e->getMessage();

            return;
        }

        $this->decisions = $fresh->decisions;
        $this->generator = $fresh->generator;
        $this->dirty = true;
        $this->notice = 'Regenerated with '.$fresh->generator['model'].'. Save to keep it.';
    }

    protected function load(): void
    {
        $design = Design::load($this->slug);
        $this->brief = $design->brief;
        $this->decisions = $design->decisions;
        $this->generator = $design->generator;
        $this->dirty = false;
    }

    protected function design(): Design
    {
        return new Design($this->slug, $this->brief, $this->decisions, generator: $this->generator);
    }

    public function with(): array
    {
        $design = $this->design();

        return [
            'design' => $design,
            'tokens' => $design->tokens(),
            'pairings' => Pairings::all(),
            'temperatures' => Neutrals::TEMPERATURES,
            'radii' => Resolver::RADII,
            'densities' => Resolver::DENSITIES,
            'motifs' => Resolver::MOTIFS,
            // The handoff panel. Everything in it comes from one class so the
            // panel, the walkthrough and the pack cannot say different things.
            'handoff' => [
                'prompt' => Handoff::prompt($design),
                'token' => Handoff::tokenCommand(),
                'commands' => implode(PHP_EOL, Handoff::commands(Handoff::packUrl($design))),
                'snippet' => Pack::claudeSnippet($design),
            ],
        ];
    }
}; ?>

<div class="flex min-h-screen" x-data>
    @php
        $frameId = 'mock-'.$slug;
        $title = $design->name();
    @endphp
    @push('fonts')
        <link rel="stylesheet" href="{{ $tokens['fonts']['stylesheet'] }}">
    @endpush
    @section('title', $title)

    {{-- Controls --}}
    <aside class="flex w-80 shrink-0 flex-col border-e border-(--ds-border) bg-(--ds-surface)">
        <div class="border-b border-(--ds-border) px-(--ds-card) py-4">
            <p class="ds-eyebrow mb-1">Design</p>
            <h1 class="truncate text-lg font-semibold leading-tight text-(--ds-text-strong)">{{ $title }}</h1>
            <p class="mt-1 line-clamp-3 text-xs text-(--ds-text-muted)">{{ $brief['product'] ?? '' }}</p>
        </div>

        <div class="flex-1 space-y-5 overflow-y-auto px-(--ds-card) py-4">
            {{-- Shell: a person's choice, in the brief --}}
            <flux:field>
                <flux:label>Shell <span class="ds-label ms-1.5 font-normal">brief, not generated</span></flux:label>
                <flux:radio.group wire:model.live="brief.shell" variant="segmented" size="sm">
                    <flux:radio value="sidebar" label="Sidebar" />
                    <flux:radio value="top-nav" label="Top nav" />
                </flux:radio.group>
            </flux:field>

            <flux:separator />

            <x-decision label="Accent" model="decisions.accent.why">
                <div class="flex items-center gap-2">
                    <input type="color" wire:model.live="decisions.accent.value" class="size-9 shrink-0 cursor-pointer rounded-(--radius-md) border border-(--ds-border-strong) bg-transparent p-0.5">
                    <flux:input wire:model.live.debounce.400ms="decisions.accent.value" size="sm" class="font-mono" />
                </div>
                <div class="mt-2 flex gap-1">
                    @foreach (['accent', 'accent-hover', 'accent-content', 'on-accent'] as $role)
                        <span class="h-4 flex-1 rounded-(--radius-sm) border border-(--ds-border)" style="background: {{ $tokens[$register][$role] }}" title="{{ $role }} {{ $tokens[$register][$role] }}"></span>
                    @endforeach
                </div>
            </x-decision>

            <x-decision label="Neutral temperature" model="decisions.temperature.why">
                <flux:radio.group wire:model.live="decisions.temperature.value" variant="segmented" size="sm">
                    @foreach ($temperatures as $t)
                        <flux:radio :value="$t" :label="ucfirst($t)" />
                    @endforeach
                </flux:radio.group>
                <div class="mt-2 flex gap-px overflow-hidden rounded-(--radius-sm) border border-(--ds-border)">
                    @foreach ($tokens['neutral'] as $step => $hex)
                        <span class="h-4 flex-1" style="background: {{ $hex }}" title="{{ $step }} {{ $hex }}"></span>
                    @endforeach
                </div>
            </x-decision>

            <x-decision label="Type pairing" model="decisions.pairing.why">
                <flux:select wire:model.live="decisions.pairing.value" size="sm">
                    @foreach ($pairings as $id => $p)
                        <flux:select.option :value="$id">{{ $id }}. {{ $p['sans']['family'] }} + {{ $p['second']['family'] }} ({{ $p['second']['kind'] }})</flux:select.option>
                    @endforeach
                </flux:select>
                <p class="mt-1.5 text-xs text-(--ds-text-muted)">{{ $design->pairing()['suits'] }}</p>
            </x-decision>

            <x-decision label="Radius" model="decisions.radius.why">
                <flux:radio.group wire:model.live="decisions.radius.value" variant="segmented" size="sm">
                    @foreach ($radii as $r)
                        <flux:radio :value="$r" :label="ucfirst($r)" />
                    @endforeach
                </flux:radio.group>
            </x-decision>

            <x-decision label="Density" model="decisions.density.why">
                <flux:radio.group wire:model.live="decisions.density.value" variant="segmented" size="sm">
                    @foreach ($densities as $d)
                        <flux:radio :value="$d" :label="ucfirst($d)" />
                    @endforeach
                </flux:radio.group>
            </x-decision>

            <x-decision label="Motif" model="decisions.motif.why">
                <flux:select wire:model.live="decisions.motif.value" size="sm">
                    @foreach ($motifs as $m)
                        <flux:select.option :value="$m">{{ str($m)->replace('-', ' ')->ucfirst() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </x-decision>

            <x-decision label="Sign-in copy" model="decisions.copy.why">
                <div class="space-y-2">
                    <flux:input wire:model.live.debounce.500ms="decisions.copy.eyebrow" size="sm" placeholder="Eyebrow" />
                    <flux:input wire:model.live.debounce.500ms="decisions.copy.headline" size="sm" placeholder="Headline" />
                    <flux:textarea wire:model.live.debounce.500ms="decisions.copy.tagline" rows="2" placeholder="Tagline" />
                </div>
            </x-decision>

            <flux:separator />

            <section>
                <p class="ds-label mb-1.5">Contrast, measured</p>
                @php $c = $tokens['contrast']; @endphp
                <p class="text-sm {{ $c['passes'] ? 'text-(--ds-success)' : 'text-(--ds-danger)' }}">
                    {{ $c['passes'] ? 'Every text role passes 4.5:1 in both registers.' : 'A text role fails the floor.' }}
                </p>
                <p class="mt-0.5 text-xs text-(--ds-text-muted)">Lowest: {{ $c['lowest']['pair'] }} at {{ $c['lowest']['ratio'] }}:1</p>
                @foreach ($c['notes'] as $note)
                    <p class="mt-1 text-xs text-(--ds-text-muted)">{{ $note }}</p>
                @endforeach
            </section>
        </div>

        <div class="space-y-2 border-t border-(--ds-border) px-(--ds-card) py-3">
            @if ($notice)
                <p class="text-xs text-(--ds-text-muted)">{{ $notice }}</p>
            @endif
            <div class="flex gap-2">
                <flux:button wire:click="save" variant="primary" size="sm" :disabled="! $dirty" class="flex-1">Save</flux:button>
                <flux:button wire:click="revert" size="sm" :disabled="! $dirty">Revert</flux:button>
            </div>
            <flux:button wire:click="regenerate" wire:confirm="Run the brief through the generator again? Unsaved changes here will be replaced." size="sm" icon="sparkles" class="w-full">
                <span wire:loading.remove wire:target="regenerate">Regenerate from brief</span>
                <span wire:loading wire:target="regenerate">Choosing again…</span>
            </flux:button>
            <flux:modal.trigger name="use-design">
                <flux:button size="sm" icon="command-line" class="w-full">Use this design</flux:button>
            </flux:modal.trigger>
            @if ($dirty)
                <p class="text-xs text-(--ds-warning)">Unsaved changes. The pack is built from the saved file.</p>
            @endif
        </div>
    </aside>

    {{-- The handoff: one prompt for the agent, the by-hand path folded away --}}
    <flux:modal name="use-design" class="md:w-[44rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Use this design</flux:heading>
                <flux:text class="mt-1">Paste the prompt into Claude Code opened in the target repo. The agent installs the adapter, applies {{ $title }}, builds, commits, and if the app already has screens, migrates them one commit at a time. You review the commits.</flux:text>
            </div>

            <x-copy-block label="Prompt for the agent" :text="$handoff['prompt']" />

            <div>
                <x-copy-block label="Once per machine" :text="$handoff['token']" />
                <p class="mt-1.5 text-xs leading-snug text-(--ds-text-muted)">The package is private, so Composer needs its own GitHub token. The agent is never given it: if Composer cannot reach the repository, the agent stops and asks you to run this.</p>
            </div>

            <flux:accordion>
                <flux:accordion.item>
                    <flux:accordion.heading>By hand</flux:accordion.heading>
                    <flux:accordion.content>
                        <div class="space-y-4 pt-1">
                            <x-copy-block label="In the target repo" :text="$handoff['commands']" />
                            <x-copy-block label="For the repo's CLAUDE.md" :text="$handoff['snippet']" />
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:button :href="route('designs.pack', $slug)" size="sm" icon="arrow-down-tray">Download pack</flux:button>
                                <flux:button :href="route('designs.json', $slug)" size="sm" variant="ghost">JSON</flux:button>
                            </div>
                            <p class="text-xs leading-snug text-(--ds-text-muted)">The zip is the same four files, for anyone offline. Unzip it into the repo root, then <code>php artisan design:apply design/design.json</code>.</p>
                        </div>
                    </flux:accordion.content>
                </flux:accordion.item>
            </flux:accordion>
        </div>
    </flux:modal>

    {{-- Mockups --}}
    <section class="flex min-w-0 flex-1 flex-col bg-(--ds-sunken)">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-(--ds-border) bg-(--ds-surface) px-(--ds-gutter) py-3">
            <flux:radio.group wire:model.live="screen" variant="segmented" size="sm">
                <flux:radio value="sign-in" label="Sign-in" />
                <flux:radio value="shell" label="Shell" />
                <flux:radio value="dashboard" label="Dashboard" />
                <flux:radio value="settings" label="Settings" />
                <flux:radio value="components" label="Components" />
            </flux:radio.group>
            <div class="flex items-center gap-3">
                <span class="text-xs text-(--ds-text-muted)">{{ $tokens['fonts']['sans']['family'] }} + {{ $tokens['fonts']['second']['family'] }} · {{ $decisions['temperature']['value'] }} · {{ $decisions['radius']['value'] }} · {{ $decisions['density']['value'] }} · {{ $decisions['motif']['value'] }}</span>
                <flux:radio.group wire:model.live="register" variant="segmented" size="sm">
                    <flux:radio value="light" icon="sun" />
                    <flux:radio value="dark" icon="moon" />
                </flux:radio.group>
            </div>
        </div>

        <div class="flex-1 p-(--ds-gutter)">
            <style>{!! Css::scoped('#'.$frameId, $tokens) !!}</style>
            {{--
                The mockup is laid out at a fixed desktop width (1280px) and scaled to
                fit the panel, so what you see is the desktop screen, not a responsive
                collapse of it. The wrapper takes the scaled height so the page scrolls
                naturally.
            --}}
            <div class="relative"
                 x-data="{ scale: 1, height: 720, fit() { const w = $el.clientWidth || 1280; this.scale = Math.min(1, w / 1280); this.height = ($refs.frame?.offsetHeight || 720) * this.scale; } }"
                 x-init="fit(); new ResizeObserver(() => fit()).observe($el); new MutationObserver(() => fit()).observe($el, { childList: true, subtree: true })"
                 x-bind:style="`height: ${height}px`">
                <div id="{{ $frameId }}" x-ref="frame" wire:key="frame-{{ $screen }}-{{ $register }}"
                     @class(['ds-frame absolute start-0 top-0 w-[1280px] origin-top-left overflow-hidden rounded-(--radius-lg) border border-(--ds-border) shadow-lg', 'dark' => $register === 'dark'])
                     x-bind:style="`transform: scale(${scale}); font-family: var(--font-sans); color: var(--ds-text); background: var(--ds-page); color-scheme: {{ $register }};`">
                    @include('mockups.'.$screen, ['t' => $tokens, 'design' => $design, 'motif' => $decisions['motif']['value'], 'shell' => $brief['shell'] ?? 'sidebar', 'register' => $register])
                </div>
            </div>
        </div>
    </section>
</div>
