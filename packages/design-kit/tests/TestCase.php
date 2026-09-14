<?php

namespace Dashcore\DesignKit\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * The package's own tests, so a kit can check the thing it installed without
 * the tool that writes it. Everything here is plain PHP against plain arrays,
 * which is why it needs no Laravel: the applier, the audit and the source
 * resolver are all string and array work.
 *
 * The two component tests stay in the design repo, because rendering a Blade
 * component needs a framework and Flux.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * A real exported design, not a hand-made one. A fixture invented here
     * would drift from the shape the tool actually writes, and the applier
     * would keep passing against a file nothing produces.
     *
     * Refresh with: php packages/design-kit/tests/fixtures/refresh.php
     *
     * @return array<string,mixed>
     */
    protected function exported(string $slug = 'harbor-point'): array
    {
        $path = __DIR__."/fixtures/{$slug}.json";

        if (! is_file($path)) {
            $this->fail("no fixture for {$slug}; run tests/fixtures/refresh.php from the design repo");
        }

        /** @var array<string,mixed> $design */
        $design = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return $design;
    }
}
