<?php

namespace Dashcore\DesignKit;

use InvalidArgumentException;

/**
 * Where a design comes from. `design:apply` takes a slug, a URL or a local
 * file, and this class tells them apart and turns what was fetched into the
 * files that belong under design/. Pure: the command does the HTTP.
 */
final class Source
{
    /** The document design.dashcore.com serves at /designs/{slug}/pack.json. */
    public const MANIFEST_SCHEMA = 'dashcore-design-pack/1';

    /** Must match App\Design\Pack::VERSION_LINE in the tool. The stamp follows it. */
    public const VERSION_LINE = 'Standard version: ';

    /**
     * The stamp out of a STANDARD.md, or null when the file predates stamping.
     * Comparing two of these is how a kit finds out the standard moved under it.
     */
    public static function standardVersion(string $standard): ?string
    {
        $pattern = '/^'.preg_quote(self::VERSION_LINE, '/').'([0-9a-f]{6,40})\s*$/m';

        return preg_match($pattern, $standard, $m) === 1 ? $m[1] : null;
    }

    /** 'file', 'url' or 'slug'. */
    public static function kind(string $argument): string
    {
        if (preg_match('~^https?://~i', $argument)) {
            return 'url';
        }

        if (str_contains($argument, '/') || str_contains($argument, '\\') || str_ends_with($argument, '.json')) {
            return 'file';
        }

        return 'slug';
    }

    /** The manifest URL for a slug on a design.dashcore.com instance. */
    public static function url(string $slug, string $base): string
    {
        return rtrim($base, '/').'/designs/'.rawurlencode($slug).'/pack.json';
    }

    /**
     * The files a fetched document stands for, keyed by name under design/.
     * A pack manifest carries all four; a bare design file stands for
     * design.json alone. Names are files only, never paths, so a manifest can
     * write nothing outside design/.
     *
     * @param  array<string,mixed>  $document
     * @return array<string,string>
     */
    public static function files(array $document): array
    {
        if (($document['schema'] ?? null) === self::MANIFEST_SCHEMA && is_array($document['files'] ?? null)) {
            $files = [];
            foreach ($document['files'] as $name => $contents) {
                if (! is_string($name) || ! is_string($contents) || $name === '' || str_contains($name, '/') || str_contains($name, '\\') || str_contains($name, '..')) {
                    throw new InvalidArgumentException("The pack manifest names a file it should not: {$name}.");
                }
                $files[$name] = $contents;
            }

            if (! isset($files['design.json'])) {
                throw new InvalidArgumentException('The pack manifest has no design.json.');
            }

            return $files;
        }

        if (isset($document['tokens'])) {
            return ['design.json' => json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n"];
        }

        throw new InvalidArgumentException('That is neither a design pack nor a design file. Export it from design.dashcore.com.');
    }
}
