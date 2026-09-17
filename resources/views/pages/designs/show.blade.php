<?php

use App\Design\Artboard;
use App\Design\Design;
use App\Design\Generation\DesignGenerator;
use App\Design\Generation\GenerationFailed;
use App\Design\Handoff;
use App\Design\Neutrals;
use App\Design\Pack;
use App\Design\Pairings;
use App\Design\Resolver;
use App\Design\Screens;
use App\Design\Standard;
use Illuminate\Support\Facades\Auth;
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
    public string $screen = Screens::DEFAULT;

    #[Url]
    public string $register = 'light';

    /** @var array<int,string> */
    #[Url]
    public array $viewports = [];

    public ?string $generatedAt = null;

    public ?array $generator = null;

    public bool $dirty = false;

    public ?string $notice = null;

    public function mount(string $slug): void
    {
        abort_unless(Design::exists($slug), 404);
        // The screen comes off the URL and ends up in a Blade include, so it is
        // checked against the list rather than trusted.
        $this->screen = Screens::resolve($this->screen);

        // Read straight from the query rather than off the property. Livewire
        // merges a URL array into the property's default by position, so with a
        // default of desktop and mobile, ?viewports[]=tablet arrives as tablet
        // and mobile: the second default survives because nothing replaced it.
        // The property therefore defaults to empty and the default list is
        // applied here, once, only when the URL asked for nothing.
        //
        // Only an array counts as an answer. A scalar means an old link from
        // before the binding was right, where the menu wrote ?viewports=true,
        // and treating that as "none ticked" would show an empty menu over a
        // canvas drawing two frames.
        $asked = request()->query('viewports');
        $this->viewports = is_array($asked) ? Artboard::valid($asked) : Artboard::DEFAULT_SHOWN;

        $this->load();
    }

    public function updatedScreen(string $value): void
    {
        $this->screen = Screens::resolve($value);
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'decisions.') || str_starts_with($property, 'brief.')) {
            $this->dirty = true;
            $this->notice = null;
        }
    }

    /**
     * Signed in or nothing happens.
     *
     * The page is open on purpose: a kit pulls its pack from here without a
     * session, and somebody deciding between two designs should be able to
     * push the controls about. Keeping is the part that needs an account,
     * because a saved design is used by every kit that pulls it.
     */
    public function save(): void
    {
        abort_unless(Auth::check(), 403);

        $this->design()->save();
        $this->dirty = false;
        // Just "Saved." The warning that saving publishes to every kit belongs
        // beside the unsaved state, where somebody can still act on it, not in
        // a toast after the decision is already made.
        $this->notice = 'Saved.';
    }

    public function revert(): void
    {
        $this->load();
        // Not "the file". Designs have been rows in `designs` since 17 Sep 2026,
        // and a message naming a file sends somebody looking for one.
        $this->notice = 'Reverted.';
    }

    /** Run the brief through the generator again. Replaces the decisions in the session, not the file, until Save. */
    public function regenerate(DesignGenerator $generator): void
    {
        // A model call per press, on somebody else's bill.
        abort_unless(Auth::check(), 403);

        try {
            $fresh = $generator->generate($this->brief, $this->slug);
        } catch (GenerationFailed $e) {
            $this->notice = $e->getMessage();

            return;
        }

        $this->decisions = $fresh->decisions;
        $this->generatedAt = $fresh->generatedAt;
        $this->generator = $fresh->generator;
        $this->dirty = true;
        $this->notice = 'Regenerated with '.$fresh->generator['model'].'. Save to keep it.';
    }

    protected function load(): void
    {
        $design = Design::load($this->slug);
        $this->brief = $design->brief;
        $this->decisions = $design->decisions;
        $this->generatedAt = $design->generatedAt;
        $this->generator = $design->generator;
        $this->dirty = false;
    }

    protected function design(): Design
    {
        return new Design($this->slug, $this->brief, $this->decisions, generatedAt: $this->generatedAt, generator: $this->generator);
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
                'flux' => Handoff::fluxCommand(),
                'commands' => implode(PHP_EOL, Handoff::commands(Handoff::packUrl($design))),
                'snippet' => Pack::claudeSnippet($design),
                'updatePrompt' => Handoff::updatePrompt($design),
                'updateCommands' => implode(PHP_EOL, Handoff::updateCommands($design)),
                'version' => Standard::version(),
            ],
        ];
    }
}; ?>

<div class="flex min-h-screen" x-data>
    @php
        $title = $design->name();
    @endphp
    @push('fonts')
        <link rel="stylesheet" href="{{ $tokens['fonts']['stylesheet'] }}">
    @endpush
    @section('title', $title)

    {{-- Controls --}}
    <aside class="flex w-80 shrink-0 flex-col border-e border-(--ds-border) bg-(--ds-surface)">
        <div class="border-b border-(--ds-border) px-(--ds-card) py-4">
            <h1 class="truncate text-lg font-semibold leading-tight text-(--ds-text-strong)">{{ $title }}</h1> {{-- @ds-audit-ignore the control pane title on a two-pane tool screen, which has no page header region --}}
            <p class="mt-1 line-clamp-3 text-xs text-(--ds-text-muted)">{{ $brief['product'] ?? '' }}</p>
        </div>

        <div class="flex-1 space-y-6 overflow-y-auto px-(--ds-card) py-4">
            {{-- Shell: a person's choice, in the brief --}}
            <flux:field>
                <flux:label>Shell <span class="ds-label ms-1.5 font-normal">not generated</span></flux:label>
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
                    {{ $c['passes'] ? 'Every text role passes 4.5:1 in both registers.' : 'A text role is under 4.5:1.' }}
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
                <flux:button wire:click="save" variant="primary" size="sm" :disabled="! $dirty" class="flex-1">Save</flux:button> {{-- @ds-audit-ignore a control in this tool's own chrome, on a two-pane desktop screen nobody reaches on a phone; 05's floor is about touch --}}
                <flux:button wire:click="revert" size="sm" :disabled="! $dirty">Revert</flux:button> {{-- @ds-audit-ignore a control in this tool's own chrome, on a two-pane desktop screen nobody reaches on a phone; 05's floor is about touch --}}
            </div>
            <flux:button wire:click="regenerate" wire:confirm="Run the brief through the generator again? Unsaved changes here will be replaced." size="sm" icon="sparkles" class="w-full"> {{-- @ds-audit-ignore a control in this tool's own chrome, on a two-pane desktop screen nobody reaches on a phone; 05's floor is about touch --}}
                <span wire:loading.remove wire:target="regenerate">Regenerate from brief</span>
                <span wire:loading wire:target="regenerate">Choosing again…</span>
            </flux:button>
            <flux:modal.trigger name="use-design">
                <flux:button size="sm" icon="command-line" class="w-full">Use this design</flux:button> {{-- @ds-audit-ignore a control in this tool's own chrome, on a two-pane desktop screen nobody reaches on a phone; 05's floor is about touch --}}
            </flux:modal.trigger>
            @if ($dirty)
                {{-- The one place the publishing consequence is actionable, so
                     it reads as an instruction rather than as how the pack is
                     built. --}}
                <p class="text-xs text-(--ds-warning)">Unsaved changes. Save before a kit pulls this.</p>
            @endif
        </div>
    </aside>

    {{-- The handoff: one prompt for the agent, the by-hand path folded away --}}
    <flux:modal name="use-design" class="md:w-[44rem]">
        <div class="space-y-6">
            <div>
                <x-ds::section-heading level="2">Use this design</x-ds::section-heading>
                <flux:text class="mt-1">Paste it into Claude Code in the target repo. The agent sets the app up and migrates existing screens one commit at a time, for you to review.</flux:text>
            </div>

            <x-copy-block label="Agent prompt" :text="$handoff['prompt']" />

            <div>
                <x-copy-block label="GitHub token" :text="$handoff['token']" />
                {{-- The package is private and the prompt never hands the agent
                     a credential, which is why the agent stops rather than
                     solving it. The panel says the part somebody acts on. --}}
                <p class="mt-1.5 text-xs leading-snug text-(--ds-text-muted)">Once per machine. The agent stops and asks for this rather than running it.</p>
            </div>

            <flux:accordion>
                <flux:accordion.item>
                    <flux:accordion.heading>Updating</flux:accordion.heading>
                    <flux:accordion.content>
                        <div class="space-y-4 pt-1">
                            <p class="text-xs leading-snug text-(--ds-text-muted)">The standard is at <span class="font-mono text-(--ds-text-strong)">{{ $handoff['version'] }}</span>. An app whose <code>design/STANDARD.md</code> shows anything else is behind. <a href="{{ route('changelog') }}" wire:navigate class="text-(--ds-accent-content) hover:underline">What changed</a>.</p>
                            <x-copy-block label="Agent prompt" :text="$handoff['updatePrompt']" />
                            <x-copy-block label="Commands" :text="$handoff['updateCommands']" />
                        </div>
                    </flux:accordion.content>
                </flux:accordion.item>
                <flux:accordion.item>
                    <flux:accordion.heading>By hand</flux:accordion.heading>
                    <flux:accordion.content>
                        <div class="space-y-4 pt-1">
                            {{-- The label used to carry this. A label is the name
                                 of the thing, and where to run it is a sentence. --}}
                            <p class="text-xs leading-snug text-(--ds-text-muted)">In the target repo, in this order.</p>
                            <x-copy-block label="Commands" :text="$handoff['commands']" />
                            <x-copy-block label="CLAUDE.md snippet" :text="$handoff['snippet']" />
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:button :href="route('designs.pack', $slug)" size="sm" icon="arrow-down-tray">Download pack</flux:button> {{-- @ds-audit-ignore a control in this tool's own chrome, on a two-pane desktop screen nobody reaches on a phone; 05's floor is about touch --}}
                                <flux:button :href="route('designs.json', $slug)" size="sm" variant="ghost">JSON</flux:button> {{-- @ds-audit-ignore a control in this tool's own chrome, on a two-pane desktop screen nobody reaches on a phone; 05's floor is about touch --}}
                            </div>
                            <p class="text-xs leading-snug text-(--ds-text-muted)">The same files, for anyone offline. Unzip into the repo root, then <code>php artisan design:apply design/design.json</code>.</p>
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
                @foreach (Screens::ALL as $value => $label)
                    <flux:radio :value="$value" :label="$label" />
                @endforeach
            </flux:radio.group>
            <div class="flex items-center gap-3">
                <span class="text-xs text-(--ds-text-muted)">{{ $tokens['fonts']['sans']['family'] }} + {{ $tokens['fonts']['second']['family'] }} · {{ $decisions['temperature']['value'] }} · {{ $decisions['radius']['value'] }} · {{ $decisions['density']['value'] }} · {{ $decisions['motif']['value'] }}</span>

                {{-- Which frames are drawn. Behind a menu rather than on the bar
                     because it is set once and then left alone, unlike the screen
                     and the register beside it, which are pressed constantly. --}}
                <flux:dropdown position="bottom" align="end">
                    <flux:button size="sm" variant="ghost" icon="computer-desktop" icon:trailing="chevron-down">{{ count($viewports) }}</flux:button> {{-- @ds-audit-ignore a control in this tool's own chrome, on a two-pane desktop screen nobody reaches on a phone; 05's floor is about touch --}}
                    <flux:menu class="w-64">
                        <flux:menu.heading>Show viewports</flux:menu.heading>
                        {{-- The group carries the binding and each box carries a
                             value. Putting wire:model on the boxes instead binds
                             four booleans to one array property, which throws on
                             the first click and ticks every box until it does. --}}
                        <flux:menu.checkbox.group wire:model.live="viewports">
                            @foreach (Artboard::VIEWPORTS as $name => $frame)
                                <flux:menu.checkbox :value="$name">
                                    <span class="flex w-full items-center justify-between gap-4">
                                        <span>{{ $frame['label'] }}</span>
                                        <span class="text-xs tabular-nums text-(--ds-text-muted)">{{ $frame['width'] }}×{{ $frame['height'] }}</span>
                                    </span>
                                </flux:menu.checkbox>
                            @endforeach
                        </flux:menu.checkbox.group>
                        <flux:menu.separator />
                        {{-- Two to a row is visible the moment the menu closes.
                             What is not visible is the floor under unticking. --}}
                        <p class="px-2 py-1.5 text-xs leading-snug text-(--ds-text-muted)">Unticking the last one puts Desktop and Mobile back.</p>
                    </flux:menu>
                </flux:dropdown>

                <flux:radio.group wire:model.live="register" variant="segmented" size="sm">
                    <flux:radio value="light" icon="sun" />
                    <flux:radio value="dark" icon="moon" />
                </flux:radio.group>
            </div>
        </div>

        <div class="flex-1 p-(--ds-gutter)">
            {{--
                The canvas: two frames to a row, wrapping below, at whatever
                widths are ticked.

                Two are on by default rather than all four, because every extra
                frame shrinks all of them. The menu is a menu and not a switcher:
                nothing is hidden behind a choice of one, and a width that is off
                is off because somebody turned it off. Tablet was missing here
                entirely at first and 12's tablet rule shipped wrong because of
                it, which is the failure this shape is avoiding.

                Each artboard is an iframe, because only an iframe has a viewport of
                its own. A scaled div keeps measuring the browser window, so a 375px
                artboard would render the desktop layout squeezed and look checked
                when it is not.
            --}}
            @php
                $markup = view('mockups.'.$screen, ['t' => $tokens, 'design' => $design, 'motif' => $decisions['motif']['value'], 'shell' => $brief['shell'] ?? 'sidebar', 'register' => $register])->render();
                $doc = Artboard::document($markup, $tokens, $register);
                $gap = 32;
                $shown = Artboard::shown($viewports);
                $total = Artboard::gridWidth($shown, $gap);
            @endphp

            {{-- Keyed on the selection so Alpine re-initialises when it changes.
                 Without this Livewire keeps the old state and the canvas goes on
                 fitting to a total that no longer describes what is drawn. --}}
            <div wire:key="canvas-{{ implode('-', $shown) }}"
                 x-data="{
                    scale: 1,
                    zoomed: null,
                    zoomedWidth: null,
                    total: {{ $total }},
                    fit() {
                        const available = $el.clientWidth || this.total;
                        this.scale = Math.min(1, available / (this.zoomedWidth ?? this.total));
                    },
                    zoom(name, width) {
                        const off = this.zoomed === name;
                        this.zoomed = off ? null : name;
                        this.zoomedWidth = off ? null : width;
                        this.fit();
                    },
                 }"
                 x-init="fit(); new ResizeObserver(() => fit()).observe($el)">
                {{-- Three widths do not fit at full size, so say what you are
                     looking at. A reduced view that does not admit it is how
                     somebody measures a gap off the screen and gets it wrong. --}}
                <p class="mb-3 text-xs text-(--ds-text-muted)"
                   x-show="scale < 0.999"
                   x-text="`Fitted to ${Math.round(scale * 100)}%, click a width to see it at full size`"></p>
                {{-- Two columns, filled in order, so a third frame wraps under
                     the first rather than running off the side. Cells size to
                     their frame, because a 390px frame beside a 1440px one in
                     an even grid is mostly empty space. --}}
                <div class="grid origin-top-left grid-cols-[max-content_max-content] items-start gap-8" x-bind:style="`transform: scale(${scale})`">
                    @foreach ($shown as $name)
                        @php $frame = Artboard::VIEWPORTS[$name]; @endphp
                        <div x-show="zoomed === null || zoomed === '{{ $name }}'"
                             @class(['shrink-0', 'col-span-2' => count($shown) === 1])
                             style="width: {{ $frame['width'] }}px">
                            <button type="button" x-on:click="zoom('{{ $name }}', {{ $frame['width'] }})" class="mb-2 flex w-full items-center justify-between gap-4 text-start">
                                <span class="ds-label">{{ $frame['label'] }}</span>
                                <span class="text-xs tabular-nums text-(--ds-text-muted)" x-text="zoomed === '{{ $name }}' ? 'Back to the rest' : '{{ $frame['width'] }}×{{ $frame['height'] }}'">{{ $frame['width'] }}×{{ $frame['height'] }}</span>
                            </button>
                            {{-- Keyed on what changes the document. A token change rewrites
                                 srcdoc as well, which reloads the frame, but the accent
                                 input is debounced so that happens at most twice a second. --}}
                            <iframe wire:key="artboard-{{ $name }}-{{ $screen }}-{{ $register }}"
                                    title="{{ $frame['label'] }}, {{ $frame['width'] }} by {{ $frame['height'] }} pixels"
                                    srcdoc="{{ $doc }}"
                                    class="block w-full rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-page) shadow-lg"
                                    style="height: {{ $frame['height'] }}px"></iframe>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</div>
