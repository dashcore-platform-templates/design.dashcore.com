<?php

namespace Tests\Feature;

use Dashcore\DesignKit\DesignKitServiceProvider;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The package's entry point, booted the way a kit boots it.
 *
 * The tool installs one command from the package by hand and never loads this
 * provider, so nothing here exercised it. A missing `use` line for a command
 * left `ConnectCommand::class` resolving to the package's own namespace, which
 * is a class that does not exist, and every artisan command in every kit died
 * on it. Green here, broken everywhere it matters.
 */
class DesignKitProviderTest extends TestCase
{
    #[Test]
    public function every_command_it_registers_can_be_resolved(): void
    {
        (new DesignKitServiceProvider($this->app))->boot();

        // all() resolves each registration. A class string that points at
        // nothing throws here rather than in somebody else's repository.
        $commands = array_keys(Artisan::all());

        foreach (['design:apply', 'design:audit', 'design:check', 'design:connect', 'design:update'] as $name) {
            $this->assertContains($name, $commands, "the provider does not register {$name}");
        }
    }

    #[Test]
    public function the_audit_knows_which_board_to_report_to(): void
    {
        // The tool's own CI reports with design:audit --report, which reads
        // this. Empty, the report went to "/api/reports" and was lost.
        $this->assertSame('https://design.dashcore.com', config('design-kit.source'));
    }
}
