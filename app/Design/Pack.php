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

        $zip->addFromString('design/design.json', $design->toJson());
        $zip->addFromString('design/STANDARD.md', self::standardFile());
        $zip->addFromString('design/CLAUDE.snippet.md', self::claudeSnippet($design));
        $zip->addFromString('design/MIGRATION.md', self::migration($design));
        $zip->close();

        return $path;
    }

    public static function filename(Design $design): string
    {
        return "{$design->slug}-design-pack.zip";
    }

    public static function standardFile(): string
    {
        return "# The Dashcore Standard\n\nExported from design.dashcore.com. Files 01 to 09 of the standard, the same text the generator was given. Read alongside design.json.\n\n---\n\n".Standard::prompt();
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

        Apply or re-apply the tokens with `php artisan design:apply design/design.json`. Do not edit the generated block in `resources/css/app.css` by hand.
        MD;
    }

    public static function migration(Design $design): string
    {
        $motif = $design->decision('motif');

        return <<<MD
        # Bringing an existing app onto the standard

        The artisan command handles the retheme. Everything below needs a person or an agent, because it is judgment, not substitution. Work through it once, commit as you go.

        ## Layout

        - [ ] Replace any dashboard built as equal boxes in a row with regions named by what they do: a stat row, a table, a form with notes. Three equal columns only when there are exactly three peers of equal weight.
        - [ ] Every main view starts with the page header: optional eyebrow, title, one-line description, actions on the right. Replace ad hoc headings.
        - [ ] The sidebar holds navigation and identity only. Move search, stats or content out of it.
        - [ ] Content is padded by the density gutter and laid out on the section gap. Remove one-off spacing values that are not on the 4, 8, 12, 16, 24, 32, 48, 64 scale.

        ## Colour

        - [ ] Lift every hardcoded colour in custom CSS or inline classes onto a role: page, surface, sunken, border, text, text-muted, accent, or a status.
        - [ ] The accent appears once per row at most: primary button, active nav, links, focus, selection. Remove it from backgrounds, headings and body text.
        - [ ] Status colours mark status only. A green button is not a primary button.
        - [ ] Muted text in dark mode uses the 400 step, not 500. Flux's default fails contrast here; the command overrides it, but custom CSS may not follow.

        ## Type

        - [ ] Two faces only: the body face and the second face. Remove any third.
        - [ ] Weights 400, 500, 600. Replace bold 700.
        - [ ] Nothing below 12px. Table heads and badges included.
        - [ ] Numbers in tables and stat tiles are tabular and right aligned.

        ## Components

        - [ ] Adopt the three custom components: page header, stat tile, empty state. Delete local copies.
        - [ ] Four button variants only: primary, default, ghost, danger. One primary per region.
        - [ ] Badges for status only, soft style. Remove badges used for categories or counts.
        - [ ] Every list has a loading, empty and error state. A blank region is never a state.

        ## Motif

        - [ ] This design's motif is `{$motif}`. Apply it everywhere it lives (nav active state, stat row, table selection, page header border) and remove traces of any other motif.

        ## Icons and motion

        - [ ] Heroicons only. Replace Lucide names and delete the four Lucide icon files the starter kit ships.
        - [ ] Remove scroll fades, hover opacity fades, pulsing dots, glowing borders and count-up numbers. Hover changes colour, border or underline, in 150ms.
        - [ ] `prefers-reduced-motion` turns every duration to zero.

        ## Copy

        - [ ] Headings say what a thing is. Empty states say what to do. No buzzwords, no emoji in UI text.
        MD;
    }
}
