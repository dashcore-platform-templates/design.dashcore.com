<?php

namespace App\Design;

use InvalidArgumentException;

/**
 * The approved type pairings from standard/03-type.md. Every family here was
 * checked against the Bunny Fonts list, and every one has been looked at
 * rendered before being listed.
 *
 * Ids are stable and are stored in every design file and exported pack, so a
 * retired pairing leaves a gap rather than renumbering the ones after it.
 * Adding one is an entry below plus a row in 03.
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
            7 => self::make('Hanken Grotesk', 'hanken-grotesk', 'Newsreader', 'newsreader', 'serif', 'Consulting, editorial, strategy. Newsreader at title size gives an authored feel.'),
            10 => self::make('Atkinson Hyperlegible Next', 'atkinson-hyperlegible-next', 'Spline Sans Mono', 'spline-sans-mono', 'mono', 'Healthcare, older or mixed-ability users, anything where legibility beats character.'),
        ];
    }

    /** The id a design falls back to when its own is unknown. */
    public static function defaultId(): int
    {
        return (int) array_key_first(self::all());
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
