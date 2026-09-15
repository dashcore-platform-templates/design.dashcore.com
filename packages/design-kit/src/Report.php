<?php

namespace Dashcore\DesignKit;

use Composer\InstalledVersions;
use Throwable;

/**
 * What an app tells design.dashcore.com about itself.
 *
 * Counts by rule and two version stamps, and nothing else. No file paths, no
 * snippets, no lines: the fleet view answers "how far off is it" and "is it
 * getting better", and neither question needs anybody's source.
 *
 * Pure, so the shape can be tested without a network or an app.
 */
final class Report
{
    /**
     * @param  array<string,int>  $byRule
     * @return array<string,mixed>
     */
    public static function payload(
        array $byRule,
        int $filesWithFindings,
        int $filesChecked,
        ?string $standardVersion,
        ?string $designSlug,
        ?string $packageVersion = null,
    ): array {
        // Rule names come from the audit, so they are ours, but the counts are
        // summed here rather than trusted from a caller that might have added
        // its own total.
        $counts = [];

        foreach ($byRule as $rule => $count) {
            if (is_string($rule) && is_int($count) && $count > 0) {
                $counts[$rule] = $count;
            }
        }

        arsort($counts);

        return [
            'standard_version' => $standardVersion,
            'package_version' => $packageVersion ?? self::packageVersion(),
            'design_slug' => $designSlug,
            'findings' => $counts,
            'total_findings' => array_sum($counts),
            'files_with_findings' => max(0, $filesWithFindings),
            'files_checked' => max(0, $filesChecked),
            'reported_at' => gmdate('c'),
        ];
    }

    /**
     * Which design-kit produced those counts.
     *
     * A count means nothing without it: the same app audited by two package
     * versions gives two numbers and neither is wrong. Composer knows, and a
     * path install has no version to give, which is worth saying rather than
     * hiding as null.
     */
    public static function packageVersion(): ?string
    {
        try {
            return InstalledVersions::getPrettyVersion('dashcore/design-kit');
        } catch (Throwable) {
            return null;
        }
    }
}
