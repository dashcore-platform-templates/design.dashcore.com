<?php

namespace Tests\Feature;

use Dashcore\DesignKit\Commands\ApplyCommand;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What a kit is told when the design it asks for is not there.
 *
 * The old answer was "Check the slug, and that design.dashcore.com is
 * reachable from here", which is not help. The slug came out of
 * design/design.json without anybody typing it, so being told to check it
 * leaves somebody looking at a file they did not write for a mistake they did
 * not make. ExecutiveOS hit this: it is on a design called executive-os that
 * no longer exists under that name, and the error sent its owner looking at
 * their network.
 */
class ApplyFailureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app[Kernel::class]->registerCommand(new ApplyCommand);
        config(['design-kit.source' => 'https://design.dashcore.com']);
    }

    #[Test]
    public function a_missing_design_names_itself_and_the_one_command_that_fixes_it(): void
    {
        Http::fake(['*' => Http::response('', 404)]);

        $this->artisan('design:apply', ['source' => 'executive-os'])
            ->expectsOutputToContain('There is no design called executive-os')
            // Where to read a real slug, rather than "check the slug".
            ->expectsOutputToContain('https://design.dashcore.com/designs')
            ->expectsOutputToContain('php artisan design:update <slug>')
            // Passing one is not free: it repaints the app.
            ->expectsOutputToContain('repaints this app')
            ->assertFailed();
    }

    #[Test]
    public function anything_other_than_a_404_is_not_blamed_on_the_slug(): void
    {
        // A 500 or a timeout says nothing about whether the design exists, and
        // sending somebody to check their slug over one wastes their time.
        Http::fake(['*' => Http::response('', 503)]);

        $this->artisan('design:apply', ['source' => 'harbor-point'])
            ->expectsOutputToContain('answered HTTP 503')
            ->expectsOutputToContain('nothing has changed here')
            ->doesntExpectOutputToContain('There is no design called')
            ->assertFailed();
    }
}
