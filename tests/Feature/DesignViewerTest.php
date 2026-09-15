<?php

namespace Tests\Feature;

use App\Design\Design;
use App\Design\Pack;
use App\Design\Standard;
use Dashcore\DesignKit\Source;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ZipArchive;

class DesignViewerTest extends TestCase
{
    #[Test]
    public function the_pages_render_without_a_login(): void
    {
        $this->get('/')->assertRedirect('/designs');
        $this->get('/designs')->assertOk()->assertSee('Designs');
        $this->get('/standard')->assertOk()->assertSee('Standard');
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
    public function the_pack_manifest_is_what_a_kit_pulls(): void
    {
        $response = $this->get('/designs/harbor-point/pack.json')->assertOk();
        $manifest = $response->json();

        $this->assertSame(Source::MANIFEST_SCHEMA, $manifest['schema'], 'the tool and the package must agree on the schema name');
        $this->assertSame('harbor-point', $manifest['slug']);
        $this->assertStringEndsWith('/designs/harbor-point', $manifest['source']);

        // The package reads it back into the files that go under design/.
        $files = Source::files($manifest);
        $this->assertSame(['design.json', 'STANDARD.md', 'CHANGELOG.md', 'CLAUDE.snippet.md', 'MIGRATION.md'], array_keys($files));
        $this->assertArrayHasKey('tokens', json_decode($files['design.json'], true));
        $this->assertStringContainsString('Migrating Harbor Point', $files['MIGRATION.md']);

        // The stamp is how a kit finds out the standard moved under it. The tool
        // writes it and the package reads it back, so both halves are checked.
        $this->assertSame(Standard::version(), $manifest['standard_version']);
        $this->assertSame(Standard::version(), Source::standardVersion($files['STANDARD.md']));
        $this->assertSame(Pack::files(Design::load('harbor-point')), $files, 'byte for byte what the zip holds');
        $this->assertSame($manifest, $this->get('/designs/harbor-point/pack.json')->json(), 'pulling twice must give the same bytes, or design:apply is never a no-op');

        $this->get('/designs/does-not-exist/pack.json')->assertNotFound();
    }

    #[Test]
    public function the_pack_holds_its_five_files(): void
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
            ['design/design.json', 'design/STANDARD.md', 'design/CHANGELOG.md', 'design/CLAUDE.snippet.md', 'design/MIGRATION.md'],
            $names,
        );
        $this->assertStringContainsString('Harbor Point', $zip->getFromName('design/CLAUDE.snippet.md'));
        $standard = $zip->getFromName('design/STANDARD.md');
        $this->assertStringContainsString('# 01. Principles', $standard);
        $this->assertStringNotContainsString('# 00. Audit', $standard);
        // The pack carries the whole standard, not the cut prompt: an agent
        // building a screen needs the component rules the generator skips.
        $this->assertStringContainsString('flux:sidebar', $standard);
        $this->assertStringContainsString('prefers-reduced-motion', $standard);
        $this->assertStringNotContainsString(Standard::PROMPT_END, $standard);
        $this->assertGreaterThan(strlen(Standard::prompt()) * 2, strlen($standard));
        $zip->close();
    }

    #[Test]
    public function the_contrast_command_passes_for_every_design(): void
    {
        $this->artisan('design:check')->assertSuccessful();
    }
}
