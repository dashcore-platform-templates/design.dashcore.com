<?php

namespace Tests\Feature;

use App\Design\Design;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A design is a row. It was a JSON file in the repo until 17 September 2026.
 */
class DesignStorageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_move_is_invisible_to_a_kit(): void
    {
        // The fixtures were exported from the file-backed version and are what
        // the package tests itself against. A kit pulls exactly these bytes, so
        // any storage change that moves one of them is a change every applied
        // repo would see and none of them asked for.
        foreach (['harbor-point', 'northline'] as $slug) {
            $this->assertSame(
                file_get_contents(base_path("packages/design-kit/tests/fixtures/{$slug}.json")),
                Design::load($slug)->toJson(),
                "the {$slug} export moved"
            );
        }
    }

    #[Test]
    public function the_migration_brings_the_seeds_with_it(): void
    {
        // Not a seeder somebody has to remember to run. A fresh clone, a test
        // run and a deploy all get the designs from the migration itself.
        $slugs = Design::all()->pluck('slug');

        $this->assertSame('dashcore', $slugs->first(), 'the tool uses its own design, so it leads the nav');
        $this->assertCount(count(glob(database_path('seeders/designs/*.json'))), $slugs);
    }

    #[Test]
    public function a_slug_off_the_pattern_never_reaches_a_query(): void
    {
        // It arrives from a URL and it is also a file name inside an exported
        // pack, so it is checked in both directions.
        $this->assertFalse(Design::exists('../etc/passwd'));
        $this->assertFalse(Design::exists('Harbor Point'));
        $this->assertFalse(Design::valid("harbor'; drop table designs; --"));

        $this->expectException(InvalidArgumentException::class);
        Design::load('nothing-here');
    }

    #[Test]
    public function saving_twice_updates_the_row_rather_than_making_a_second(): void
    {
        $design = Design::load('harbor-point');
        $design->decisions['radius']['value'] = 'round';
        $design->save();

        $created = DB::table('designs')->where('slug', 'harbor-point')->value('created_at');

        $design->decisions['radius']['value'] = 'soft';
        $design->save();

        $this->assertSame(1, DB::table('designs')->where('slug', 'harbor-point')->count());
        $this->assertSame('soft', Design::load('harbor-point')->decision('radius'));
        // updateOrInsert would have rewritten this every time somebody changed
        // a radius, so the row would claim to be new on every save.
        $this->assertSame($created, DB::table('designs')->where('slug', 'harbor-point')->value('created_at'));
    }

    #[Test]
    public function a_save_does_not_restamp_when_the_model_chose(): void
    {
        // generated_at goes out in the export, so moving it on save would change
        // the pack's bytes and show a kit a change that was not one. It is kept
        // as the exact string the generator wrote, offset and all, which is why
        // the column is a string rather than a timestamp.
        $before = Design::load('harbor-point')->generatedAt;
        $this->assertNotNull($before);

        $design = Design::load('harbor-point');
        $design->decisions['density']['value'] = 'comfortable';
        $design->save();

        $this->assertSame($before, Design::load('harbor-point')->generatedAt);
    }

    #[Test]
    public function a_new_design_inserts_and_can_be_removed(): void
    {
        $design = new Design(
            slug: 'tidewater',
            brief: ['client' => 'Tidewater', 'shell' => 'sidebar'],
            // A form control hands the pairing back as a string; it is stored
            // as the id it is.
            decisions: ['pairing' => ['value' => '3'], 'accent' => ['value' => '  #1D6F8A  ']],
        );

        $design->save();

        $saved = Design::load('tidewater');
        $this->assertSame(3, $saved->decision('pairing'));
        $this->assertSame('#1d6f8a', $saved->decision('accent'));
        $this->assertNull($saved->generatedAt);

        $saved->delete();
        $this->assertFalse(Design::exists('tidewater'));
    }
}
