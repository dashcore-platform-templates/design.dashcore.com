{{--
    The Components screen: every Flux component that renders without a user
    action, free and Pro, in the current design and register. Built from real
    Flux components so what you see is what a kit ships, not a drawing of it.
    Components that only exist after a click (modal, toast, dropdown menu,
    command palette, context menu, popover, carousel) are shown by their
    trigger and named in the last section.
--}}
@php
    $mono = $design->pairing()['second']['kind'] === 'mono' ? 'font-second' : 'font-mono';
    $eyebrowClass = $motif === 'eyebrow' ? 'ds-eyebrow' : 'ds-label';
    $section = 'space-y-4';
@endphp
<div class="min-h-[720px] p-(--ds-gutter)">
    <div class="mb-(--ds-section) flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="{{ $eyebrowClass }} mb-1.5">Components</p>
            <h1 class="text-2xl font-semibold leading-[1.25] text-(--ds-text-strong) {{ $design->pairing()['second']['kind'] !== 'mono' ? 'font-second' : '' }}">Every Flux component in this design</h1>
            <p class="mt-1 text-sm text-(--ds-text-muted)">Free and Pro. Rules are in the standard, section 06. Anything that fails contrast here fails the design.</p>
            {{-- Ten Pro components render on this screen, and most of the fleet
                 installs the free package only. Without this line somebody can
                 judge a design here and promise an app a component it cannot
                 install. Section 06 names a free stand-in for eleven of them. --}}
            <p class="mt-1 text-sm text-(--ds-text-muted)">Accordion, chart, composer, date picker, editor, file upload, pillbox, slider, tabs and timeline come from Flux Pro. The licence covers every fleet site, but a site that has not installed it yet is still on the standard, and section 06 says what it uses meanwhile.</p>
        </div>
    </div>

    <div class="grid gap-(--ds-section) lg:grid-cols-12">

        {{-- Text --}}
        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card) lg:col-span-4 {{ $section }}">
            <p class="{{ $eyebrowClass }}">Text</p>
            <flux:heading size="xl">Heading xl</flux:heading>
            <flux:heading size="lg">Heading lg</flux:heading>
            <flux:heading>Heading</flux:heading>
            <flux:subheading>Subheading, one sentence under a title.</flux:subheading>
            <flux:text>Body text at 14px. <flux:link href="#">A link</flux:link> takes accent-content and underlines on hover.</flux:text>
            <flux:text variant="subtle">Muted text, measured at 4.5:1 on this surface.</flux:text>
            <flux:separator text="Separator with text" />
            <flux:breadcrumbs>
                <flux:breadcrumbs.item href="#">Workspace</flux:breadcrumbs.item>
                <flux:breadcrumbs.item href="#">Reports</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>Quarter</flux:breadcrumbs.item>
            </flux:breadcrumbs>
        </section>

        {{-- Actions --}}
        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card) lg:col-span-4 {{ $section }}">
            <p class="{{ $eyebrowClass }}">Actions</p>
            <div class="flex flex-wrap items-center gap-2">
                <flux:button variant="primary">Primary</flux:button>
                <flux:button>Default</flux:button>
                <flux:button variant="ghost">Ghost</flux:button>
                <flux:button variant="danger">Danger</flux:button>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <flux:button size="sm" variant="primary" icon="plus">Small</flux:button>
                <flux:button size="sm" icon="arrow-down-tray">Export</flux:button>
                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" />
                <flux:button size="xs">Extra small</flux:button>
            </div>
            <flux:button.group>
                <flux:button>Day</flux:button>
                <flux:button>Week</flux:button>
                <flux:button>Month</flux:button>
            </flux:button.group>
            <div class="flex items-center gap-3">
                <flux:tooltip content="Only on icon buttons">
                    <flux:button variant="ghost" icon="information-circle" />
                </flux:tooltip>
                <flux:text variant="subtle">Tooltip on an icon button</flux:text>
            </div>
            <flux:dropdown>
                <flux:button icon-trailing="chevron-down">Dropdown menu</flux:button>
                <flux:menu>
                    <flux:menu.item icon="pencil-square">Edit</flux:menu.item>
                    <flux:menu.item icon="document-duplicate">Duplicate</flux:menu.item>
                    <flux:menu.separator />
                    <flux:menu.item icon="trash" variant="danger">Delete</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </section>

        {{-- Status --}}
        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card) lg:col-span-4 {{ $section }}">
            <p class="{{ $eyebrowClass }}">Status, fixed fleet-wide</p>
            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-(--radius-sm) px-1.5 py-0.5 text-xs font-medium" style="background: var(--ds-success-soft); color: var(--ds-success)">Done</span>
                <span class="inline-flex items-center rounded-(--radius-sm) px-1.5 py-0.5 text-xs font-medium" style="background: var(--ds-warning-soft); color: var(--ds-warning)">Needs attention</span>
                <span class="inline-flex items-center rounded-(--radius-sm) px-1.5 py-0.5 text-xs font-medium" style="background: var(--ds-danger-soft); color: var(--ds-danger)">Cancelled</span>
                <span class="inline-flex items-center rounded-(--radius-sm) px-1.5 py-0.5 text-xs font-medium" style="background: var(--ds-info-soft); color: var(--ds-info)">Scheduled</span>
                <span class="inline-flex items-center rounded-(--radius-sm) px-1.5 py-0.5 text-xs font-medium" style="background: var(--ds-sunken); color: var(--ds-text-muted-on-sunken)">Admin</span>
                <span class="inline-flex items-center rounded-(--radius-sm) px-1.5 py-0.5 text-xs font-medium" style="background: var(--ds-sunken); color: var(--ds-text-muted-on-sunken)">Rejected</span>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:badge color="green" size="sm">Flux badge</flux:badge>
                <flux:badge color="amber" size="sm">Flux badge</flux:badge>
                <flux:badge color="red" size="sm">Flux badge</flux:badge>
                <flux:badge color="blue" size="sm">Flux badge</flux:badge>
            </div>
            <flux:callout icon="exclamation-triangle" color="amber">
                <flux:callout.heading>Two items have no owner</flux:callout.heading>
                <flux:callout.text>They will slip past this week unless someone picks them up.</flux:callout.text>
            </flux:callout>
            <flux:callout icon="information-circle">
                <flux:callout.heading>Neutral callout</flux:callout.heading>
                <flux:callout.text>For a note that is not a status.</flux:callout.text>
            </flux:callout>
            <div>
                <flux:text variant="subtle" class="mb-1">Progress, accent fill on sunken</flux:text>
                <flux:progress :value="64" />
            </div>
        </section>

        {{-- Forms --}}
        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card) lg:col-span-6 {{ $section }}">
            <p class="{{ $eyebrowClass }}">Forms</p>
            <div class="grid gap-(--ds-field-gap) sm:grid-cols-2">
                <flux:field>
                    <flux:label>Input</flux:label>
                    <flux:input placeholder="Placeholder, never the only label" />
                    <flux:description>Description below the label.</flux:description>
                </flux:field>
                <flux:field>
                    <flux:label>With an error</flux:label>
                    <flux:input value="0" invalid />
                    <flux:error name="example" />
                    <flux:text class="text-xs" style="color: var(--ds-danger)">Must be at least 15 minutes.</flux:text>
                </flux:field>
                <flux:field>
                    <flux:label>Select</flux:label>
                    <flux:select placeholder="Choose a team">
                        <flux:select.option>Sales</flux:select.option>
                        <flux:select.option>Operations</flux:select.option>
                        <flux:select.option>Finance</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:label>Input group</flux:label>
                    <flux:input.group>
                        <flux:input.group.prefix>https://</flux:input.group.prefix>
                        <flux:input placeholder="example.com" />
                    </flux:input.group>
                </flux:field>
                <flux:field class="sm:col-span-2">
                    <flux:label>Textarea</flux:label>
                    <flux:textarea rows="2" placeholder="Prose at the density's prose size." />
                </flux:field>
                <flux:field class="sm:col-span-2">
                    <flux:label>Date picker <span class="ds-label ms-1.5 font-normal">Pro</span></flux:label>
                    <flux:date-picker value="2026-09-15" />
                </flux:field>
                <flux:field class="sm:col-span-2">
                    <flux:label>Pillbox <span class="ds-label ms-1.5 font-normal">Pro</span></flux:label>
                    <flux:pillbox multiple placeholder="Teams">
                        <flux:pillbox.option value="sales" selected>Sales</flux:pillbox.option>
                        <flux:pillbox.option value="ops" selected>Operations</flux:pillbox.option>
                        <flux:pillbox.option value="fin">Finance</flux:pillbox.option>
                    </flux:pillbox>
                </flux:field>
            </div>
            <div class="flex flex-wrap items-center gap-6 pt-2">
                <flux:checkbox label="Checkbox" checked />
                <flux:radio.group>
                    <flux:radio label="Radio one" checked />
                    <flux:radio label="Radio two" />
                </flux:radio.group>
                <flux:switch label="Switch" checked />
            </div>
            <flux:radio.group variant="segmented" size="sm">
                <flux:radio label="Sidebar" checked />
                <flux:radio label="Top nav" />
            </flux:radio.group>
            <div>
                <flux:text variant="subtle" class="mb-1">Slider <span class="ds-label ms-1.5 font-normal">Pro</span></flux:text>
                <flux:slider :value="40" />
            </div>
        </section>

        {{-- Data --}}
        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card) lg:col-span-6 {{ $section }}">
            <p class="{{ $eyebrowClass }}">Data</p>
            <flux:tabs variant="segmented" size="sm">
                <flux:tab selected>All</flux:tab>
                <flux:tab>Sales</flux:tab>
                <flux:tab>Operations</flux:tab>
            </flux:tabs>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Ref</flux:table.column>
                    <flux:table.column>Owner</flux:table.column>
                    <flux:table.column align="end">Time</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ([['RF-2041', 'Mara Ellison', '09:00', 'Done', 'success'], ['RF-2042', 'Devin Okafor', '09:15', 'Needs attention', 'warning'], ['RF-2043', 'Priya Natarajan', '09:30', 'Scheduled', 'info']] as [$ref, $owner, $time, $status, $tone])
                        <flux:table.row>
                            <flux:table.cell class="{{ $mono }} text-xs">{{ $ref }}</flux:table.cell>
                            <flux:table.cell variant="strong">{{ $owner }}</flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums">{{ $time }}</flux:table.cell>
                            <flux:table.cell><span class="inline-flex items-center rounded-(--radius-sm) px-1.5 py-0.5 text-xs font-medium" style="background: var(--ds-{{ $tone }}-soft); color: var(--ds-{{ $tone }})">{{ $status }}</span></flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
            <div class="flex items-center justify-between">
                <flux:avatar.group>
                    <flux:avatar initials="ME" size="sm" />
                    <flux:avatar initials="DO" size="sm" />
                    <flux:avatar initials="PN" size="sm" />
                    <flux:avatar initials="+4" size="sm" />
                </flux:avatar.group>
                <div class="flex items-center gap-2">
                    <flux:skeleton class="h-4 w-24" />
                    <flux:text variant="subtle" class="text-xs">Skeleton</flux:text>
                </div>
            </div>
            <flux:accordion>
                <flux:accordion.item>
                    <flux:accordion.heading>Accordion <span class="ds-label ms-1.5 font-normal">Pro</span></flux:accordion.heading>
                    <flux:accordion.content>Reference content only. Never hides a form's required fields.</flux:accordion.content>
                </flux:accordion.item>
                <flux:accordion.item>
                    <flux:accordion.heading>Second item</flux:accordion.heading>
                    <flux:accordion.content>Headings at the section heading size, one pixel border between items.</flux:accordion.content>
                </flux:accordion.item>
            </flux:accordion>
        </section>

        {{-- Chart and timeline --}}
        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card) lg:col-span-7 {{ $section }}">
            <p class="{{ $eyebrowClass }}">Chart <span class="ds-label ms-1.5 font-normal">Pro</span></p>
            <flux:text variant="subtle" class="text-xs">Series one is accent, series two is muted text. Axes and grid are border. Unit: items per day.</flux:text>
            <flux:chart :value="[['day' => 'Mon', 'open' => 112, 'closed' => 84], ['day' => 'Tue', 'open' => 148, 'closed' => 97], ['day' => 'Wed', 'open' => 131, 'closed' => 120], ['day' => 'Thu', 'open' => 156, 'closed' => 110], ['day' => 'Fri', 'open' => 140, 'closed' => 133]]" class="h-48">
                <flux:chart.svg>
                    <flux:chart.line field="open" class="text-(--ds-accent)" />
                    <flux:chart.line field="closed" class="text-(--ds-text-muted)" />
                    <flux:chart.axis axis="x" field="day">
                        <flux:chart.axis.tick />
                        <flux:chart.axis.line class="text-(--ds-border)" />
                    </flux:chart.axis>
                    <flux:chart.axis axis="y">
                        <flux:chart.axis.grid class="text-(--ds-border)" />
                        <flux:chart.axis.tick />
                    </flux:chart.axis>
                </flux:chart.svg>
            </flux:chart>
        </section>

        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card) lg:col-span-5 {{ $section }}">
            <p class="{{ $eyebrowClass }}">Timeline <span class="ds-label ms-1.5 font-normal">Pro</span></p>
            <flux:timeline>
                @foreach ([['Item opened', 'Mara Ellison, 09:00'], ['Assigned to Operations', 'Devin Okafor, 09:15'], ['Flagged for attention', 'System, 09:40']] as [$event, $meta])
                    <flux:timeline.item>
                        <flux:timeline.content>
                            <flux:heading size="sm">{{ $event }}</flux:heading>
                            <flux:text variant="subtle" class="tabular-nums">{{ $meta }}</flux:text>
                        </flux:timeline.content>
                    </flux:timeline.item>
                @endforeach
            </flux:timeline>
        </section>

        {{-- Editor, file upload, composer --}}
        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card) lg:col-span-6 {{ $section }}">
            <p class="{{ $eyebrowClass }}">Editor <span class="ds-label ms-1.5 font-normal">Pro</span></p>
            <flux:editor value="<p>Rich text at the density's prose size. The toolbar is ghost buttons in one row, with no font or colour controls.</p>" />
        </section>
        <section class="rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card) lg:col-span-6 {{ $section }}">
            <p class="{{ $eyebrowClass }}">File upload and composer <span class="ds-label ms-1.5 font-normal">Pro</span></p>
            <flux:file-upload>
                <flux:file-upload.dropzone heading="Drop the export here" text="CSV or XLSX up to 10 MB." />
            </flux:file-upload>
            <flux:file-item heading="items-september.csv" :size="188416" />
            <flux:composer placeholder="Write a note for the owner" />
        </section>

        {{--
            Overlays, drawn open rather than behind a trigger. A real modal or
            toast is fixed to the viewport, so from inside this scaled frame it
            would render over the tool's own chrome in the tool's own colours
            and show nothing about the design. Drawn here they can be judged.
            Shadow is the one place the standard allows it (05).
        --}}
        <section class="lg:col-span-12">
            <p class="{{ $eyebrowClass }} mb-3">Overlays, drawn open</p>
            <div class="grid gap-(--ds-section) lg:grid-cols-3">

                {{-- Modal: 32rem for a confirmation, primary last (06) --}}
                <div>
                    <p class="ds-label mb-2">Modal</p>
                    <div class="rounded-(--radius-xl) border border-(--ds-border) bg-(--ds-surface) p-(--ds-card) shadow-lg">
                        <flux:heading size="lg">Delete this item?</flux:heading>
                        <flux:text class="mt-2">It leaves the list for everyone. This cannot be undone.</flux:text>
                        <div class="mt-6 flex justify-end gap-2">
                            <flux:button size="sm" variant="ghost">Cancel</flux:button>
                            <flux:button size="sm" variant="danger">Delete</flux:button>
                        </div>
                    </div>
                </div>

                {{-- Toast: status coloured, one line, at most one action (06) --}}
                <div>
                    <p class="ds-label mb-2">Toast</p>
                    <div class="flex items-start gap-3 rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-4 shadow-lg">
                        <flux:icon name="check-circle" variant="outline" class="mt-0.5 size-5 shrink-0" style="color: var(--ds-success)" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-(--ds-text-strong)">Saved</p>
                            <p class="mt-0.5 text-xs text-(--ds-text-muted)">Your changes are live for everyone.</p>
                        </div>
                        <flux:icon name="x-mark" variant="micro" class="mt-0.5 size-4 shrink-0 text-(--ds-text-muted)" />
                    </div>
                    <div class="mt-2 flex items-start gap-3 rounded-(--radius-lg) border border-(--ds-border) bg-(--ds-surface) p-4 shadow-lg">
                        <flux:icon name="exclamation-triangle" variant="outline" class="mt-0.5 size-5 shrink-0" style="color: var(--ds-danger)" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-(--ds-text-strong)">Could not save</p>
                            <p class="mt-0.5 text-xs text-(--ds-text-muted)">The connection dropped. Nothing was lost.</p>
                        </div>
                        <span class="text-xs font-medium text-(--ds-accent-content)">Retry</span>
                    </div>
                </div>

                {{-- Command palette: one per app, results grouped under labels (06) --}}
                <div>
                    <p class="ds-label mb-2">Command palette</p>
                    <div class="overflow-hidden rounded-(--radius-xl) border border-(--ds-border) bg-(--ds-surface) shadow-lg">
                        <div class="flex items-center gap-2.5 border-b border-(--ds-border) px-4 py-3">
                            <flux:icon name="magnifying-glass" variant="outline" class="size-5 text-(--ds-text-muted)" />
                            <span class="text-sm text-(--ds-text)">rep</span>
                            <span class="ms-auto rounded-(--radius-sm) border border-(--ds-border) px-1.5 py-0.5 text-xs text-(--ds-text-muted)">esc</span>
                        </div>
                        <div class="p-2">
                            <p class="{{ $eyebrowClass }} px-2 py-1">Go to</p>
                            <div class="flex items-center gap-2.5 rounded-(--radius-md) bg-(--ds-accent-soft) px-2 py-1.5 text-sm text-(--ds-text-strong)">
                                <flux:icon name="chart-bar" variant="outline" class="size-4 text-(--ds-accent-content)" />Reports
                            </div>
                            <div class="flex items-center gap-2.5 rounded-(--radius-md) px-2 py-1.5 text-sm text-(--ds-text)">
                                <flux:icon name="clock" variant="outline" class="size-4 text-(--ds-text-muted)" />Recent activity
                            </div>
                            <p class="{{ $eyebrowClass }} px-2 pt-2 pb-1">Actions</p>
                            <div class="flex items-center gap-2.5 rounded-(--radius-md) px-2 py-1.5 text-sm text-(--ds-text)">
                                <flux:icon name="arrow-down-tray" variant="outline" class="size-4 text-(--ds-text-muted)" />Export this report
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <p class="mt-3 text-xs text-(--ds-text-muted)">Flyout, context menu, popover and carousel follow the same surface, border, radius and accent variables. Their rules are in 06 and they are checked in the kit rather than drawn here.</p>
        </section>
    </div>
</div>
