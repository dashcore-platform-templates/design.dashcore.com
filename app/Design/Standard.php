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
            ->filter(fn (string $path) => basename($path) !== 'README.md')
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

    /** Files 01 to 09 joined. The audit (00) is history for people and stays out. */
    public static function prompt(): string
    {
        return collect(self::sections())
            ->reject(fn (array $s) => str_starts_with($s['file'], '00-'))
            ->map(fn (array $s) => $s['markdown'])
            ->implode("\n\n---\n\n");
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
