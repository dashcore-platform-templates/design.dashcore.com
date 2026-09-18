<?php

namespace Tests\Feature;

use App\Design\Design;
use App\Models\Site;
use App\Models\SiteReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Retiring a design without breaking the apps using it, and knowing which apps
 * those are rather than warning about hypothetical ones.
 */
class ArchiveDesignTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function archiving_takes_it_off_the_board_and_leaves_the_pack_serving(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test('pages::designs.index')
            ->call('archive', 'northline');

        $this->assertTrue(Design::load('northline')->isArchived());
        $this->assertNotContains('northline', Design::all()->pluck('slug'));
        $this->assertContains('northline', Design::archived()->pluck('slug'));

        // The whole point. Nothing downstream notices.
        $this->get('/designs/northline')->assertOk();
        $this->get('/designs/northline/pack.json')->assertOk();
        $this->get('/designs/northline/design.json')->assertOk();

        // Off the board and out of the nav, for everybody. Asserted on the
        // card's own link rather than on the client's name, which any other
        // design is free to mention in its brief.
        $this->get('/designs')->assertOk()->assertDontSee('designs/northline"', escape: false);
    }

    #[Test]
    public function an_archived_design_comes_back(): void
    {
        $page = Livewire::actingAs(User::factory()->create())
            ->test('pages::designs.index')
            ->call('archive', 'northline');

        $page->set('showArchived', true)->assertSee('designs/northline"', escape: false);

        $page->call('restore', 'northline');

        $this->assertFalse(Design::load('northline')->isArchived());
        $this->assertContains('northline', Design::all()->pluck('slug'));
    }

    #[Test]
    public function the_modal_names_the_apps_using_it(): void
    {
        $this->reportUsing('dashcoretech/newproject.dashcore.com', 'harbor-point');
        $this->reportUsing('dashcoretech/other.dashcore.com', 'harbor-point');

        Livewire::actingAs(User::factory()->create())
            ->test('pages::designs.index')
            ->call('ask', 'harbor-point')
            // The count and the repositories, from the fleet rather than from a
            // guess in the copy.
            ->assertSee('Used by 2 apps')
            ->assertSee('dashcoretech/newproject.dashcore.com')
            ->assertSee('dashcoretech/other.dashcore.com')
            // Delete is not the button on offer when something is using it.
            ->assertSee('Archive instead')
            ->assertDontSee('>Delete</flux:button>', false);
    }

    #[Test]
    public function a_design_something_uses_cannot_be_deleted_even_from_below_the_page(): void
    {
        $this->reportUsing('dashcoretech/newproject.dashcore.com', 'harbor-point');

        // forget() is a public Livewire method, so hiding the button is not the
        // guard. This is.
        Livewire::actingAs(User::factory()->create())
            ->test('pages::designs.index')
            ->call('ask', 'harbor-point')
            ->call('forget');

        $this->assertTrue(Design::exists('harbor-point'));
    }

    #[Test]
    public function only_the_newest_report_counts(): void
    {
        // A repo that moved to another design last week is not still using the
        // old one.
        $site = Site::factory()->create(['repository' => 'dashcoretech/moved.dashcore.com']);
        SiteReport::factory()->for($site)->create(['design_slug' => 'harbor-point', 'reported_at' => now()->subWeek()]);
        SiteReport::factory()->for($site)->create(['design_slug' => 'northline', 'reported_at' => now()]);

        $usage = Site::usage();

        $this->assertFalse($usage->has('harbor-point'));
        $this->assertSame(['dashcoretech/moved.dashcore.com'], $usage->get('northline')->all());
    }

    #[Test]
    public function the_design_the_tool_uses_cannot_be_archived(): void
    {
        // Its tokens are on :root in every layout. Hiding it would leave the nav
        // lying about what is here.
        $this->assertFalse(Design::load(Design::OWN)->archivable());

        Livewire::actingAs(User::factory()->create())
            ->test('pages::designs.index')
            ->call('archive', Design::OWN);

        $this->assertFalse(Design::load(Design::OWN)->isArchived());

        $this->expectException(InvalidArgumentException::class);
        Design::load(Design::OWN)->archive();
    }

    #[Test]
    public function a_stranger_can_neither_archive_nor_restore(): void
    {
        Livewire::test('pages::designs.index')->call('archive', 'northline')->assertForbidden();
        Livewire::test('pages::designs.index')->call('restore', 'northline')->assertForbidden();

        $this->assertFalse(Design::load('northline')->isArchived());
    }

    #[Test]
    public function saving_a_design_does_not_quietly_unarchive_it(): void
    {
        $design = Design::load('northline');
        $design->archive();

        $design->decisions['radius']['value'] = 'round';
        $design->save();

        $this->assertTrue(Design::load('northline')->isArchived());
    }

    private function reportUsing(string $repository, string $slug): void
    {
        $site = Site::factory()->create(['repository' => $repository]);

        SiteReport::factory()->for($site)->create([
            'design_slug' => $slug,
            'reported_at' => now(),
        ]);
    }
}
