<?php

namespace App\Design\Generation;

use App\Design\Color;
use App\Design\Neutrals;
use App\Design\Pairings;
use App\Design\Resolver;

/**
 * What the schema cannot express, checked in PHP after the model answers: the
 * accent hue and saturation rules from 04, the copy word limits from 09, and a
 * safe fallback for any value that is somehow off the list. Every correction is
 * appended to the decision's `why` so a person can see what changed and why.
 */
final class Rules
{
    /**
     * @param  array<string,mixed>  $decisions
     * @param  array<string,mixed>  $brief
     * @return array<string,mixed>
     */
    public static function apply(array $decisions, array $brief): array
    {
        $decisions = self::accent($decisions, $brief);
        $decisions = self::fromList($decisions, 'temperature', Neutrals::TEMPERATURES, 'pure');
        $decisions = self::fromList($decisions, 'radius', Resolver::RADII, 'soft');
        $decisions = self::fromList($decisions, 'density', Resolver::DENSITIES, 'comfortable');
        $decisions = self::fromList($decisions, 'motif', Resolver::MOTIFS, 'accent-bar');
        $decisions = self::pairing($decisions);

        return self::copy($decisions);
    }

    /**
     * @param  array<string,mixed>  $decisions
     * @param  array<string,mixed>  $brief
     * @return array<string,mixed>
     */
    private static function accent(array $decisions, array $brief): array
    {
        $raw = strtolower(trim((string) ($decisions['accent']['value'] ?? '')));
        if (preg_match('/^#?([0-9a-f]{6})$/', $raw, $m)) {
            $hex = '#'.$m[1];
        } else {
            $hex = '#2d5bd7';
            $decisions['accent']['why'] = self::note($decisions['accent']['why'] ?? '', "The model returned '{$raw}', which is not a hex colour; a neutral blue stands in.");
        }

        $brand = strtolower(trim((string) ($brief['brand_colour'] ?? '')));
        $brandGiven = preg_match('/^#[0-9a-f]{6}$/', $brand) === 1;

        if ($brandGiven && $hex === $brand) {
            // The brief overrides the hue rule; the standard says so in 09.
            $decisions['accent']['value'] = $hex;

            return $decisions;
        }

        [$h, $s, $l] = Color::hexToHsl($hex);
        $changes = [];

        // Hue must be at least 30 degrees from red (0) and amber (40): allowed is 70 to 330.
        if ($h < 70 || $h > 330) {
            $target = ($h <= 20 || $h > 330) ? 330 : 70;
            if ($h > 330) {
                $target = 330;
            }
            $changes[] = sprintf('hue moved from %d to %d degrees to stay clear of the red and amber zones', round($h), $target);
            $h = $target;
        }

        if ($s < 45) {
            $changes[] = sprintf('saturation raised from %d to 45 percent so it does not read as a grey', round($s));
            $s = 45;
        } elseif ($s > 85) {
            $changes[] = sprintf('saturation lowered from %d to 85 percent so it does not vibrate on white', round($s));
            $s = 85;
        }

        if ($changes !== []) {
            $adjusted = Color::hslToHex($h, $s, $l);
            $decisions['accent']['why'] = self::note($decisions['accent']['why'] ?? '', 'Adjusted from '.$hex.': '.implode('; ', $changes).'.');
            $hex = $adjusted;
        }

        $decisions['accent']['value'] = $hex;

        return $decisions;
    }

    /**
     * @param  array<string,mixed>  $decisions
     * @param  array<int,string>  $allowed
     * @return array<string,mixed>
     */
    private static function fromList(array $decisions, string $key, array $allowed, string $fallback): array
    {
        $value = strtolower(trim((string) ($decisions[$key]['value'] ?? '')));
        if (! in_array($value, $allowed, true)) {
            $decisions[$key]['why'] = self::note($decisions[$key]['why'] ?? '', "'{$value}' is not on the list; {$fallback} stands in.");
            $value = $fallback;
        }
        $decisions[$key]['value'] = $value;

        return $decisions;
    }

    /**
     * @param  array<string,mixed>  $decisions
     * @return array<string,mixed>
     */
    private static function pairing(array $decisions): array
    {
        $id = (int) ($decisions['pairing']['value'] ?? 0);
        if (! array_key_exists($id, Pairings::all())) {
            $decisions['pairing']['why'] = self::note($decisions['pairing']['why'] ?? '', "Pairing {$id} does not exist; pairing 1 stands in.");
            $id = 1;
        }
        $decisions['pairing']['value'] = $id;

        return $decisions;
    }

    /**
     * @param  array<string,mixed>  $decisions
     * @return array<string,mixed>
     */
    private static function copy(array $decisions): array
    {
        $copy = $decisions['copy'] ?? [];
        $limits = ['eyebrow' => 4, 'headline' => 8, 'tagline' => 16];
        $trimmed = [];

        foreach ($limits as $field => $max) {
            $text = trim((string) ($copy[$field] ?? ''));
            $text = str_replace(['—', '–', '!'], [',', ',', '.'], $text);
            $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (count($words) > $max) {
                $text = implode(' ', array_slice($words, 0, $max));
                $trimmed[] = "{$field} cut to {$max} words";
            }
            $copy[$field] = $text === '' ? null : $text;
        }

        $copy['headline'] = $copy['headline'] ?? 'Welcome back.';
        $copy['tagline'] = $copy['tagline'] ?? 'Sign in to pick up where you left off.';
        $copy['footer'] = filled($copy['footer'] ?? null) ? trim((string) $copy['footer']) : null;
        $copy['why'] = (string) ($copy['why'] ?? '');

        if ($trimmed !== []) {
            $copy['why'] = self::note($copy['why'], ucfirst(implode('; ', $trimmed)).'.');
        }

        $decisions['copy'] = [
            'eyebrow' => $copy['eyebrow'],
            'headline' => $copy['headline'],
            'tagline' => $copy['tagline'],
            'footer' => $copy['footer'],
            'why' => $copy['why'],
        ];

        return $decisions;
    }

    private static function note(string $why, string $note): string
    {
        return trim($why) === '' ? $note : rtrim($why, ' .').'. '.$note;
    }
}
