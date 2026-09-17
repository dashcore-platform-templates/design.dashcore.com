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

    #[Test]
    public function the_snippets_say_where_they_go(): void
    {
        // "On the job" and "after composer install" were true and useless:
        // both name a place somebody has to work out. These name a line that
        // is already in their file.
        $this->actingAs(User::factory()->create())
            ->get('/connect')
            ->assertOk()
            ->assertSee('Beside runs-on')
            ->assertSee('Last in steps')
            ->assertSee('.github/workflows');
    }

    #[Test]
    public function the_finished_example_contains_both_snippets(): void
    {
        // Composed from the same constants, so it cannot start showing a job
        // that does not match what the page told somebody to paste.
        $html = $this->actingAs(User::factory()->create())->get('/connect')->assertOk()->getContent();

        $this->assertStringContainsString('runs-on: ubuntu-latest', $html);

        foreach ([Workflow::PERMISSIONS, Workflow::STEP] as $block) {
            foreach (explode('
', trim($block)) as $line) {
                $this->assertStringContainsString(trim($line), $html);
            }
        }
    }

    #[Test]
    public function the_two_halves_are_told_apart_and_ordered(): void
    {
        // One is done once on a laptop, the other once per repository, and a
        // flat list of steps hid that. Somebody on their third repo should be
        // able to see the first half is already done.
        $this->actingAs(User::factory()->create())
            ->get('/connect')
            ->assertOk()
            ->assertSee('Your machine')
            ->assertSee('Once, ever')
            ->assertSee('Each repository')
            ->assertSee('Needs the token above')
            ->assertSee('lg:grid-cols-3', false);
    }

    #[Test]
    public function the_spine_is_drawn_with_a_rail_between_its_nodes(): void
    {
        // Three numbered steps and the outcome, joined. The first version built
        // the rail from before: utilities held in a PHP variable and Tailwind
        // never saw them, so it rendered as four unconnected circles and every
        // test still passed.
        $html = $this->actingAs(User::factory()->create())->get('/connect')->assertOk()->getContent();

        $this->assertSame(3, substr_count($html, 'bg-(--ds-accent-soft) text-(--ds-accent-content)'), 'three numbered nodes');
        $this->assertStringContainsString('bg-(--ds-success-soft)', $html, 'the outcome node');
        // One rail per node except the last.
        $this->assertSame(3, substr_count($html, 'absolute start-4 top-8 bottom-0 w-px bg-(--ds-border)'));
    }

    #[Test]
    public function it_answers_the_message_an_older_major_actually_prints(): void
    {
        // ExecutiveOS ran composer update, got "Nothing to modify in lock file"
        // because it requires ^4.0, and the page had nothing more to say. The
        // symptom is what somebody is looking at, so it is what the page names.
        $this->actingAs(User::factory()->create())
            ->get('/connect')
            ->assertOk()
            ->assertSee('Nothing to modify in lock file')
            ->assertSee('composer require &quot;dashcore/design-kit:5.*&quot;', false);
    }
}
