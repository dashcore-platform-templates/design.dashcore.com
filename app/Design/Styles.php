<?php

namespace App\Design;

use InvalidArgumentException;
use RuntimeException;

/**
 * The built-in styles: a fixed set of tokens with a name, shipped inside
 * design-kit and read by an app from vendor/ rather than copied into it.
 *
 * A design is the seven decisions put through the resolver. A style is the
 * same tokens written down directly, because the one style there is, dashcore,
 * makes four exceptions the resolver is right to refuse a generated design.
 * standard/14 is the rule; this is it as data, and the two say the same thing.
 *
 * Every style goes through the resolver's own contrast check, so a style that
 * would fail the floor cannot be built, never mind shipped.
 */
final class Styles
{
    /** The motif each style takes (07). Not a token, but the components read it. */
    public const MOTIFS = ['dashcore' => 'accent-bar'];

    /** @return list<string> */
    public static function names(): array
    {
        return ['dashcore'];
    }

    /** @return array<string,mixed> */
    public static function get(string $name): array
    {
        $tokens = match ($name) {
            'dashcore' => self::dashcore(),
            default => throw new InvalidArgumentException("Unknown style: {$name}"),
        };

        $tokens['contrast'] = Resolver::measure($tokens);
        if (! $tokens['contrast']['passes']) {
            throw new RuntimeException("The {$name} style fails contrast at {$tokens['contrast']['lowest']['pair']}.");
        }

        return $tokens;
    }

    /**
     * standard/14. Exceptions 1 to 4 are the accent, the neutrals, the pairing
     * and the radius ceiling; everything else is the standard's.
     *
     * @return array<string,mixed>
     */
    private static function dashcore(): array
    {
        $accentSoft = fn (string $accent, string $alpha) => $accent.$alpha;

        return [
            'fonts' => [
                'sans' => ['family' => 'Inter', 'bunny' => 'inter', 'weights' => [400, 500, 600], 'fallback' => 'ui-sans-serif, system-ui, sans-serif'],
                'second' => ['family' => 'Fraunces', 'bunny' => 'fraunces', 'weights' => [500, 600], 'kind' => 'serif', 'fallback' => 'ui-serif, Georgia, serif', 'condensed' => false],
                'stylesheet' => 'https://fonts.bunny.net/css?family=inter:400,500,600|fraunces:500,600&display=swap',
            ],
            'radius' => ['xs' => '2px', 'sm' => '2px', 'md' => '3px', 'lg' => '4px', 'xl' => '4px', '2xl' => '4px', '3xl' => '4px', '4xl' => '4px'],
            'radius_ceiling' => '4px',
            'density' => array_map(fn (int $px): string => "{$px}px", Resolver::DENSITY_STEPS['comfortable']),
            // Flux paints from zinc, so the style's own neutrals stand in for it,
            // each step the role that sits nearest it in 14.
            'neutral' => [
                50 => '#f7f4ef', 100 => '#efebe2', 200 => '#d8d2c7', 300 => '#b8af9f', 400 => '#9b948a',
                500 => '#6b6560', 600 => '#454039', 700 => '#2e2b26', 800 => '#1d1b18', 900 => '#151412', 950 => '#11100e',
            ],
            'light' => [
                'page' => '#f7f4ef', 'surface' => '#ffffff', 'sunken' => '#efebe2',
                'border' => '#d8d2c7', 'border-strong' => '#b8af9f',
                'text-strong' => '#1c1b19', 'text' => '#1c1b19', 'text-muted' => '#6b6560', 'text-muted-on-sunken' => '#6b6560',
                'accent' => '#4a4438', 'accent-hover' => '#33301f', 'accent-content' => '#4a4438', 'accent-soft' => $accentSoft('#4a4438', '1a'), 'on-accent' => '#ffffff',
            ],
            'dark' => [
                'page' => '#151412', 'surface' => '#1d1b18', 'sunken' => '#11100e',
                'border' => '#2e2b26', 'border-strong' => '#454039',
                'text-strong' => '#ece7dd', 'text' => '#ece7dd', 'text-muted' => '#a39c90', 'text-muted-on-sunken' => '#aaa397',
                'accent' => '#d9cbb0', 'accent-hover' => '#e8dcc4', 'accent-content' => '#d9cbb0', 'accent-soft' => $accentSoft('#d9cbb0', '29'), 'on-accent' => '#1c1b19',
            ],
            'status' => Resolver::STATUS,
        ];
    }
}
