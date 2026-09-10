@include('mockups.partials.page-header', ['title' => 'Activity', 'description' => 'Everything that changed since yesterday, across the whole workspace.', 'eyebrow' => null, 'motif' => $motif, 'eyebrowClass' => $eyebrowClass, 'design' => $design, 'slot' => new \Illuminate\Support\HtmlString('
    <button class="inline-flex h-(--ds-control) items-center gap-2 rounded-(--radius-lg) border border-(--ds-border-strong) bg-(--ds-surface) px-3 text-sm font-medium text-(--ds-text)">Export</button>
    <button class="inline-flex h-(--ds-control) items-center gap-2 rounded-(--radius-lg) bg-(--ds-accent) px-3 text-sm font-medium text-(--ds-on-accent)">New item</button>
')])

<div class="px-(--ds-gutter) pb-(--ds-section) {{ $motif === 'hairline' ? 'pt-(--ds-section)' : '' }}">
    {{-- Empty state: one icon, what belongs here, how to add it, one default button (06). --}}
    <div @class(['grid min-h-[420px] place-items-center bg-(--ds-surface)', 'rounded-(--radius-lg) border border-(--ds-border)' => $motif !== 'hairline', 'border-y border-(--ds-border)' => $motif === 'hairline'])>
        <div class="max-w-sm text-center">
            <flux:icon name="inbox" variant="outline" class="mx-auto size-6 text-(--ds-text-muted)" />
            <p class="mt-3 text-sm font-medium text-(--ds-text-strong)">Nothing new since yesterday.</p>
            <p class="mt-1 text-sm text-(--ds-text-muted)">New items appear here as they come in. Add one from the button above, or import a list to start.</p>
            <button class="mt-4 inline-flex h-(--ds-control) items-center rounded-(--radius-lg) border border-(--ds-border-strong) bg-(--ds-surface) px-3 text-sm font-medium text-(--ds-text)">Import a list</button>
        </div>
    </div>
</div>
