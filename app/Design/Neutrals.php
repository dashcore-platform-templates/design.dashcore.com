<?php

namespace App\Design;

use InvalidArgumentException;

/**
 * The three neutral temperatures from standard/04-colour.md. Each fills Flux's
 * eleven zinc slots, which is what rethemes every component at once.
 */
final class Neutrals
{
    public const TEMPERATURES = ['pure', 'cool', 'warm'];

    private const SCALES = [
        'pure' => [50 => '#fafafa', 100 => '#f5f5f5', 200 => '#e5e5e5', 300 => '#d4d4d4', 400 => '#a3a3a3', 500 => '#737373', 600 => '#525252', 700 => '#404040', 800 => '#262626', 900 => '#171717', 950 => '#0a0a0a'],
        'cool' => [50 => '#f8fafc', 100 => '#f1f5f9', 200 => '#e2e8f0', 300 => '#cbd5e1', 400 => '#94a3b8', 500 => '#64748b', 600 => '#475569', 700 => '#334155', 800 => '#1e293b', 900 => '#0f172a', 950 => '#020617'],
        'warm' => [50 => '#fafaf9', 100 => '#f5f5f4', 200 => '#e7e5e4', 300 => '#d6d3d1', 400 => '#a8a29e', 500 => '#78716c', 600 => '#57534e', 700 => '#44403c', 800 => '#292524', 900 => '#1c1917', 950 => '#0c0a09'],
    ];

    /** @return array<int,string> */
    public static function scale(string $temperature): array
    {
        return self::SCALES[$temperature] ?? throw new InvalidArgumentException("Unknown temperature: {$temperature}");
    }

    public static function describe(string $temperature): string
    {
        return match ($temperature) {
            'pure' => 'True grey. Technical and financial work.',
            'cool' => 'Blue-leaning grey. Software and data.',
            'warm' => 'Brown-leaning grey. People, hospitality, health.',
            default => '',
        };
    }
}
