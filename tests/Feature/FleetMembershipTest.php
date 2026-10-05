<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Design is enrolled on the bridge so the fleet's monitors can see it. A peer
 * that answers the ping but never reports reads as a quiet app rather than a
 * blind spot, so the reporting schedule is held here alongside the routes.
 */
class FleetMembershipTest extends TestCase
{
    #[Test]
    public function the_bridge_serves_its_ping_and_health_surfaces(): void
    {
        $this->assertTrue(Route::has('bridge.ping'));
        $this->assertTrue(Route::has('bridge.health'));
    }

    #[Test]
    public function the_calls_and_telemetry_reports_are_scheduled(): void
    {
        $commands = collect(app(Schedule::class)->events())->pluck('command')->implode("\n");

        $this->assertStringContainsString('bridge:report-calls', $commands);
        $this->assertStringContainsString('bridge:report-telemetry --prune', $commands);
    }
}
