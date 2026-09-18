<?php

namespace Tests\Feature;

use App\Design\Design;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Putting a design back that the tool does not hold.
 *
 * An app can be wearing one: made before it was saved here, or renamed, or
 * removed. ExecutiveOS is on a design called executive-os that no design here
 * is called any more, so its updates 404. The only other way to make a design
 * was to generate one, which needs a model to pick the decisions, would pick
 * different ones, and would repaint the app to fix a bookkeeping problem.
 */
class ImportDesignTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_design_an_app_is_wearing_comes_back_under_its_own_slug(): void
    {
        $exported = Design::load('harbor-point')->toArray();
        Design::load('harbor-point')->delete();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::designs.index')
            ->set('importing', json_encode($exported))
            ->call('import')
            ->assertHasNoErrors();

        $back = Design::load('harbor-point');

        // Same slug, so every app already pulling it keeps pulling it, and
        // nothing is repainted.
        $this->assertSame('harbor-point', $back->slug);
        $this->assertSame($exported['decisions'], $back->decisions);
        // The stamp says when the model chose, and choosing is not what
        // happened here, so it is carried rather than refreshed.
        $this->assertSame($exported['generated_at'], $back->generatedAt);
    }

    #[Test]
    public function what_comes_back_out_is_what_went_in(): void
    {
        // The point of the whole thing: a kit that pulls the reimported design
        // must get the bytes it had before.
        $before = Design::load('northline')->toJson();
        $exported = Design::load('northline')->toArray();
        Design::load('northline')->delete();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::designs.index')
            ->set('importing', json_encode($exported))
            ->call('import')
            ->assertHasNoErrors();

        $this->assertSame($before, Design::load('northline')->toJson());
    }

    #[Test]
    public function it_never_quietly_replaces_one(): void
    {
        // A design something is wearing is not a thing to overwrite because a
        // file was pasted at it.
        $exported = Design::load('harbor-point')->toArray();
        $exported['decisions']['accent']['value'] = '#ff0000';

        Livewire::actingAs(User::factory()->create())
            ->test('pages::designs.index')
            ->set('importing', json_encode($exported))
            ->call('import')
            ->assertHasErrors('importing');

        $this->assertNotSame('#ff0000', Design::load('harbor-point')->decision('accent'));
    }

    #[Test]
    public function rubbish_is_refused_with_a_reason(): void
    {
        $page = Livewire::actingAs(User::factory()->create())->test('pages::designs.index');
        // Counted rather than fixed at the number that ships today, which is a
        // number that moves every time a design is added.
        $before = Design::all()->count();

        foreach ([
            'not json at all',
            '{"slug": "no-schema", "decisions": {"accent": {"value": "#111111"}}}',
            '{"schema": "dashcore-design/1", "decisions": {}}',
            '{"schema": "dashcore-design/1", "slug": "fine", "decisions": []}',
            '{"schema": "dashcore-design/1", "slug": "Not A Slug", "decisions": {"accent": {"value": "#111111"}}}',
        ] as $bad) {
            $page->set('importing', $bad)->call('import')->assertHasErrors('importing');
        }

        $this->assertCount($before, Design::all(), 'nothing was written');
    }

    #[Test]
    public function tokens_in_the_file_are_not_believed(): void
    {
        // They are derived from the decisions. Reading them out of a file would
        // let an import carry colours the standard would never produce.
        $exported = Design::load('harbor-point')->toArray();
        Design::load('harbor-point')->delete();

        $exported['tokens']['light']['accent'] = '#ff0000';

        Livewire::actingAs(User::factory()->create())
            ->test('pages::designs.index')
            ->set('importing', json_encode($exported))
            ->call('import')
            ->assertHasNoErrors();

        $this->assertNotSame('#ff0000', Design::load('harbor-point')->tokens()['light']['accent']);
    }

    #[Test]
    public function a_stranger_cannot_import(): void
    {
        $exported = Design::load('harbor-point')->toArray();
        Design::load('harbor-point')->delete();

        Livewire::test('pages::designs.index')
            ->set('importing', json_encode($exported))
            ->call('import')
            ->assertForbidden();

        $this->assertFalse(Design::exists('harbor-point'));
    }

    #[Test]
    public function the_page_offers_it(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/designs')
            ->assertOk()
            ->assertSee('Import')
            ->assertSee('For an app wearing a design that is not here');
    }
}
