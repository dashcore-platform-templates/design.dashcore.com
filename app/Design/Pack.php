<?php

namespace App\Design;

use RuntimeException;
use ZipArchive;

/**
 * The export pack from standard/09-decisions.md: four files under design/ that
 * a target repo commits. The artisan command reads design.json; an agent coder
 * reads all four.
 */
final class Pack
{
    /** Build the zip in a temp file and return its path. */
    public static function build(Design $design): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dashcore-design-');
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not open zip for writing.');
        }

        foreach (self::files($design) as $name => $contents) {
            $zip->addFromString('design/'.$name, $contents);
        }
        $zip->close();

        return $path;
    }

    /**
     * The four files, keyed by name under design/. The zip and the manifest
     * both come from here, so a pulled pack is byte for byte the downloaded one.
     *
     * @return array<string,string>
     */
    public static function files(Design $design): array
    {
        return [
            'design.json' => $design->toJson(),
            'STANDARD.md' => self::standardFile(),
            'CHANGELOG.md' => Standard::changelog(),
            'CLAUDE.snippet.md' => self::claudeSnippet($design),
            'MIGRATION.md' => self::migration($design),
        ];
    }

    /** Must match Dashcore\DesignKit\Source::MANIFEST_SCHEMA in the package. */
    public const MANIFEST_SCHEMA = 'dashcore-design-pack/1';

    /** Must match Dashcore\DesignKit\Source::VERSION_LINE. The kit reads the stamp back off this. */
    public const VERSION_LINE = 'Standard version: ';

    /**
     * The pack as one JSON document, served at /designs/{slug}/pack.json for
     * `php artisan design:apply <slug>` to pull.
     *
     * @return array<string,mixed>
     */
    public static function manifest(Design $design, ?string $source = null): array
    {
        return array_filter([
            'schema' => self::MANIFEST_SCHEMA,
            'slug' => $design->slug,
            'name' => $design->name(),
            'standard_version' => Standard::version(),
            'source' => $source,
            'files' => self::files($design),
        ], fn ($value) => $value !== null);
    }

    public static function filename(Design $design): string
    {
        return "{$design->slug}-design-pack.zip";
    }

    public static function standardFile(): string
    {
        return "# The Dashcore Standard\n\n".self::VERSION_LINE.Standard::version()."\n\nThat stamp is the text below, hashed. If it differs from the one in your `design/STANDARD.md`, the standard moved since you last applied a design, and `design/CHANGELOG.md` says what changed.\n\nExported from design.dashcore.com. Files 01 to 09 of the standard in full, including the rules the generator does not read: component behaviour, shell geometry, motion, the mapping onto Flux. Read alongside design.json before building or changing any screen.\n\nThe text below says \"the fleet\" throughout. The fleet is every app Dashcore runs, about fifteen of them, which have to look and work the same without looking like one client's app repainted. That number is the test a rule has to pass, and it is why a rule here is not open to a project: what suits one client is a preference, and the seven decisions in design.json are where a project differs.\n\n---\n\n".Standard::full();
    }

    public static function claudeSnippet(Design $design): string
    {
        $name = $design->name();

        return <<<MD
        ## Design

        This app's visual design is decided and lives in `design/`. Before building or changing any screen:

        1. Read `design/design.json`. The `decisions` block says what was chosen for {$name} and why. The `tokens` block is the resolved values: colour roles for light and dark, fonts, radius, density.
        2. Read `design/STANDARD.md`. It fixes the shell, type scale, spacing, components and motion for every Dashcore app. Nothing in it is optional per screen.
        3. Use only the colour roles, components and the one motif the two files name. Do not add a font, a colour, a button variant or a component. If a screen seems to need one, the standard is wrong or the screen is; say so rather than invent.
        4. Text sits at 4.5:1 or better on the surface it is actually on, in both light and dark. Measure it; the tokens block carries the checked pairs.
        5. Heroicons only. Never Lucide names.
        6. Check a screen with `php artisan design:audit <file>`. It lists what in that file is off the standard and exits non-zero while anything is. Bringing an existing screen onto the design follows `design/MIGRATION.md`, one screen per commit.

        Apply or re-apply the tokens with `php artisan design:apply design/design.json`. Do not edit the generated block in `resources/css/app.css` by hand.
        MD;
    }

    /**
     * The procedure an agent follows to bring an existing app's screens onto
     * the design, with a person reviewing one commit per screen. The apply
     * command has already rethemed the app; this is everything it could not do
     * because it takes judgment.
     */
    public static function migration(Design $design): string
    {
        $name = $design->name();
        $motif = $design->decision('motif');

        return <<<MD
        # Migrating {$name} onto the standard

        `design:apply` has rethemed this app: the theme variables, fonts, sign-in layout and icons follow the design. It has not restructured the screens. They still carry their own headings, colour utilities, spacing and badges, and bringing them over is a judgment job. This file is the procedure, written for an agent working in this repo with a person reviewing each commit.

        ## Before touching a screen

        Read `design/design.json` (the `decisions` block says what was chosen and why; `tokens` is the resolved values) and `design/STANDARD.md`, sections 04 colour, 05 type and spacing, 06 components, 07 motif. This design's motif is `{$motif}`.

        One command is the map and the check. It exits non-zero while anything is off:

        ```bash
        php artisan design:audit                                   # every view
        php artisan design:audit resources/views/pages/dashboard   # one directory or one file
        php artisan design:audit --full                            # every finding, not the first few per file
        ```

        It reports what a text scan can see. Whether a screen is composed well, whether a stat row says the right four things, is judged with the screen open in a browser, in both registers.

        ## Order

        1. If the tree is not clean, commit, so every diff from here is readable.
        2. Mark the pages that are not app screens, before touching anything else. A landing page, legal documents, anything reachable signed out and composed on its own terms, sits outside the app standard. It still takes the fonts and the neutral scale from the theme, so it does not look foreign. Put `{{-- @ds-audit-ignore-file <reason> --}}` at the top of each, commit that on its own, and the audit leaves the file alone from here on, the mechanical pass included. Do this before step 3 or the mechanical pass rewrites the very pages this step exempts.
        3. `php artisan design:audit --fix`. Weights and off-scale spacing only, nothing with judgment in it. Run the app's tests and commit on its own: `Mechanical pass onto the standard`.
        4. Screens, most-used first: the dashboard, then the sidebar's items in order, then settings, then admin. One commit per screen, named `Migrate <screen> onto the standard`.

        ## Each kind of finding, and its rewrite

        **Colour off the roles.** A role flips per register, so a light class and its dark partner collapse into one. Decide first what the colour means. Red on a form error means danger; red on a heading was decoration and becomes the text role.

        ```
        text-red-600 dark:text-red-400          text-(--ds-danger)
        text-amber-600 dark:text-amber-400      text-(--ds-warning)
        text-green-600 dark:text-green-400      text-(--ds-success)
        text-sky-600 dark:text-sky-400          text-(--ds-info)
        bg-amber-50 dark:bg-amber-950           bg-(--ds-warning-soft)
        text-neutral-500 / text-stone-500       text-(--ds-text-muted)
        bg-neutral-50 / bg-gray-50              bg-(--ds-sunken)
        ```

        Secondary text sitting on a tinted row takes `text-(--ds-text-muted-on-sunken)` rather than `text-muted`, which fails the contrast floor on the danger and info tints in light. The status text itself is safe on its own tint, so a badge needs nothing special.

        The roles: `page`, `surface`, `sunken`, `border`, `border-strong`, `text-strong`, `text`, `text-muted`, `text-muted-on-sunken`, `accent`, `accent-hover`, `accent-content`, `accent-soft`, `on-accent`, and for each of `success`, `warning`, `danger`, `info` the role itself for text, `-soft` for a background, `-fill` for a solid. Status colour marks status only: badges, callouts, toasts, form errors, deltas. Not a stat value, not a heading, not a button unless it is destructive. The accent appears once per region at most. A second grey family beside the design's own, stone or gray or neutral sitting next to zinc, is the drift itself: fold it into the roles.

        **Zinc is already this design, and the audit is right not to report it.** `design:apply` redefines `--color-zinc-50` through `--color-zinc-950` as the chosen temperature's neutral ramp, and remaps `zinc-500` to the 400 step in dark. So `text-zinc-500` resolves to this design's muted grey in both registers without being touched. It is the seam the whole retheme runs through, not drift.

        Moving zinc onto the roles is still worth doing on a line you are already editing, because one role replaces a hand-typed light and dark pair and says what the colour is for:

        ```
        text-zinc-500                           text-(--ds-text-muted)
        text-zinc-900 dark:text-white           text-(--ds-text-strong)
        bg-white dark:bg-zinc-900               bg-(--ds-surface)
        bg-zinc-50 dark:bg-zinc-950             bg-(--ds-page) or bg-(--ds-sunken)
        border-zinc-200 dark:border-zinc-700    border-(--ds-border)
        ```

        That is polish, not a finding. Do not go hunting for it, and do not read a file the audit calls clean as one that still has work in it. The one case where it is more than polish is a hand-picked pair landing on a different step than the role does: the dark surface role is the 800 step, so `dark:bg-zinc-900` is a shade darker than every other surface in the app.

        **What the audit count is, and is not.** It counts what can be judged from the text of a file: colour families that are not this design's, spacing off the scale, weights, text under 12px, hardcoded hex, Flux badges, Lucide names, and page titles outside the page header. It does not count zinc, and it cannot see composition at all. A file at zero is on the standard as far as a text scan reaches, which is not the same as finished. Judge the screen, not the number.

        **Hardcoded colour.** A hex in a view or a `style=` moves to the role it means. If it is a brand identity colour the design does not carry, say so in the report rather than keeping it.

        **Page title not in the page header.** This is the only place the pairing's second face appears, so a screen that skips it looks like the old app.

        ```blade
        <x-ds::page-header title="Today" description="Everything since yesterday.">
            <flux:button variant="primary">New item</flux:button>
        </x-ds::page-header>
        ```

        Props: `title`, `description` (a prop, or `<x-slot:description>` when it holds a link), `eyebrow`. The slot is the actions, on the right. Remove the heading and subtitle it replaces.

        **Flux badge.** Every Flux badge becomes the standard badge, one per cell:

        ```blade
        <x-ds::badge status="success">Done</x-ds::badge>
        <x-ds::badge>Admin</x-ds::badge>
        ```

        With a `status` of `success`, `warning`, `danger` or `info` it takes that status's colours. Without one it is neutral, and neutral is what a category, a role, a plan, a type, or a state the four colours do not cover takes: rejected, retired, not configured. Do not reach for a colour to make one of those stand out. A count is not a badge at all, so unwrap it and leave the number as text beside its label.

        **A count marker.** An unread count on a nav item or a notification bell fills with the accent and takes `on-accent` text. It is never red, because red says something has gone wrong and a pile of unread items has not. It sits at `text-xs` like everything else.

        ```
        bg-red-600 text-white text-[10px]       bg-(--ds-accent) text-(--ds-on-accent) text-xs
        ```

        **Spacing off the scale.** The mechanical pass rewrote the utility classes. What remains is in `style=` or arbitrary values. The scale is Tailwind steps 1 2 3 4 6 8 12 16, or a density value: `--ds-gutter`, `--ds-section`, `--ds-card`, `--ds-stat`, `--ds-row`, `--ds-cell-x`, `--ds-control`, `--ds-label-gap`, `--ds-field-gap`, `--ds-nav-item`, `--ds-brand`, `--ds-prose`. `p-(--ds-card)` pads a card; `gap-(--ds-section)` separates regions.

        **Weight off the scale.** Three weights: `font-normal`, `font-medium`, `font-semibold`. The mechanical pass handles the classes; anything in `style=` is yours.

        **Text under 12px.** `text-[10px]` becomes `text-xs`. Notification counts and table heads included.

        **Lucide icon name.** `design:apply` renames these. One left means a name it did not know: use the Heroicon and list the pair in the report so the rename table grows.

        ## What the audit cannot see

        Check these with the screen open, and fix them in the same commit as the screen.

        A stat row is two to four tiles. Six equal boxes become the four that are the work, and the rest move to a quiet line of text or a side panel. Status colour goes in the delta, never in the value.

        ```blade
        <x-ds::stat-row :columns="4">
            <x-ds::stat label="Open items" value="148" delta="+12 since yesterday" tone="up" first />
            <x-ds::stat label="Needs attention" value="9" footnote="3 waiting on you" tone="warn" />
        </x-ds::stat-row>
        ```

        Stat props: `label`, `value`, `delta`, `tone` (`up`, `down`, `warn`), `footnote`, `first` (the filled tile under the filled-stat motif). Stat row: `columns` 3 or 4.

        Every list has a loading, empty and error state. A blank region is not a state.

        ```blade
        <x-ds::empty icon="inbox" title="Nothing here yet." description="Add one from the button above.">
            <flux:button variant="primary">New item</flux:button>
        </x-ds::empty>

        <x-ds::empty size="compact" icon="inbox" title="Nothing waiting." />
        ```

        The default fills a whole region and stands 320px tall. Use `size="compact"` for a panel inside a split region, where the full size pushes everything below it off the screen. Compact has no button, so a slot passed to it is dropped.

        And the rest of the composition:

        - Cards hold one thing and are never nested. Under the hairline motif there are no cards at all; regions are separated by borders.
        - The sidebar holds navigation and identity only.
        - Four button variants: primary, default, ghost, danger. One primary per region.
        - Hover changes colour, border or underline in 150ms. No opacity fades, pulsing dots, glowing borders or count-up numbers.
        - Headings say what a thing is. Empty states say what to do. No buzzwords, no emoji in UI text.

        ## Finishing a screen

        ```bash
        php artisan design:audit resources/views/pages/<screen>
        php artisan test
        ```

        Open the screen in both registers. Then commit.

        ## The report

        When every screen is done, or when stuck, report: the audit count before and after, the screens migrated, what is left and why (badges awaiting a rule, deliberate exceptions with their ignore reasons), and anything the standard has no rule for. A gap in the standard is filled in section 06 of the standard, never worked around in this app.
        MD;
    }
}
