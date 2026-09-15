<?php

use App\Design\Generation\GenerationFailed;
use App\Design\Generation\DesignGenerator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * The brief form. A person fills it in; the generator chooses the seven
 * decisions; the result opens on the design page where every one of them can
 * be changed. The shell is asked here and never generated.
 */
new #[Layout('layouts::tool')] #[Title('New design')] class extends Component
{
    #[Validate('required|string|max:80')]
    public string $client = '';

    #[Validate('required|string|min:20|max:600')]
    public string $product = '';

    #[Validate('required|in:sidebar,top-nav')]
    public string $shell = 'sidebar';

    #[Validate('nullable|regex:/^#[0-9a-fA-F]{6}$/')]
    public ?string $brand_colour = null;

    #[Validate('nullable|string|max:600')]
    public ?string $notes = null;

    public ?string $error = null;

    public function generate(DesignGenerator $generator): void
    {
        $this->validate();
        $this->error = null;

        try {
            $design = $generator->generate([
                'client' => $this->client,
                'product' => $this->product,
                'shell' => $this->shell,
                'brand_colour' => $this->brand_colour,
                'notes' => $this->notes,
            ]);
        } catch (GenerationFailed $e) {
            $this->error = $e->getMessage();

            return;
        }

        $design->save();

        $this->redirectRoute('designs.show', ['slug' => $design->slug], navigate: true);
    }
}; ?>

<div class="px-(--ds-gutter) py-(--ds-section)">
    <x-ds::page-header
        eyebrow="New design"
        title="Describe the project"
        description="Two sentences are enough. The generator reads the standard and this brief, then chooses the seven decisions the standard leaves open. You can change any of them afterwards."
        class="mb-(--ds-section)"
    />

    <form wire:submit="generate" class="grid gap-(--ds-section) lg:grid-cols-12">
        <div class="space-y-(--ds-field-gap) lg:col-span-8">
            <flux:field>
                <flux:label>Client</flux:label>
                <flux:input wire:model="client" placeholder="Harbor Point Physical Therapy" />
                <flux:error name="client" />
            </flux:field>

            <flux:field>
                <flux:label>What the app does, and who uses it day to day</flux:label>
                <flux:textarea wire:model="product" rows="3" placeholder="Front-desk and clinician scheduling for a three-location PT practice. Used all day by reception and therapists." />
                <flux:error name="product" />
            </flux:field>

            <flux:field>
                <flux:label>Shell <span class="ds-label ms-1.5 font-normal">a person decides this</span></flux:label>
                <flux:description>Sidebar for tools used for hours with many destinations. Top nav for a portal opened now and then with six or fewer.</flux:description>
                {{--
                    Live, not deferred. The shell is the one field whose wrong
                    value still passes validation, so a click that does not
                    reach the server would generate a sidebar design silently.
                    Live means the server holds what is on screen.
                --}}
                <flux:radio.group wire:model.live="shell" variant="segmented">
                    <flux:radio value="sidebar" label="Sidebar" />
                    <flux:radio value="top-nav" label="Top nav" />
                </flux:radio.group>
            </flux:field>

            <div class="grid gap-(--ds-field-gap) sm:grid-cols-2">
                <flux:field>
                    <flux:label>Brand colour <span class="ds-label ms-1.5 font-normal">optional</span></flux:label>
                    <flux:description>A hex the client already owns. It becomes the accent even where it bends the hue rule.</flux:description>
                    <flux:input wire:model="brand_colour" placeholder="#b5542d" class="font-mono" /> {{-- @ds-audit-ignore a placeholder showing the shape of a hex, not a colour this page paints with --}}
                    <flux:error name="brand_colour" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Notes <span class="ds-label ms-1.5 font-normal">optional</span></flux:label>
                <flux:textarea wire:model="notes" rows="2" placeholder="Older patients see the sign-in page. Nothing that looks like a bank." />
                <flux:error name="notes" />
            </flux:field>

            @if ($error)
                <div class="flex gap-3 rounded-(--radius-lg) p-(--ds-card)" style="background: var(--ds-danger-soft);">
                    <flux:icon name="exclamation-circle" variant="outline" class="mt-0.5 size-5 shrink-0" style="color: var(--ds-danger)" />
                    <div class="text-sm">
                        <p class="font-medium" style="color: var(--ds-danger)">The generator did not answer.</p>
                        <p class="mt-0.5 text-(--ds-text)">{{ $error }}</p>
                    </div>
                </div>
            @endif

            <div class="flex items-center gap-3 pt-2">
                <flux:button type="submit" variant="primary" icon="sparkles">
                    <span wire:loading.remove wire:target="generate">Generate</span>
                    <span wire:loading wire:target="generate">Choosing the seven decisions…</span>
                </flux:button>
                <span wire:loading wire:target="generate" class="text-xs text-(--ds-text-muted)">Ten to twenty seconds. The standard is read in full every time.</span>
            </div>
        </div>

        <aside class="lg:col-span-4">
            <div class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card)">
                <p class="ds-eyebrow">What gets decided</p>
                <ul class="mt-3 space-y-2 text-sm text-(--ds-text)">
                    <li><span class="font-medium text-(--ds-text-strong)">Accent</span>, one colour for primary actions and active states.</li>
                    <li><span class="font-medium text-(--ds-text-strong)">Neutral temperature</span>, pure, cool or warm greys.</li>
                    <li><span class="font-medium text-(--ds-text-strong)">Type pairing</span>, from the approved list.</li>
                    <li><span class="font-medium text-(--ds-text-strong)">Radius</span>, sharp, soft or round.</li>
                    <li><span class="font-medium text-(--ds-text-strong)">Density</span>, comfortable or compact.</li>
                    <li><span class="font-medium text-(--ds-text-strong)">Motif</span>, one structural habit.</li>
                    <li><span class="font-medium text-(--ds-text-strong)">Sign-in copy</span>, eyebrow, headline, tagline.</li>
                </ul>
                <p class="mt-4 text-xs text-(--ds-text-muted)">Everything else, shell regions, scales, components, icons, motion and status colours, is fixed by the standard and is not up for generation.</p>
                <p class="mt-2 text-xs text-(--ds-text-muted)">Model: <span class="font-mono">{{ config('design.model') }}</span></p>
            </div>
        </aside>
    </form>
</div>
