<?php

namespace Dashcore\DesignKit;

/**
 * Finds what in an app is off the standard. Pure: give it a file's contents,
 * get back findings. The command walks the tree and reports.
 *
 * It only reports what can be judged from the text of a file. Layout questions,
 * whether a stat row says the right things, whether a screen is composed around
 * its content, are for a person looking at the screen. Those live in the pack's
 * MIGRATION.md, not here.
 */
final class Audit
{
    /** Spacing steps that are not on the standard's eight-step scale (05). */
    private const OFF_SCALE = ['5', '7', '9', '10', '11', '13', '14', '15', '18', '20'];

    private const SPACING_PREFIXES = ['p', 'px', 'py', 'pt', 'pb', 'ps', 'pe', 'm', 'mx', 'my', 'mt', 'mb', 'ms', 'me', 'gap', 'gap-x', 'gap-y', 'space-x', 'space-y'];

    /** Colour families the standard does not use directly in a view. */
    private const OFF_PALETTE = ['red', 'amber', 'green', 'blue', 'sky', 'emerald', 'rose', 'orange', 'yellow', 'indigo', 'violet', 'purple', 'pink', 'teal', 'cyan', 'lime', 'stone', 'slate', 'gray', 'neutral'];

    /** A whole file that is deliberately outside the standard says so once, with a reason. */
    public const IGNORE_FILE = '@ds-audit-ignore-file';

    /**
     * @param  string|null  $path  Where the file lives, when known. A sign-in view
     *                             keeps its own heading, so the page-title rule
     *                             does not apply there.
     * @return list<array{rule: string, line: int, snippet: string, fix: string}>
     */
    public static function file(string $contents, ?string $path = null): array
    {
        if (self::optsOut($contents)) {
            return [];
        }

        $findings = [];
        $lines = preg_split('/\R/', $contents) ?: [];
        $isAuth = $path !== null && self::isAuthView($path);

        foreach ($lines as $i => $line) {
            $n = $i + 1;

            if (str_contains($line, '@ds-audit-ignore')) {
                continue;
            }

            foreach (self::matches($line, '/\b(?:bg|text|border|ring|from|to|via|divide|outline|decoration|shadow|accent|fill|stroke)-('.implode('|', self::OFF_PALETTE).')-\d{2,3}\b/') as $m) {
                $findings[] = self::finding('colour off the roles', $n, $m, 'Use a --ds-* role: text, text-muted, border, accent-content, or a status role.');
            }

            foreach (self::matches($line, '/(?<![\w-])#[0-9a-fA-F]{3,8}\b/') as $m) {
                if (! str_contains($line, '--ds-') && ! str_contains($line, 'dashcore-design')) {
                    $findings[] = self::finding('hardcoded colour', $n, $m, 'Move it to a --ds-* role, or to the design file if it is an identity colour.');
                }
            }

            foreach (self::matches($line, '/\b(?:'.implode('|', self::SPACING_PREFIXES).')-(?:'.implode('|', self::OFF_SCALE).')\b/') as $m) {
                $findings[] = self::finding('spacing off the scale', $n, $m, 'The scale is 1 2 3 4 6 8 12 16. Pick the nearest, or a --ds-* density value.');
            }

            foreach (self::matches($line, '/\bfont-(?:bold|extrabold|black|light|thin|extralight)\b/') as $m) {
                $findings[] = self::finding('weight off the scale', $n, $m, 'Three weights: font-normal, font-medium, font-semibold.');
            }

            foreach (self::matches($line, '/\btext-\[(?:[0-9]|10|11)px\]/') as $m) {
                $findings[] = self::finding('text under 12px', $n, $m, 'Nothing is set below 12px, including badges and table heads.');
            }

            foreach (self::matches($line, '/<flux:badge\b/') as $m) {
                $findings[] = self::finding('Flux badge', $n, $m, 'Use x-ds::badge: a status for the four the standard names, no status for a category, a role or a plan. Flux paints badges from Tailwind, not the standard roles.');
            }

            foreach (self::matches($line, '/\b(?:'.implode('|', array_keys(Applier::LUCIDE_RENAMES)).')\b/') as $m) {
                $findings[] = self::finding('Lucide icon name', $n, $m, 'Heroicons only. `design:apply` renames these.');
            }

            if (! $isAuth) {
                foreach (self::matches($line, '/<flux:heading[^>]*size="xl"|<h1\b/') as $m) {
                    $findings[] = self::finding('page title not in the page header', $n, $m, 'Titles go through x-ds::page-header, which applies the pairing second face.');
                }
            }
        }

        return $findings;
    }

    /** True when the file carries the whole-file opt-out marker. */
    public static function optsOut(string $contents): bool
    {
        return str_contains($contents, self::IGNORE_FILE);
    }

    /**
     * Sign-in, register, reset, and the layouts under them: a form on a page
     * of its own, not an app screen, so a heading there is not a page title.
     */
    public static function isAuthView(string $path): bool
    {
        $path = str_replace('\\', '/', $path);

        return (bool) preg_match('~(^|/)auth(/|\.blade\.php$|-[^/]*\.blade\.php$)~', $path);
    }

    /** @return list<string> */
    private static function matches(string $line, string $pattern): array
    {
        preg_match_all($pattern, $line, $m);

        return array_values(array_unique($m[0]));
    }

    /** @return array{rule: string, line: int, snippet: string, fix: string} */
    private static function finding(string $rule, int $line, string $snippet, string $fix): array
    {
        return ['rule' => $rule, 'line' => $line, 'snippet' => $snippet, 'fix' => $fix];
    }

    /**
     * The mechanical half: rewrites with no judgment in them. Returns the new
     * contents and what changed. Anything needing a look at the screen is left
     * for the report.
     *
     * @return array{0: string, 1: list<string>}
     */
    public static function mechanical(string $contents): array
    {
        $changed = [];

        $weights = ['font-bold' => 'font-semibold', 'font-extrabold' => 'font-semibold', 'font-black' => 'font-semibold', 'font-light' => 'font-normal', 'font-thin' => 'font-normal', 'font-extralight' => 'font-normal'];
        foreach ($weights as $from => $to) {
            $out = preg_replace('/\b'.preg_quote($from, '/').'\b/', $to, $contents, -1, $count);
            if ($count > 0) {
                $contents = (string) $out;
                $changed[] = "{$from} to {$to} ({$count})";
            }
        }

        // Off-scale spacing to the nearest step on the eight-step scale.
        $steps = ['5' => '4', '7' => '6', '9' => '8', '10' => '8', '11' => '12', '13' => '12', '14' => '12', '15' => '16', '18' => '16', '20' => '16'];
        foreach ($steps as $from => $to) {
            $pattern = '/\b((?:'.implode('|', self::SPACING_PREFIXES).')-)'.$from.'\b/';
            $out = preg_replace($pattern, '${1}'.$to, $contents, -1, $count);
            if ($count > 0) {
                $contents = (string) $out;
                $changed[] = "spacing {$from} to {$to} ({$count})";
            }
        }

        return [$contents, $changed];
    }
}
