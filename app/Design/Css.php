<?php

namespace App\Design;

/**
 * Emits a design's tokens as CSS custom properties. Two audiences: Flux and
 * Tailwind utilities read the --color-zinc-*, --color-accent* , --font-* and
 * --radius-* names, so redefining them rethemes every component; the three
 * custom components and the mockups read the --ds-* roles directly.
 *
 * The same emitter serves the tool's own chrome (on :root and .dark) and each
 * mockup frame (scoped to the frame's id), so a mockup can be dark while the
 * chrome is light.
 */
final class Css
{
    /**
     * Declarations for one register, without selector or braces.
     *
     * @param  array<string,mixed>  $tokens
     */
    public static function declarations(array $tokens, string $register): string
    {
        $r = $tokens[$register];
        $out = [];

        foreach ($tokens['neutral'] as $step => $hex) {
            $out["--color-zinc-{$step}"] = $hex;
        }

        $out['--color-accent'] = $r['accent'];
        $out['--color-accent-content'] = $r['accent-content'];
        $out['--color-accent-foreground'] = $r['on-accent'];

        $sans = $tokens['fonts']['sans'];
        $second = $tokens['fonts']['second'];
        $out['--font-sans'] = "'{$sans['family']}', {$sans['fallback']}";
        $out['--font-second'] = "'{$second['family']}', {$second['fallback']}";
        $out['--font-mono'] = $second['kind'] === 'mono' ? $out['--font-second'] : 'ui-monospace, SFMono-Regular, Menlo, monospace';

        foreach ($tokens['radius'] as $step => $px) {
            $out["--radius-{$step}"] = $px;
        }

        foreach ($tokens['density'] as $name => $px) {
            $out["--ds-{$name}"] = $px;
        }

        foreach ($r as $role => $hex) {
            $out["--ds-{$role}"] = $hex;
        }

        foreach ($tokens['status'] as $name => $s) {
            $out["--ds-{$name}"] = $s["text-{$register}"];
            $out["--ds-{$name}-fill"] = $s['fill'];
            $out["--ds-{$name}-soft"] = $s["soft-{$register}"];
        }

        return collect($out)->map(fn (string $v, string $k) => "{$k}: {$v};")->implode(' ');
    }

    /**
     * A full rule set scoped to a selector: light on the selector itself, dark
     * when it also carries the .dark class. For :root pass ':root'.
     *
     * @param  array<string,mixed>  $tokens
     */
    public static function scoped(string $selector, array $tokens): string
    {
        $dark = $selector === ':root' ? ':root.dark, .dark' : "{$selector}.dark";

        return "{$selector} { ".self::declarations($tokens, 'light')." }\n{$dark} { ".self::declarations($tokens, 'dark').' }';
    }

    /**
     * The @theme block the Phase 3 adapter writes into a kit's app.css.
     *
     * @param  array<string,mixed>  $tokens
     */
    public static function themeBlock(array $tokens): string
    {
        $lines = ['/* dashcore-design:start (generated, do not edit by hand) */', '@theme {'];
        foreach ($tokens['neutral'] as $step => $hex) {
            $lines[] = "    --color-zinc-{$step}: {$hex};";
        }
        $lines[] = "    --color-accent: {$tokens['light']['accent']};";
        $lines[] = "    --color-accent-content: {$tokens['light']['accent-content']};";
        $lines[] = "    --color-accent-foreground: {$tokens['light']['on-accent']};";
        $sans = $tokens['fonts']['sans'];
        $second = $tokens['fonts']['second'];
        $lines[] = "    --font-sans: '{$sans['family']}', {$sans['fallback']};";
        $lines[] = "    --font-second: '{$second['family']}', {$second['fallback']};";
        foreach ($tokens['radius'] as $step => $px) {
            $lines[] = "    --radius-{$step}: {$px};";
        }
        $lines[] = '}';
        $lines[] = '@layer theme { .dark {';
        $lines[] = "    --color-accent: {$tokens['dark']['accent']};";
        $lines[] = "    --color-accent-content: {$tokens['dark']['accent-content']};";
        $lines[] = "    --color-accent-foreground: {$tokens['dark']['on-accent']};";
        $lines[] = '} }';
        $lines[] = ':root { '.self::rolesOnly($tokens, 'light').' }';
        $lines[] = '.dark { '.self::rolesOnly($tokens, 'dark').' }';
        $lines[] = '/* dashcore-design:end */';

        return implode("\n", $lines);
    }

    /** @param  array<string,mixed>  $tokens */
    private static function rolesOnly(array $tokens, string $register): string
    {
        $out = [];
        foreach ($tokens['density'] as $name => $px) {
            $out[] = "--ds-{$name}: {$px};";
        }
        foreach ($tokens[$register] as $role => $hex) {
            $out[] = "--ds-{$role}: {$hex};";
        }
        foreach ($tokens['status'] as $name => $s) {
            $out[] = "--ds-{$name}: {$s["text-{$register}"]}; --ds-{$name}-fill: {$s['fill']}; --ds-{$name}-soft: {$s["soft-{$register}"]};";
        }

        return implode(' ', $out);
    }
}
