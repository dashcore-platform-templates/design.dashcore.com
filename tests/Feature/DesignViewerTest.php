<?php

namespace Tests\Feature;

use App\Design\Design;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ZipArchive;

class DesignViewerTest extends TestCase
{
    #[Test]
    public function the_pages_render_without_a_login(): void
    {
        $this->get('/')->assertRedirect('/designs');
        $this->get('/designs')->assertOk()->assertSee('Every design in the fleet');
        $this->get('/standard')->assertOk()->assertSee('The Dashcore Standard');
        $this->get('/designs/dashcore')->assertOk()->assertSee('Download pack');
        $this->get('/designs/does-not-exist')->assertNotFound();
    }

    #[Test]
    public function every_shipped_design_resolves_and_passes_contrast(): void
    {
        $this->assertGreaterThanOrEqual(1, Design::all()->count());

        foreach (Design::all() as $design) {
            $tokens = $design->tokens();
            $this->assertTrue($tokens['contrast']['passes'], "{$design->slug} fails contrast");
            $this->assertCount(11, $tokens['neutral']);
        }
    }

    #[Test]
    public function the_json_export_carries_both_layers(): void
    {
        $response = $this->get('/designs/harbor-point/design.json')->assertOk();
        $json = $response->json();

        $this->assertSame(Design::SCHEMA, $json['schema']);
        $this->assertArrayHasKey('decisions', $json);
        $this->assertArrayHasKey('tokens', $json);
        $this->assertSame('warm', $json['decisions']['temperature']['value']);
        $this->assertSame($json['tokens']['neutral']['50'], $json['tokens']['light']['sunken']);
    }

    #[Test]
    public function the_pack_holds_the_four_files(): void
    {
        $response = $this->get('/designs/harbor-point/pack.zip')->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $this->assertEqualsCanonicalizing(
            ['design/design.json', 'design/STANDARD.md', 'design/CLAUDE.snippet.md', 'design/MIGRATION.md'],
            $names,
        );
        $this->assertStringContainsString('Harbor Point', $zip->getFromName('design/CLAUDE.snippet.md'));
        $this->assertStringContainsString('# 01. Principles', $zip->getFromName('design/STANDARD.md'));
        $this->assertStringNotContainsString('# 00. Audit', $zip->getFromName('design/STANDARD.md'));
        $zip->close();
    }

    #[Test]
    public function the_contrast_command_passes_for_every_design(): void
    {
        $this->artisan('design:check')->assertSuccessful();
    }
}
