<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\SiteReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FleetPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_nav_carries_the_fleet(): void
    {
        // It was reachable only by typing the URL, which is the same as not
        // existing.
        $this->actingAs(User::factory()->create())
            ->get('/designs')
            ->assertOk()
            ->assertSee('>Fleet<', false);
    }

    #[Test]
    public function nobody_can_sign_themselves_up(): void
    {
        // The page lists every Dashcore product and its failings, so accounts
        // are made with fleet:invite and not by whoever finds the form.
        $this->get('/register')->assertNotFound();
    }

    #[Test]
    public function the_pages_sign_in_sends_people_to_actually_render(): void
    {
        // Asserting the redirect target is not the same as loading it. Turning
        // registration off left the login view linking to route('register'),
        // so every sign-in was a 500 and the test above still passed.
        $this->get('/login')->assertOk()->assertSee('The Dashcore design standard.');
        $this->get('/forgot-password')->assertOk()->assertSee('We will email you a link to set a new one.');
    }

    #[Test]
    public function the_page_shows_what_each_site_last_reported(): void
    {
        $site = Site::factory()->create(['name' => 'ExecutiveOS', 'repository' => 'dashcoretech/executiveos.dashcore.com', 'design_slug' => 'dashcore-product-executiveos']);
        SiteReport::factory()->for($site)->withFindings(['tap target under 36px' => 6])->create();

        $this->actingAs(User::factory()->create())
            ->get('/fleet')
            ->assertOk()
            ->assertSee('ExecutiveOS')
            ->assertSee('executiveos.dashcore.com')
            ->assertSee('dashcore-product-executiveos')
            ->assertSee('Current')
            // "6 in 1" was a riddle: in one what. And the rules behind the
            // number are the thing somebody actually wants, so they are on the
            // cell rather than nowhere.
            ->assertSee('in 1 file')
            ->assertSee('tap target under 36px: 6');
    }

    #[Test]
    public function an_app_with_nothing_wrong_says_none(): void
    {
        $site = Site::factory()->create();
        SiteReport::factory()->for($site)->create();

        // Zero reads better as a word than as a nought under numbers in the
        // hundreds, and it is a different row from never having reported.
        $this->actingAs(User::factory()->create())
            ->get('/fleet')
            ->assertOk()
            ->assertSee('None')
            ->assertDontSee('Never reported');
    }

    #[Test]
    public function a_site_that_has_never_reported_says_so(): void
    {
        Site::factory()->create(['name' => 'Quiet App']);

        // Never reported is a different thing from nothing to report, and the
        // page has to tell them apart or a broken CI job looks like success.
        $this->actingAs(User::factory()->create())
            ->get('/fleet')
            ->assertOk()
            ->assertSee('Never reported');
    }

    #[Test]
    public function a_repo_added_before_it_is_set_up_says_not_applied(): void
    {
        $site = Site::factory()->create();

        // Nothing stops a repo being added here first, and then its CI reports
        // an audit of an app with no design/ at all. Both stamps come out of
        // that folder, so both are null, and the board used to read that as
        // "Unstamped" which means an app that is on the standard.
        SiteReport::factory()->for($site)->create([
            'standard_version' => null,
            'design_slug' => null,
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/fleet')
            ->assertOk()
            ->assertSee('Not applied')
            ->assertDontSee('Unstamped')
            // A row that reports a problem and stops there makes somebody go
            // and work out the next move. This one carries it.
            ->assertSee('Set it up')
            ->assertSee(route('designs.index'));
    }

    #[Test]
    public function a_site_behind_the_standard_shows_the_stamp_it_is_on(): void
    {
        $site = Site::factory()->create();
        SiteReport::factory()->for($site)->behind()->create();

        $this->actingAs(User::factory()->create())
            ->get('/fleet')
            ->assertOk()
            ->assertSee('deadbeef');
    }

    #[Test]
    public function forgetting_a_repository_takes_its_reports_with_it(): void
    {
        $site = Site::factory()->create();
        SiteReport::factory()->for($site)->count(2)->create();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::fleet')
            ->call('forget', $site->id);

        $this->assertSame(0, Site::query()->count());
        $this->assertSame(0, SiteReport::query()->count());
    }

    #[Test]
    public function a_new_token_retires_the_old_one(): void
    {
        // Only for apps not yet proving themselves to GitHub. One on the
        // current kit carries no token at all.
        $site = Site::factory()->create();
        $old = $site->issueToken();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::fleet')
            ->call('rotate', $site->id)
            ->assertSee('An app on the current kit needs none of this');

        $this->assertNull(Site::byToken($old));
    }

    #[Test]
    public function a_stranger_can_do_neither(): void
    {
        $site = Site::factory()->create();

        Livewire::test('pages::fleet')->call('forget', $site->id)->assertForbidden();
        Livewire::test('pages::fleet')->call('rotate', $site->id)->assertForbidden();

        $this->assertSame(1, Site::query()->count());
    }

    #[Test]
    public function an_app_on_the_style_shows_it_and_what_it_is_allowed(): void
    {
        $site = Site::factory()->create(['name' => 'Finance', 'repository' => 'dashcoretech/finance.dashcore.com', 'design_slug' => null]);
        $site->forceFill(['style' => 'dashcore'])->save();
        SiteReport::factory()->for($site)->withFindings(['hardcoded colour' => 12])->create(['design_slug' => null, 'style' => 'dashcore', 'allowance' => 40]);

        $this->actingAs(User::factory()->create())
            ->get('/fleet')
            ->assertOk()
            ->assertSee('dashcore</span> style', false)
            ->assertSee('allowed 40')
            ->assertDontSee('Not applied');
    }
}
