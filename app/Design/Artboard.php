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
    /**
     * The four frames the canvas can draw, listed in the menu by size.
     *
     * 12 names three bands: phone below `md`, tablet from `md` to below `lg`,
     * desktop at `lg` and up. Four frames rather than three because desktop
     * and laptop sit in the same band and still disagree: 02 caps content at
     * 80rem, which is 1280, so a laptop fills the cap exactly and a desktop
     * shows the gutter either side of it. Nothing else distinguishes them.
     *
     * The heights are real device heights rather than one number for all, so a
     * phone frame is a phone's shape and a fold is where a fold really is.
     */
    public const VIEWPORTS = [
        'desktop' => ['label' => 'Desktop', 'width' => 1440, 'height' => 900],
        'laptop' => ['label' => 'Laptop', 'width' => 1280, 'height' => 800],
        'tablet' => ['label' => 'Tablet', 'width' => 768, 'height' => 1024],
        'mobile' => ['label' => 'Mobile', 'width' => 390, 'height' => 844],
    ];

    /**
     * On by default: the two ends of the range.
     *
     * Not all four, because the more frames are drawn the smaller every one of
     * them gets, and a frame too small to read is worse than a frame that is
     * one click away. The two that are hardest to satisfy at once are on.
     */
    public const DEFAULT_SHOWN = ['desktop', 'mobile'];

    /**
     * Layout order, which is not menu order.
     *
     * The two ends of the range take the first row, because they are the pair
     * worth reading against each other and they are what is on by default.
     * The two middles fill the row below as they are turned on, so ticking one
     * adds a row rather than pushing the default pair apart.
     */
    public const LAYOUT_ORDER = ['desktop', 'mobile', 'laptop', 'tablet'];

    /**
     * The chosen frames, in layout order, never empty and never anything else.
     *
     * The list arrives from the URL and decides what gets rendered, so it is
     * filtered against the map rather than trusted.
     *
     * @param  array<int,mixed>  $chosen
     * @return list<string>
     */
    public static function shown(array $chosen): array
    {
        $valid = self::valid($chosen);

        return $valid === [] ? self::DEFAULT_SHOWN : $valid;
    }

    /**
     * The same filter without the fallback, for the property the checkboxes
     * are bound to. It has to be able to say "none ticked", or unticking the
     * last box would tick two more and the menu would fight the person using it.
     *
     * @param  array<int,mixed>  $chosen
     * @return list<string>
     */
    public static function valid(array $chosen): array
    {
        $valid = [];

        foreach (self::LAYOUT_ORDER as $name) {
            if (in_array($name, $chosen, true)) {
                $valid[] = $name;
            }
        }

        return $valid;
    }

    /**
     * How wide the grid is unscaled, so the canvas knows what to fit.
     *
     * Two columns, filled in order, so frames wrap to a second row rather than
     * running off the side. A column is as wide as its widest frame, which is
     * why this cannot be a plain sum.
     *
     * @param  list<string>  $shown
     */
    public static function gridWidth(array $shown, int $gap): int
    {
        $columns = [0, 0];

        foreach ($shown as $i => $name) {
            $columns[$i % 2] = max($columns[$i % 2], self::VIEWPORTS[$name]['width']);
        }

        return $columns[1] === 0 ? $columns[0] : $columns[0] + $gap + $columns[1];
    }

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
