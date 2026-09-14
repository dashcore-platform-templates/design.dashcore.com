<?php

namespace App\Design;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Reads the standard from standard/*.md. The same text serves two readers: the
 * Standard page renders it for people, and files 01 to 09 concatenated become
 * the generator's system prompt and the STANDARD.md in the export pack.
 */
final class Standard
{
    public static function directory(): string
    {
        return base_path('standard');
    }

    /** @return array<int, array{file:string, slug:string, title:string, markdown:string}> */
    public static function sections(): array
    {
        return collect(File::glob(self::directory().'/*.md'))
            // README is the front door and CHANGELOG describes the sections
            // rather than being one. Including the changelog would also make
            // version() circular, since it hashes the sections.
            ->filter(fn (string $path) => ! in_array(basename($path), ['README.md', 'CHANGELOG.md'], true))
            ->sort()
            ->map(function (string $path): array {
                $markdown = File::get($path);
                preg_match('/^#\s+(.+)$/m', $markdown, $m);

                return [
                    'file' => basename($path),
                    'slug' => Str::slug(pathinfo($path, PATHINFO_FILENAME)),
                    'title' => $m[1] ?? basename($path),
                    'markdown' => $markdown,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * The marker that ends the part of a file the generator reads. Everything
     * after it is for a person or an agent building a screen: component
     * behaviour, shell geometry, motion, the mapping onto Flux. The generator
     * never builds a screen, so those rules would cost tokens and steer
     * nothing. See docs/cut-sheet.md for which rule sits on which side.
     */
    public const PROMPT_END = '<!-- prompt-end -->';

    /**
     * Files 01 to 09, each truncated at the marker, joined. The audit (00) is
     * history for people and stays out entirely.
     */
    public static function prompt(): string
    {
        return collect(self::sections())
            ->reject(fn (array $s) => str_starts_with($s['file'], '00-'))
            ->map(fn (array $s) => self::beforeMarker($s['markdown']))
            ->implode("\n\n---\n\n");
    }

    /**
     * Files 01 to 09 whole, markers and all. This is what ships in the export
     * pack, because an agent building a screen needs the rules the generator
     * does not: component behaviour, shell geometry, motion, the Flux mapping.
     */
    public static function full(): string
    {
        return collect(self::sections())
            ->reject(fn (array $s) => str_starts_with($s['file'], '00-'))
            ->map(fn (array $s) => str_replace(self::PROMPT_END."\n", '', $s['markdown']))
            ->implode("\n\n---\n\n");
    }

    /**
     * A short stamp for the standard as it currently reads. Derived from the
     * text rather than declared, because a number somebody has to remember to
     * raise is a number that stops being raised. A kit compares its own copy
     * against this to find out whether the standard moved under it.
     */
    public static function version(): string
    {
        return substr(sha1(self::full()), 0, 8);
    }

    /** What changed in the standard and when, newest first. Shipped in the pack. */
    public static function changelog(): string
    {
        $path = self::directory().DIRECTORY_SEPARATOR.'CHANGELOG.md';

        return File::exists($path) ? File::get($path) : '';
    }

    /** One file's generator-facing part: everything above the marker. */
    public static function beforeMarker(string $markdown): string
    {
        $at = strpos($markdown, self::PROMPT_END);

        return rtrim($at === false ? $markdown : substr($markdown, 0, $at));
    }

    public static function readme(): string
    {
        return File::get(self::directory().'/README.md');
    }

    public static function html(string $markdown): string
    {
        return (string) Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }
}
