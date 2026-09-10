<?php

namespace App\Design;

use InvalidArgumentException;

/**
 * The approved type pairings from standard/03-type.md. Ten for now; John cuts
 * to four to six. Every family here was checked against the Bunny Fonts list.
 */
final class Pairings
{
    private const SANS_FALLBACK = 'ui-sans-serif, system-ui, sans-serif';

    private const MONO_FALLBACK = 'ui-monospace, SFMono-Regular, Menlo, monospace';

    private const SERIF_FALLBACK = 'ui-serif, Georgia, serif';

    /** @return array<int, array<string,mixed>> */
    public static function all(): array
    {
        return [
            1 => self::make('IBM Plex Sans', 'ibm-plex-sans', 'IBM Plex Mono', 'ibm-plex-mono', 'mono', 'Operations, logistics, anything with IDs and codes in every row. Neutral and slightly technical.'),
            2 => self::make('Source Sans 3', 'source-sans-3', 'Source Serif 4', 'source-serif-4', 'serif', 'Clinics, law, accounting, professional services. Quiet and trustworthy.'),
            3 => self::make('Manrope', 'manrope', 'JetBrains Mono', 'jetbrains-mono', 'mono', 'Modern product tools, dashboards with lots of numbers. Rounder and warmer than 1.'),
            4 => self::make('Public Sans', 'public-sans', 'Fraunces', 'fraunces', 'serif', 'Hospitality, publishing, civic, anything with a warm brand. Fraunces only at title size.'),
            5 => self::make('Archivo', 'archivo', 'Archivo Narrow', 'archivo-narrow', 'display', 'Dense data, field operations, dispatch. Narrow cut for titles and stat values, uppercase allowed.', condensed: true),
            6 => self::make('Figtree', 'figtree', 'Geist Mono', 'geist-mono', 'mono', 'Friendly internal tools, HR, people-facing apps.'),
            7 => self::make('Hanken Grotesk', 'hanken-grotesk', 'Newsreader', 'newsreader', 'serif', 'Consulting, editorial, strategy. Newsreader at title size gives an authored feel.'),
            8 => self::make('Red Hat Text', 'red-hat-text', 'Red Hat Display', 'red-hat-display', 'display', 'Corporate, finance, anything that must read as safe. One family in two cuts, hard to get wrong.'),
            9 => self::make('Albert Sans', 'albert-sans', 'Bricolage Grotesque', 'bricolage-grotesque', 'display', 'Marketing-adjacent apps, agencies, brands with some personality.'),
            10 => self::make('Atkinson Hyperlegible Next', 'atkinson-hyperlegible-next', 'Spline Sans Mono', 'spline-sans-mono', 'mono', 'Healthcare, older or mixed-ability users, anything where legibility beats character.'),
        ];
    }

    /** @return array<string,mixed> */
    public static function find(int $id): array
    {
        return self::all()[$id] ?? throw new InvalidArgumentException("Unknown pairing: {$id}");
    }

    /** The Bunny Fonts stylesheet URL for a pairing, loaded at runtime by the viewer. */
    public static function stylesheetUrl(int $id): string
    {
        $p = self::find($id);
        $families = [
            $p['sans']['bunny'].':'.implode(',', $p['sans']['weights']),
            $p['second']['bunny'].':'.implode(',', $p['second']['weights']),
        ];

        return 'https://fonts.bunny.net/css?family='.implode('|', $families).'&display=swap';
    }

    /** @return array<string,mixed> */
    private static function make(string $sans, string $sansSlug, string $second, string $secondSlug, string $kind, string $suits, bool $condensed = false): array
    {
        $secondWeights = match ($kind) {
            'mono' => [400, 500],
            default => [500, 600],
        };

        $secondFallback = match ($kind) {
            'mono' => self::MONO_FALLBACK,
            'serif' => self::SERIF_FALLBACK,
            default => self::SANS_FALLBACK,
        };

        return [
            'sans' => ['family' => $sans, 'bunny' => $sansSlug, 'weights' => [400, 500, 600], 'fallback' => self::SANS_FALLBACK],
            'second' => ['family' => $second, 'bunny' => $secondSlug, 'weights' => $secondWeights, 'kind' => $kind, 'fallback' => $secondFallback, 'condensed' => $condensed],
            'suits' => $suits,
        ];
    }
}
