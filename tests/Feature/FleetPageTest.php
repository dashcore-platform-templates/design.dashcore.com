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
    public function the_fleet_is_behind_sign_in_and_the_design_pages_are_not(): void
    {
        $this->get('/fleet')->assertRedirect('/login');

        // A kit pulls its pack without a session, so nothing below may move
        // behind the login with it.
        $this->get('/designs')->assertOk();
        $this->get('/designs/dashcore/pack.json')->assertOk();
        $this->get('/standard')->assertOk();
    }

    #[Test]
    public function the_nav_links_to_the_fleet_only_for_people_who_can_open_it(): void
    {
        // It was reachable only by typing the URL, which is the same as not
        // existing. And a link that always bounces to sign-in teaches people
        // the nav lies, so signed-out visitors do not get one.
        $this->get('/designs')->assertOk()->assertDontSee('>Fleet<', false);

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
        $this->get('/login')->assertOk()->assertSee('Log in to your account');
        $this->get('/forgot-password')->assertOk();
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
            ->assertDontSee('Unstamped');
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
    public function adding_a_site_issues_a_token_once(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test('pages::fleet')
            ->set('name', 'New Project')
            ->set('repository', 'dashcoretech/newproject.dashcore.com')
            ->call('add')
            ->assertHasNoErrors()
            // The token is shown once, so this is the one moment somebody has
            // everything in front of them. It names the repository, where the
            // secrets go, and the step to add, rather than saying "that app's
            // CI" and leaving them to work out where that is.
            ->assertSee('Connect dashcoretech/newproject.dashcore.com')
            ->assertSee('Settings → Secrets and variables → Actions', false)
            ->assertSee('DESIGN_TOKEN')
            ->assertSee('DESIGN_SOURCE')
            ->assertSee('design:audit --report')
            // The route is a link, not a set of directions to follow.
            ->assertSee('https://github.com/dashcoretech/newproject.dashcore.com/settings/secrets/actions', false)
            // The CI step contains ${{ }}, which is GitHub's syntax and Blade's
            // too. @verbatim escaped it and stopped copy-block compiling with
            // it, so the whole snippet rendered on the page as raw markup.
            ->assertSee('secrets.DESIGN_TOKEN')
            ->assertDontSee('<x-copy-block', false)
            // A row says what a repository's default branch looks like. Without
            // this a pull request build overwrites it with a branch nobody has
            // merged, and the board quietly stops meaning anything.
            ->assertSee('github.ref_name == github.event.repository.default_branch')
            // The repo half is agent work, so there is a prompt for it. It
            // names the repository it is talking about, refuses the token the
            // way the setup prompt refuses the Composer and Flux ones, and
            // stops at the one step a person has to do in a browser.
            ->assertSee('Agent prompt')
            ->assertSee('Connect this app to the Dashcore design fleet board')
            ->assertSee('dashcoretech/newproject.dashcore.com')
            ->assertSee('Never ask me for the token value');

        $site = Site::query()->sole();
        $this->assertSame('dashcoretech/newproject.dashcore.com', $site->repository);
        $this->assertNotEmpty($site->token_hash);
    }

    #[Test]
    public function a_repository_is_owner_slash_name_and_is_only_added_once(): void
    {
        Site::factory()->create(['repository' => 'dashcoretech/taken.dashcore.com']);

        Livewire::actingAs(User::factory()->create())
            ->test('pages::fleet')
            ->set('name', 'Taken')
            ->set('repository', 'dashcoretech/taken.dashcore.com')
            ->call('add')
            ->assertHasErrors(['repository']);

        Livewire::actingAs(User::factory()->create())
            ->test('pages::fleet')
            ->set('name', 'Odd')
            ->set('repository', 'executiveos.dashcore.com')
            ->call('add')
            ->assertHasErrors(['repository']);

        $this->assertSame(1, Site::query()->count());
    }

    #[Test]
    public function issuing_a_new_token_retires_the_old_one(): void
    {
        $site = Site::factory()->create();
        $old = $site->issueToken();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::fleet')
            ->call('rotate', $site->id)
            ->assertSee('The token is shown once');

        $this->assertNull(Site::byToken($old));
    }

    #[Test]
    public function removing_a_site_takes_its_reports_with_it(): void
    {
        $site = Site::factory()->create();
        SiteReport::factory()->for($site)->count(2)->create();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::fleet')
            ->call('forget', $site->id);

        $this->assertSame(0, Site::query()->count());
        $this->assertSame(0, SiteReport::query()->count());
    }
}
