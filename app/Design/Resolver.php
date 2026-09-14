<?php

namespace App\Design;

use InvalidArgumentException;

/**
 * Turns the seven decisions into the tokens layer of the JSON, exactly as
 * standard/04, 05 and 09 describe. Nothing downstream reads decisions; the
 * viewer and the adapter read only what this produces. The contrast block at
 * the end is measured on the resolved values, not assumed.
 */
final class Resolver
{
    public const RADII = ['sharp', 'soft', 'round'];

    public const DENSITIES = ['comfortable', 'compact'];

    public const MOTIFS = ['accent-bar', 'hairline', 'eyebrow', 'filled-stat'];

    private const RADIUS_STEPS = [
        'sharp' => ['sm' => 2, 'md' => 3, 'lg' => 4, 'xl' => 6, '2xl' => 8],
        'soft' => ['sm' => 4, 'md' => 6, 'lg' => 8, 'xl' => 12, '2xl' => 16],
        'round' => ['sm' => 6, 'md' => 8, 'lg' => 12, 'xl' => 16, '2xl' => 24],
    ];

    private const DENSITY_STEPS = [
        'comfortable' => ['gutter' => 32, 'section' => 32, 'card' => 24, 'stat' => 24, 'row' => 48, 'cell-x' => 16, 'control' => 40, 'label-gap' => 8, 'field-gap' => 24, 'nav-item' => 40, 'brand' => 56, 'prose' => 16],
        'compact' => ['gutter' => 24, 'section' => 24, 'card' => 16, 'stat' => 16, 'row' => 40, 'cell-x' => 12, 'control' => 36, 'label-gap' => 6, 'field-gap' => 16, 'nav-item' => 36, 'brand' => 48, 'prose' => 14],
    ];

    /** Fixed fleet-wide, from 04. Text shades pass 4.5:1 in their register; fills carry white. */
    public const STATUS = [
        'success' => ['text-light' => '#15803d', 'text-dark' => '#4ade80', 'fill' => '#15803d', 'soft-light' => '#f0fdf4', 'soft-dark' => '#052e16'],
        'warning' => ['text-light' => '#b45309', 'text-dark' => '#fbbf24', 'fill' => '#b45309', 'soft-light' => '#fffbeb', 'soft-dark' => '#451a03'],
        'danger' => ['text-light' => '#b91c1c', 'text-dark' => '#f87171', 'fill' => '#dc2626', 'soft-light' => '#fef2f2', 'soft-dark' => '#450a0a'],
        'info' => ['text-light' => '#1d4ed8', 'text-dark' => '#60a5fa', 'fill' => '#1d4ed8', 'soft-light' => '#eff6ff', 'soft-dark' => '#172554'],
    ];

    /**
     * @param  array<string,mixed>  $decisions  The decisions block, each entry {value, why}.
     * @return array<string,mixed>
     */
    public static function resolve(array $decisions): array
    {
        $value = fn (string $key) => $decisions[$key]['value'] ?? throw new InvalidArgumentException("Decision missing: {$key}");

        $temperature = $value('temperature');
        $accent = strtolower($value('accent'));
        $pairingId = (int) $value('pairing');
        $radius = $value('radius');
        $density = $value('density');

        $n = Neutrals::scale($temperature);
        $pairing = Pairings::find($pairingId);
        $notes = [];

        // Light register.
        $light = [
            'page' => '#ffffff',
            'surface' => '#ffffff',
            'sunken' => $n[50],
            'border' => $n[200],
            'border-strong' => $n[300],
            'text-strong' => $n[950],
            'text' => $n[800],
            'text-muted' => $n[500],
            'text-muted-on-sunken' => $n[600],
        ];
        $light += self::accentRoles($accent, $light['surface'], $n[950], dark: false, notes: $notes);

        // Dark register. The accent is lightened until it reads as a fill on the dark page.
        $dark = [
            'page' => $n[900],
            'surface' => $n[800],
            'sunken' => $n[950],
            'border' => $n[700],
            'border-strong' => $n[600],
            'text-strong' => $n[50],
            'text' => $n[200],
            'text-muted' => $n[400],
            'text-muted-on-sunken' => $n[400],
        ];
        $darkAccent = Color::untilContrast($accent, $dark['page'], 3.0, +1);
        if ($darkAccent !== $accent) {
            $notes[] = "Dark accent lightened from {$accent} to {$darkAccent} to read on the dark page.";
        }
        $dark += self::accentRoles($darkAccent, $dark['surface'], $n[950], dark: true, notes: $notes);

        $tokens = [
            'fonts' => [
                'sans' => $pairing['sans'],
                'second' => $pairing['second'],
                'stylesheet' => Pairings::stylesheetUrl($pairingId),
            ],
            'radius' => array_map(fn (int $px): string => "{$px}px", self::RADIUS_STEPS[$radius] ?? throw new InvalidArgumentException("Unknown radius: {$radius}")),
            'density' => array_map(fn (int $px): string => "{$px}px", self::DENSITY_STEPS[$density] ?? throw new InvalidArgumentException("Unknown density: {$density}")),
            'neutral' => $n,
            'light' => $light,
            'dark' => $dark,
            'status' => self::STATUS,
        ];

        $tokens['contrast'] = self::measure($tokens, $notes);

        return $tokens;
    }

    /**
     * Accent roles for one register. The fill is nudged away from mid-lightness
     * until white or near-black text passes on it, and accent-soft is a tint of
     * the fill over the surface.
     *
     * accent-content is moved until it passes on that tint, not on the page.
     * 04 puts the tint behind selected rows and active list items, so accent
     * text sits on it more often than it sits on anything else, and the tint is
     * the worst of the three surfaces in both registers. Targeting the page
     * left it failing at 3.40:1 in dark while every design reported passing.
     *
     * @param  array<int,string>  $notes
     * @return array<string,string>
     */
    private static function accentRoles(string $accent, string $surface, string $nearBlack, bool $dark, array &$notes): array
    {
        $fill = $accent;
        $onAccent = Color::contrast('#ffffff', $fill) >= Color::contrast($nearBlack, $fill) ? '#ffffff' : $nearBlack;

        if (Color::contrast($onAccent, $fill) < 4.5) {
            $direction = $onAccent === '#ffffff' ? -1 : +1;
            $adjusted = self::moveFillForText($fill, $onAccent, $direction);
            if ($adjusted !== $fill) {
                $notes[] = sprintf('%s accent fill moved from %s to %s so %s text passes 4.5:1 on it.', $dark ? 'Dark' : 'Light', $fill, $adjusted, $onAccent === '#ffffff' ? 'white' : 'dark');
                $fill = $adjusted;
            }
        }

        $softAlpha = $dark ? 0.16 : 0.10;
        $tint = Color::over($fill, $surface, $softAlpha);

        $content = Color::untilContrast($fill, $tint, 4.5, $dark ? +1 : -1);
        $hover = Color::shiftLightness($fill, $dark ? +6 : -6);

        return [
            'accent' => $fill,
            'accent-hover' => $hover,
            'accent-content' => $content,
            'accent-soft' => Color::withAlpha($fill, $softAlpha),
            'on-accent' => $onAccent,
        ];
    }

    private static function moveFillForText(string $fill, string $text, int $direction): string
    {
        $current = $fill;
        for ($i = 0; $i < 50; $i++) {
            if (Color::contrast($text, $current) >= 4.5) {
                return $current;
            }
            $next = Color::shiftLightness($current, 2 * $direction);
            if ($next === $current) {
                break;
            }
            $current = $next;
        }

        return $current;
    }

    /**
     * Every text role against the surface it actually sits on, both registers,
     * plus the status text shades. Principle 4: measured, not eyeballed.
     *
     * @param  array<string,mixed>  $tokens
     * @param  array<int,string>  $notes
     * @return array<string,mixed>
     */
    private static function measure(array $tokens, array $notes): array
    {
        $pairs = [];
        foreach (['light', 'dark'] as $register) {
            $r = $tokens[$register];
            $pairs["{$register}: text on page"] = [$r['text'], $r['page'], 4.5];
            $pairs["{$register}: text on surface"] = [$r['text'], $r['surface'], 4.5];
            $pairs["{$register}: text-strong on page"] = [$r['text-strong'], $r['page'], 4.5];
            $pairs["{$register}: text-muted on page"] = [$r['text-muted'], $r['page'], 4.5];
            $pairs["{$register}: text-muted on surface"] = [$r['text-muted'], $r['surface'], 4.5];
            $pairs["{$register}: text-muted-on-sunken on sunken"] = [$r['text-muted-on-sunken'], $r['sunken'], 4.5];
            $pairs["{$register}: accent-content on page"] = [$r['accent-content'], $r['page'], 4.5];
            // The tint 04 puts behind selected rows and active list items. It is
            // where accent text actually sits, and it was never measured, so
            // every design reported passing while dark sat at 3.40:1.
            $accentTint = Color::over($r['accent'], $r['surface'], $register === 'dark' ? 0.16 : 0.10);
            $pairs["{$register}: accent-content on accent-soft"] = [$r['accent-content'], $accentTint, 4.5];
            $pairs["{$register}: text-muted-on-sunken on accent-soft"] = [$r['text-muted-on-sunken'], $accentTint, 4.5];
            $pairs["{$register}: on-accent on accent"] = [$r['on-accent'], $r['accent'], 4.5];
            $pairs["{$register}: border-strong on surface"] = [$r['border-strong'], $r['surface'], 1.5];
            foreach (self::STATUS as $name => $s) {
                $soft = $s["soft-{$register}"];
                $pairs["{$register}: {$name} text on page"] = [$s["text-{$register}"], $r['page'], 4.5];
                // The badge, which is status text on its own tint.
                $pairs["{$register}: {$name} text on {$name}-soft"] = [$s["text-{$register}"], $soft, 4.5];
                // Secondary text on a tinted row. Plain text-muted fails here in
                // light, at 4.35:1 on the danger tint and 4.37:1 on the info one,
                // so 04 sends it to the same 600 step as on sunken. Measured, or
                // it goes quietly wrong again.
                $pairs["{$register}: text-muted-on-sunken on {$name}-soft"] = [$r['text-muted-on-sunken'], $soft, 4.5];
            }
        }

        $results = [];
        $lowest = null;
        $passes = true;
        foreach ($pairs as $label => [$fg, $bg, $min]) {
            $ratio = Color::contrast($fg, $bg);
            $ok = $ratio >= $min;
            // Only text pairs (4.5 floor) decide pass or fail. Border pairs are
            // reported for information: 04 gives decorative borders no floor, and
            // whether input outlines must reach 3:1 is an open question for the cut.
            if ($min >= 4.5) {
                $passes = $passes && $ok;
            }
            $results[] = ['pair' => $label, 'ratio' => $ratio, 'min' => $min, 'passes' => $ok, 'informational' => $min < 4.5];
            if ($min >= 4.5 && ($lowest === null || $ratio < $lowest['ratio'])) {
                $lowest = ['pair' => $label, 'ratio' => $ratio];
            }
        }

        // No timestamp: the block is a function of the decisions, and stamping
        // it made every export differ, so a kit pulling twice always saw a change.
        return [
            'passes' => $passes,
            'lowest' => $lowest,
            'notes' => $notes,
            'pairs' => $results,
        ];
    }
}
