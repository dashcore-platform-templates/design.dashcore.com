<?php

namespace Tests\Feature;

use App\Models\User;
use Dashcore\DesignKit\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The guide, and only the guide.
 *
 * There is no form here any more because there is nothing to register: a
 * repository joins the board the first time it reports, proving which one it
 * is with a token GitHub mints for the job. Nothing is stored in the
 * repository, so nothing has to be issued here and pasted over there.
 */
class ConnectPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_snippets_are_the_ones_the_checker_looks_for(): void
    {
        // The whole point. A guide telling somebody to write something
        // design:connect does not recognise is worse than no guide, so the
        // page renders the package's own constants rather than a copy that
        // drifts the first time either changes.
        $page = $this->actingAs(User::factory()->create())->get('/connect')->assertOk();

        foreach (explode("\n", Workflow::PERMISSIONS) as $line) {
            $page->assertSee(trim($line));
        }

        $page->assertSee('design:audit --report');
        $page->assertSee('github.event.repository.default_branch');
    }

    #[Test]
    public function it_says_there_is_nothing_to_register(): void
    {
        // Somebody arriving here looking for an Add button should find out why
        // there isn't one rather than conclude the page is broken.
        $this->actingAs(User::factory()->create())
            ->get('/connect')
            ->assertOk()
            ->assertSee('Nothing to fill in here')
            ->assertDontSee('wire:submit', false);
    }

    #[Test]
    public function it_names_the_permission_mistake_that_costs_the_most_time(): void
    {
        // Naming any permission turns the defaults off, so a job that asks for
        // id-token alone cannot check itself out. The job still runs, which is
        // what makes it expensive to find.
        $this->actingAs(User::factory()->create())
            ->get('/connect')
            ->assertOk()
            ->assertSee('contents: read')
            ->assertSee('cannot check itself out');
    }

    #[Test]
    public function it_sends_people_to_their_account_for_the_one_credential_left(): void
    {
        // A person's token, for the commands a person runs. It never goes in
        // the repository or in CI, and the page says so.
        $this->actingAs(User::factory()->create())
            ->get('/connect')
            ->assertOk()
            ->assertSee('DESIGN_TOKEN')
            ->assertSee(route('account'))
            ->assertSee('never in CI');
    }

    #[Test]
    public function it_ends_at_the_command_that_checks_the_work(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/connect')
            ->assertOk()
            ->assertSee('php artisan design:connect')
            ->assertSee(route('fleet'));
    }
}
