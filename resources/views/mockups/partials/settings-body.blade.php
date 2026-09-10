@php
    $cardClass = $motif === 'hairline' ? 'bg-(--ds-surface) border-y border-(--ds-border)' : 'rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface)';
    $input = 'block h-(--ds-control) w-full rounded-(--radius-lg) border border-(--ds-border-strong) bg-(--ds-surface) px-3 text-(--ds-text) text-[length:var(--ds-prose)] placeholder:text-(--ds-text-muted)';
@endphp

@include('mockups.partials.page-header', ['title' => 'Workspace settings', 'description' => 'Names, hours and who gets notified.', 'eyebrow' => null, 'motif' => $motif, 'eyebrowClass' => $eyebrowClass, 'design' => $design, 'slot' => new \Illuminate\Support\HtmlString('
    <button class="inline-flex h-(--ds-control) items-center gap-2 rounded-(--radius-lg) bg-(--ds-accent) px-3 text-sm font-medium text-(--ds-on-accent)">Save changes</button>
')])

<div class="grid gap-(--ds-section) px-(--ds-gutter) pb-(--ds-section) lg:grid-cols-12 {{ $motif === 'hairline' ? 'pt-(--ds-section)' : '' }}">
    {{-- Form spans 8, notes span 4 (02, 06). Fieldsets group related fields. --}}
    <div class="space-y-(--ds-section) lg:col-span-8">
        <fieldset class="{{ $cardClass }} p-(--ds-card)">
            @if ($motif === 'eyebrow')
                <p class="{{ $eyebrowClass }} mb-4">Workspace</p>
            @else
                <h2 class="mb-4 text-lg font-semibold leading-tight text-(--ds-text-strong)">Workspace</h2>
            @endif
            <div class="grid gap-(--ds-field-gap) sm:grid-cols-2">
                <label class="block">
                    <span class="ds-label block text-(--ds-text)">Workspace name</span>
                    <input class="{{ $input }} mt-(--ds-label-gap)" value="{{ $design->name() }}">
                </label>
                <label class="block">
                    <span class="ds-label block text-(--ds-text)">Contact phone</span>
                    <input class="{{ $input }} mt-(--ds-label-gap)" value="(555) 014-2200">
                </label>
                <label class="block sm:col-span-2">
                    <span class="ds-label block text-(--ds-text)">Notification email</span>
                    <span class="mt-0.5 block text-xs text-(--ds-text-muted)">Where summaries and alerts are sent from.</span>
                    <input class="{{ $input }} mt-(--ds-label-gap)" value="hello@example.com">
                </label>
                <label class="block">
                    <span class="ds-label block text-(--ds-text)">Time zone</span>
                    <select class="{{ $input }} mt-(--ds-label-gap)"><option>America/Los_Angeles</option></select>
                </label>
                <label class="block">
                    <span class="ds-label block text-(--ds-text)">Default reminder lead time</span>
                    <input class="{{ $input }} mt-(--ds-label-gap) border-(--ds-danger)" value="0">
                    <span class="mt-1 block text-xs" style="color: var(--ds-danger)">Must be at least 15 minutes.</span>
                </label>
            </div>
        </fieldset>

        <fieldset class="{{ $cardClass }} p-(--ds-card)">
            @if ($motif === 'eyebrow')
                <p class="{{ $eyebrowClass }} mb-4">Notifications</p>
            @else
                <h2 class="mb-4 text-lg font-semibold leading-tight text-(--ds-text-strong)">Notifications</h2>
            @endif
            <div class="divide-y divide-(--ds-border)">
                @foreach ([['Attention alerts', 'Tell the owner when an item has waited 10 minutes.', true], ['Daily summary', 'One email at 6pm with the day\'s numbers.', true], ['Follow-up nudges', 'Remind the owner when an item goes quiet.', false]] as [$label, $desc, $on])
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-(--ds-text-strong)">{{ $label }}</p>
                            <p class="text-xs text-(--ds-text-muted)">{{ $desc }}</p>
                        </div>
                        <span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full {{ $on ? 'bg-(--ds-accent)' : 'bg-(--ds-border-strong)' }}">
                            <span class="absolute size-4 rounded-full bg-white {{ $on ? 'end-0.5' : 'start-0.5' }}"></span>
                        </span>
                    </div>
                @endforeach
            </div>
        </fieldset>
    </div>

    <aside class="lg:col-span-4">
        <div class="{{ $cardClass }} p-(--ds-card)">
            <p class="{{ $eyebrowClass }}">About these settings</p>
            <p class="mt-2 text-sm text-(--ds-text)">Changes apply to the whole workspace. Team-level settings live under each team.</p>
            <p class="mt-3 text-sm font-medium text-(--ds-accent-content)">Manage teams</p>
        </div>
    </aside>
</div>
