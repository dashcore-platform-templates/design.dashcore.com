<?php

namespace Tests\Feature;

use Dashcore\DesignKit\Commands\ApplyCommand;
use Dashcore\DesignKit\Commands\UpdateCommand;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * design:apply --style against a kit laid out the way a dashcore/ui app is,
 * in a scratch directory standing in for the app's base path.
 */
class ApplyStyleTest extends TestCase
{
    private string $kit;

    private string $realBase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app[Kernel::class]->registerCommand(new ApplyCommand);
        $this->app[Kernel::class]->registerCommand(new UpdateCommand);

        $this->kit = sys_get_temp_dir().'/design-kit-style-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->kit.'/resources/css');
        File::ensureDirectoryExists($this->kit.'/resources/js');
        File::put($this->kit.'/resources/css/app.css', "@import 'tailwindcss';\n@import '../../vendor/dashcore/ui/resources/css/ui.css';\n@source '../../vendor/dashcore/ui/resources/views';\n");
        File::put($this->kit.'/resources/js/app.js', "import './bootstrap';\nimport '../../vendor/dashcore/ui/resources/js/ui.js';\n");
        File::put($this->kit.'/.env', "APP_NAME=Finance\n");

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
    public function a_dashcore_ui_app_moves_onto_the_style_with_pointers_only(): void
    {
        $this->artisan('design:apply', ['--style' => 'dashcore'])->assertSuccessful();

        $this->assertSame(['schema' => 'dashcore-design/1', 'style' => 'dashcore'], json_decode(File::get($this->kit.'/design/design.json'), true));

        $css = File::get($this->kit.'/resources/css/app.css');
        $this->assertStringContainsString("@import '../../vendor/dashcore/design-kit/dist/dashcore.css';", $css);
        $this->assertStringNotContainsString('dashcore/ui', $css);
        // A pointer, never a copy of a value the package holds.
        $this->assertStringNotContainsString('#4a4438', $css);

        $this->assertStringContainsString('design-kit/resources/js/frame.js', File::get($this->kit.'/resources/js/app.js'));

        $env = File::get($this->kit.'/.env');
        $this->assertStringContainsString('DESIGN_MOTIF="accent-bar"', $env);
        $this->assertStringContainsString('DESIGN_SECOND_KIND="serif"', $env);
        $this->assertStringContainsString('APP_NAME=Finance', $env);
    }

    #[Test]
    public function applying_twice_changes_nothing_and_check_says_so(): void
    {
        $this->artisan('design:apply', ['--style' => 'dashcore'])->assertSuccessful();

        $this->artisan('design:apply', ['--style' => 'dashcore', '--check' => true])
            ->expectsOutputToContain('Nothing to change.')
            ->assertSuccessful();
    }

    #[Test]
    public function an_unknown_style_is_refused_and_nothing_is_written(): void
    {
        $this->artisan('design:apply', ['--style' => 'neon'])
            ->expectsOutputToContain('No built-in style called neon')
            ->assertFailed();

        $this->assertFileDoesNotExist($this->kit.'/design/design.json');
    }

    #[Test]
    public function update_follows_the_style_the_app_already_has(): void
    {
        $this->artisan('design:apply', ['--style' => 'dashcore'])->assertSuccessful();
        File::put($this->kit.'/resources/css/app.css', "@import 'tailwindcss';\n");

        // The audit is not registered here; update only needs to have re-applied.
        $this->artisan('design:update')->expectsOutputToContain('Using the dashcore style');

        $this->assertStringContainsString('dist/dashcore.css', File::get($this->kit.'/resources/css/app.css'));
    }
}
