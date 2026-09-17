<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What the app reaches for at runtime has to exist at runtime.
 *
 * The tool uses the package it publishes: the audit command, the ds components
 * and the workflow snippets the connect guide renders. That namespace was
 * mapped only in autoload-dev, and production installs with --no-dev, so the
 * class was simply absent there. Every test passed, because a test run has the
 * dev autoload.
 *
 * /connect was a 500 in production and fine everywhere else.
 */
class ProductionAutoloadTest extends TestCase
{
    #[Test]
    public function the_package_namespace_is_not_dev_only(): void
    {
        $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);

        $this->assertArrayHasKey(
            'Dashcore\\DesignKit\\',
            $composer['autoload']['psr-4'],
            'the app uses this namespace at runtime, so --no-dev has to keep it'
        );

        // Kept out of both, rather than in both, so there is one answer to
        // where it is mapped.
        $this->assertArrayNotHasKey('Dashcore\\DesignKit\\', $composer['autoload-dev']['psr-4']);
    }

    #[Test]
    public function every_class_the_app_names_from_it_resolves(): void
    {
        // Named rather than scanned, because the point is the short list the
        // app actually depends on, and a new one should be a deliberate
        // addition here.
        foreach ([
            \Dashcore\DesignKit\Workflow::class,
            \Dashcore\DesignKit\Commands\AuditCommand::class,
        ] as $class) {
            $this->assertTrue(class_exists($class), "{$class} is missing");
        }
    }
}
