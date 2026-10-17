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

    #[Test]
    public function an_app_on_a_style_takes_its_title_face_from_the_style_not_its_environment(): void
    {
        // Found on finance: DESIGN_SECOND_KIND was in .env.example only, so the
        // config default, mono, drew the page title in the body face and the
        // stat values in Fraunces, the wrong way round.
        $root = sys_get_temp_dir().'/design-kit-provider-'.bin2hex(random_bytes(4));
        mkdir($root.'/design', 0777, true);
        file_put_contents($root.'/design/design.json', json_encode(['schema' => 'dashcore-design/1', 'style' => 'dashcore']));

        $original = $this->app->basePath();
        config(['brand.second_kind' => 'mono', 'brand.motif' => 'eyebrow']);

        try {
            $this->app->setBasePath($root);
            (new DesignKitServiceProvider($this->app))->register();
        } finally {
            $this->app->setBasePath($original);
            @unlink($root.'/design/design.json');
            @rmdir($root.'/design');
            @rmdir($root);
        }

        $this->assertSame('serif', config('brand.second_kind'));
        $this->assertSame('accent-bar', config('brand.motif'));
        $this->assertFalse(config('brand.condensed'));
    }

    #[Test]
    public function an_app_on_a_design_of_its_own_keeps_its_environment(): void
    {
        config(['brand.second_kind' => 'mono']);

        // This app's root has no design/design.json naming a style.
        (new DesignKitServiceProvider($this->app))->register();

        $this->assertSame('mono', config('brand.second_kind'));
    }
}
