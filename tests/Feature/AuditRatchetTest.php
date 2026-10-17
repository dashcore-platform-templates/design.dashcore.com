<?php

namespace Tests\Feature;

use Dashcore\DesignKit\Commands\AuditCommand;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The ratchet end to end, in a scratch kit standing in for an app.
 */
class AuditRatchetTest extends TestCase
{
    private string $kit;

    private string $realBase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app[Kernel::class]->registerCommand(new AuditCommand);

        $this->kit = sys_get_temp_dir().'/design-kit-ratchet-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->kit.'/resources/views');
        File::put($this->kit.'/resources/views/home.blade.php', "<div class=\"text-[#ff0000]\">One</div>\n");

        $this->realBase = $this->app->basePath();
        $this->app->setBasePath($this->kit);
    }

    protected function tearDown(): void
    {
        $this->app->setBasePath($this->realBase);
        File::deleteDirectory($this->kit);

        parent::tearDown();
    }

    #[Test]
    public function without_an_allowance_any_finding_fails_as_before(): void
    {
        $this->artisan('design:audit')->assertFailed();
    }

    #[Test]
    public function the_day_it_joins_is_green_and_only_a_rise_goes_red(): void
    {
        $this->artisan('design:audit', ['--baseline' => true])->assertSuccessful();
        $this->assertFileExists($this->kit.'/design/allowance.json');

        $this->artisan('design:audit')->expectsOutputToContain('Within the allowance.')->assertSuccessful();

        File::put($this->kit.'/resources/views/new.blade.php', "<div class=\"text-[#00ff00]\">Two</div>\n");
        $this->artisan('design:audit')->expectsOutputToContain('Worse than the allowance')->assertFailed();
    }

    #[Test]
    public function fixing_something_passes_and_recording_it_keeps_the_gain(): void
    {
        $this->artisan('design:audit', ['--baseline' => true])->assertSuccessful();

        File::put($this->kit.'/resources/views/home.blade.php', "<div>One</div>\n");
        $this->artisan('design:audit')->expectsOutputToContain('Nothing off the standard.')->assertSuccessful();
        $this->artisan('design:audit', ['--baseline' => true])->assertSuccessful();

        File::put($this->kit.'/resources/views/home.blade.php', "<div class=\"text-[#ff0000]\">One</div>\n");
        $this->artisan('design:audit')->assertFailed();
    }

    #[Test]
    public function reporting_never_fails_whatever_the_allowance(): void
    {
        $this->artisan('design:audit', ['--baseline' => true])->assertSuccessful();
        File::put($this->kit.'/resources/views/new.blade.php', "<div class=\"text-[#00ff00]\">Two</div>\n");

        $this->artisan('design:audit', ['--report' => true])->assertSuccessful();
    }

    #[Test]
    public function an_app_without_a_pack_is_pointed_at_the_standard_in_the_package(): void
    {
        $this->artisan('design:audit')
            ->expectsOutputToContain('vendor/dashcore/design-kit/STANDARD.md')
            ->doesntExpectOutputToContain('design/MIGRATION.md')
            ->assertFailed();
    }
}
