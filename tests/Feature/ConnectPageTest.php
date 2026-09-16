<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\SiteReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Connecting a repository, and everything that used to sit on the fleet board.
 *
 * Setup inside the thing setup produces was the wrong way round, and it is
 * about to matter more: once a pack is only served to a repository that can
 * name itself, this page is what a design depends on rather than a tidy-up
 * afterwards.
 */
class ConnectPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function connecting_a_repository_issues_a_token_once(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test('pages::connect')
            ->set('name', 'New Project')
            ->set('repository', 'dashcoretech/newproject.dashcore.com')
            ->call('add')
            ->assertHasNoErrors()
            // The token is shown once, so this is the one moment somebody has
            // everything in front of them. It names the repository, where the
            // secrets go, and the step to add, rather than saying "that app's
            // CI" and leaving them to work out where that is.
            ->assertSee('Set up dashcoretech/newproject.dashcore.com')
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
            // The repo half is agent work, so there is a prompt for it. It names
            // the repository it is talking about, refuses the token the way the
            // setup prompt refuses the Composer and Flux ones, and stops at the
            // one step a person has to do in a browser.
            ->assertSee('Agent prompt')
            ->assertSee('dashcoretech/newproject.dashcore.com')
            ->assertSee('Never ask me for the token value')
            // The prompt refuses a repository with no design, which is how we
            // found out ExecutiveOS had never been set up. The page says so
            // rather than letting somebody open an agent to find out.
            ->assertSee('It expects a design applied already')
            ->assertSee(route('designs.index'));

        $site = Site::query()->sole();
        $this->assertSame('dashcoretech/newproject.dashcore.com', $site->repository);
        $this->assertNotEmpty($site->token_hash);
    }

    #[Test]
    public function the_secrets_come_before_the_prompt(): void
    {
        // The one step nobody else can do, and the one every step below depends
        // on. The agent is told never to ask for it, so it sits with the person
        // who has it rather than being discovered halfway through a run.
        $html = Livewire::actingAs(User::factory()->create())
            ->test('pages::connect')
            ->set('name', 'Fresh')
            ->set('repository', 'dashcoretech/fresh.dashcore.com')
            ->call('add')
            ->html();

        $this->assertLessThan(
            strpos($html, 'Then hand the rest to an agent'),
            strpos($html, "Put these in the repository's secrets"),
            'the prompt is offered before the thing it depends on'
        );
    }

    #[Test]
    public function a_repository_is_owner_slash_name_and_is_only_connected_once(): void
    {
        Site::factory()->create(['repository' => 'dashcoretech/taken.dashcore.com']);

        Livewire::actingAs(User::factory()->create())
            ->test('pages::connect')
            ->set('name', 'Taken')
            ->set('repository', 'dashcoretech/taken.dashcore.com')
            ->call('add')
            ->assertHasErrors(['repository']);

        Livewire::actingAs(User::factory()->create())
            ->test('pages::connect')
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
            ->test('pages::connect')
            ->call('rotate', $site->id)
            ->assertSee('Shown once');

        $this->assertNull(Site::byToken($old));
    }

    #[Test]
    public function disconnecting_takes_its_reports_with_it(): void
    {
        $site = Site::factory()->create();
        SiteReport::factory()->for($site)->count(2)->create();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::connect')
            ->call('disconnect', $site->id);

        $this->assertSame(0, Site::query()->count());
        $this->assertSame(0, SiteReport::query()->count());
    }

    #[Test]
    public function a_connected_repository_with_nothing_to_show_says_so(): void
    {
        // Connected and silent is normal for the first few minutes and wrong
        // after that. The row says nothing has arrived rather than that
        // something failed.
        Site::factory()->create(['repository' => 'dashcoretech/quiet.dashcore.com']);

        $this->actingAs(User::factory()->create())
            ->get('/connect')
            ->assertOk()
            ->assertSee('dashcoretech/quiet.dashcore.com')
            ->assertSee('Nothing yet');
    }

    #[Test]
    public function the_fleet_board_no_longer_edits_the_fleet(): void
    {
        // The board is a view. Everything that changes the fleet is here, and
        // the board points at it rather than carrying a second copy.
        $this->actingAs(User::factory()->create())
            ->get('/fleet')
            ->assertOk()
            ->assertDontSee('wire:click="rotate(', false)
            ->assertDontSee('name="add-site"', false)
            ->assertSee(route('connect'));
    }
}
