<?php

namespace App\Design;

use Illuminate\Support\Facades\Vite;

/**
 * One artboard on the canvas: a mockup rendered as a whole document, handed to
 * an iframe through srcdoc.
 *
 * It has to be an iframe rather than a scaled div. A transform-scaled div does
 * not create a viewport, so the breakpoints inside it keep measuring the
 * browser window. A 375px artboard built that way would render the desktop
 * layout squeezed, which is worse than showing nothing, because it would look
 * checked when it is not.
 *
 * srcdoc rather than a route, because the page holds decisions the user has
 * changed and not saved. A route would read the file from disk and show the
 * old design while the controls said otherwise.
 */
final class Artboard
{
    /** The widths the canvas renders at. 12 names them; these are the numbers. */
    public const WIDTHS = [
        'desktop' => 1280,
        'phone' => 375,
        'tablet' => 820,
    ];

    /** Shown side by side. Tablet is the width fewest things break at, so it is not one of them. */
    public const SHOWN = ['desktop', 'phone'];

    /**
     * A complete HTML document for one artboard.
     *
     * @param  array<string,mixed>  $tokens
     */
    public static function document(string $markup, array $tokens, string $register): string
    {
        $dark = $register === 'dark' ? ' class="dark"' : '';
        $stylesheet = e($tokens['fonts']['stylesheet'] ?? '');
        $css = Css::scoped(':root', $tokens);

        // The built stylesheet by URL rather than inlined. Inlining 350KB twice
        // into an attribute would make every keystroke rewrite a megabyte.
        $app = Vite::asset('resources/css/app.css');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en"{$dark}>
        <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="{$stylesheet}">
        <link rel="stylesheet" href="{$app}">
        <style>{$css}</style>
        <style>
            html, body { margin: 0; background: var(--ds-page); color: var(--ds-text); font-family: var(--font-sans); color-scheme: {$register}; }
            /* The artboard is the page here, so nothing inside it scrolls on its own. */
            body { overflow-x: hidden; }
        </style>
        </head>
        <body>{$markup}</body>
        </html>
        HTML;
    }
}
