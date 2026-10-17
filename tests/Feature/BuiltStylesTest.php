<?php

namespace Tests\Feature;

use App\Console\Commands\DesignBuildStylesCommand;
use App\Design\Pack;
use App\Design\Standard;
use App\Design\Styles;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A style is built here and shipped in the package. These hold the two ends
 * together: what is committed is what a build makes, and what the build makes
 * is what standard/14 says.
 */
class BuiltStylesTest extends TestCase
{
    #[Test]
    public function the_committed_styles_are_what_a_build_makes(): void
    {
        foreach (Styles::names() as $name) {
            [$json, $css] = DesignBuildStylesCommand::build($name);

            $this->assertSame($json, file_get_contents(DesignBuildStylesCommand::jsonPath($name)), "{$name}.json is stale: run php artisan design:build-styles");
            $this->assertSame($css, file_get_contents(DesignBuildStylesCommand::cssPath($name)), "{$name}.css is stale: run php artisan design:build-styles");
        }
    }

    #[Test]
    public function the_shipped_standard_is_the_standard_today(): void
    {
        $this->assertSame(Pack::standardFile(), file_get_contents(DesignBuildStylesCommand::standardPath()), 'packages/design-kit/STANDARD.md is stale: run php artisan design:build-styles');
    }

    #[Test]
    public function every_style_passes_the_floor(): void
    {
        foreach (Styles::names() as $name) {
            $this->assertTrue(Styles::get($name)['contrast']['passes'], $name);
        }
    }

    #[Test]
    public function the_roles_are_the_ones_section_14_lists(): void
    {
        $section = file_get_contents(base_path('standard/14-dashcore.md'));
        $tokens = Styles::get('dashcore');

        foreach (['light', 'dark'] as $register) {
            foreach ($tokens[$register] as $role => $hex) {
                if ($role === 'accent-soft') {
                    continue;
                }
                $this->assertStringContainsString("`{$hex}`", $section, "{$register} {$role}");
            }
        }
    }

    #[Test]
    public function the_generator_never_sees_the_styles_values(): void
    {
        // 14's exceptions are for the dashcore style alone. Above the marker a
        // client design could reach for them.
        $prompt = Standard::prompt();

        foreach (['#4a4438', '#f7f4ef', '#d9cbb0', 'radius_ceiling'] as $value) {
            $this->assertStringNotContainsString($value, $prompt);
        }
    }

    #[Test]
    public function the_tool_wears_the_style(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/standard')
            ->assertOk()
            ->assertSee('--ds-page: #f7f4ef;', false)
            ->assertSee('fonts.bunny.net/css?family=inter:400,500,600|fraunces:500,600', false);
    }
}
