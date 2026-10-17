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

    #[Test]
    public function a_vite_fonts_array_nobody_renders_is_called_out_not_trusted(): void
    {
        File::put($this->kit.'/vite.config.js', "export default {\n    plugins: [laravel({\n        fonts: [\n            bunny('Instrument Sans', { weights: [400] }),\n        ],\n    })],\n};\n");
        File::ensureDirectoryExists($this->kit.'/resources/views/layouts');
        File::put($this->kit.'/resources/views/layouts/app.blade.php', "<html><head>@vite(['resources/css/app.css'])</head></html>\n");

        $this->artisan('design:apply', ['--style' => 'dashcore'])
            ->expectsOutputToContain('no view renders @fonts')
            ->assertSuccessful();

        // Left alone: rewriting a list nothing renders would look like a fix.
        $this->assertStringContainsString('Instrument Sans', File::get($this->kit.'/vite.config.js'));
    }

    #[Test]
    public function a_bunny_link_in_any_head_is_found_not_only_the_partial(): void
    {
        File::ensureDirectoryExists($this->kit.'/resources/views/layouts/partials');
        $head = $this->kit.'/resources/views/layouts/partials/head.blade.php';
        File::put($head, "<link rel=\"stylesheet\" href=\"https://fonts.bunny.net/css?family=schibsted-grotesk:400\">\n");

        $this->artisan('design:apply', ['--style' => 'dashcore'])->assertSuccessful();

        $this->assertStringContainsString('inter', File::get($head));
        $this->assertStringNotContainsString('schibsted', File::get($head));
    }

    #[Test]
    public function the_fonts_component_needs_nothing_and_says_nothing(): void
    {
        File::ensureDirectoryExists($this->kit.'/resources/views/partials');
        File::put($this->kit.'/resources/views/partials/head.blade.php', "<x-dashcore::fonts />\n");

        $this->artisan('design:apply', ['--style' => 'dashcore'])
            ->doesntExpectOutputToContain('Put <x-ds::fonts />')
            ->assertSuccessful();
    }
}
