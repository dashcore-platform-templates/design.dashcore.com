<?php

namespace App\Design;

/**
 * The mockup screens, in the order they are shown.
 *
 * An explicit list rather than a glob of the mockups folder, for two reasons.
 * The folder also holds shared partials, which are not screens. And the chosen
 * screen arrives from the URL and ends up in a Blade include, so it has to be
 * checked against something before it gets there.
 */
final class Screens
{
    public const DEFAULT = 'dashboard';

    /** @var array<string,string> view name => label */
    public const ALL = [
        'sign-in' => 'Sign-in',
        'shell' => 'Shell',
        'dashboard' => 'Dashboard',
        'settings' => 'Settings',
        'components' => 'Components',
        'email' => 'Email',
        'errors' => 'Errors',
    ];

    public static function has(string $screen): bool
    {
        return array_key_exists($screen, self::ALL);
    }

    /** The screen asked for, or the default. Never anything else, because this reaches an include. */
    public static function resolve(?string $screen): string
    {
        return $screen !== null && self::has($screen) ? $screen : self::DEFAULT;
    }
}
