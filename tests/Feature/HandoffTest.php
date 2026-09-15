<?php

namespace Tests\Feature;

use App\Design\Design;
use App\Design\Handoff;
use App\Design\Pack;
use App\Design\Standard;
use Dashcore\DesignKit\Audit;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The handoff is what a person copies out of the design page and pastes into
 * Claude Code in the target repo. The panel, the walkthrough and the prompt
 * all come from one class, and these tests hold them together.
 */
class HandoffTest extends TestCase
{
    #[Test]
    public function the_prompt_gives_the_agent_everything_and_never_the_token(): void
    {
        $design = Design::load('harbor-point');
        $prompt = Handoff::prompt($design);

        $this->assertStringEndsWith('/designs/harbor-point/pack.json', Handoff::packUrl($design));
        $this->assertStringContainsString('Harbor Point', $prompt);
        $this->assertStringContainsString('composer require dashcore/design-kit', $prompt);
        // Flux Pro is not on Packagist, so the repository line has to travel with
        // the require or Composer reports a package that does not exist.
        $this->assertStringContainsString('composer config repositories.flux-pro composer '.Handoff::FLUX_REPO, $prompt);
        $this->assertStringContainsString('composer require livewire/flux-pro', $prompt);
        $this->assertStringContainsString('Never ask me for the licence key', $prompt);
        $this->assertStringContainsString('design:apply '.Handoff::packUrl($design).' --force', $prompt);
        $this->assertStringContainsString('design/CLAUDE.snippet.md', $prompt);
        $this->assertStringContainsString('design/MIGRATION.md', $prompt);
        $this->assertStringContainsString('one screen per commit', $prompt);
        $this->assertStringContainsString('Never ask for the token value', $prompt);
    }

    #[Test]
    public function the_walkthrough_lists_the_same_commands_as_the_panel(): void
    {
        $walkthrough = (string) file_get_contents(base_path('docs/walkthrough.md'));

        foreach (Handoff::commands('https://design.dashcore.com/designs/<slug>/pack.json') as $command) {
            $this->assertStringContainsString($command, $walkthrough, "docs/walkthrough.md step 6 is missing: {$command}");
        }
        $this->assertStringContainsString(Handoff::tokenCommand(), $walkthrough);
        $this->assertStringContainsString(Handoff::fluxCommand(), $walkthrough);

        // Order matters in a way nothing else would catch: Tailwind has to see
        // the Pro views before the build, or their classes never reach the CSS.
        $commands = Handoff::commands('<url>');
        $this->assertLessThan(
            array_search('npm run build', $commands, true),
            array_search('composer require livewire/flux-pro', $commands, true),
            'Flux Pro must be installed before the build'
        );
    }

    #[Test]
    public function the_guide_exempts_the_public_pages_before_the_pass_that_would_rewrite_them(): void
    {
        $guide = Pack::migration(Design::load('harbor-point'));

        $mark = strpos($guide, '@ds-audit-ignore-file');
        $fix = strpos($guide, 'design:audit --fix');

        $this->assertIsInt($mark);
        $this->assertIsInt($fix);
        // The first agent migration hit this: the mechanical pass ran over
        // everything in step 2 and step 4 then said those pages were out of
        // scope, so it rewrote the landing page it was about to exempt.
        $this->assertLessThan($fix, $mark, 'the mechanical pass would rewrite the pages the guide goes on to exempt');
    }

    #[Test]
    public function the_guide_and_the_audit_agree_about_zinc(): void
    {
        // design:apply redefines the whole zinc scale as this design's neutral
        // ramp, so zinc utilities already resolve to the design. The audit is
        // right to ignore them. The guide used to list them among the rewrites,
        // which made a file read as clean while the guide still called it drift.
        $this->assertSame([], Audit::file('<div class="text-zinc-500 bg-zinc-50 dark:bg-zinc-900 border-zinc-200">x</div>'));
        $this->assertStringContainsString('Zinc is already this design', Pack::migration(Design::load('harbor-point')));

        // A second grey family beside the design's own is drift, and still reported.
        $this->assertNotSame([], Audit::file('<div class="text-neutral-500">x</div>'));
        $this->assertNotSame([], Audit::file('<div class="bg-stone-100">x</div>'));
    }

    #[Test]
    public function the_design_page_offers_the_handoff(): void
    {
        $this->get('/designs/harbor-point')
            ->assertOk()
            ->assertSee('Use this design')
            ->assertSee('Agent prompt')
            ->assertSee('GitHub token')
            ->assertSee('Updating')
            ->assertSee('Download pack');
    }

    #[Test]
    public function the_update_path_is_not_the_setup_path(): void
    {
        $design = Design::load('harbor-point');
        $prompt = Handoff::updatePrompt($design);

        $this->assertSame([
            'composer require "dashcore/design-kit:^4.0"',
            'php artisan design:update harbor-point',
        ], Handoff::updateCommands($design));

        $this->assertStringContainsString('design/CHANGELOG.md', $prompt);
        $this->assertStringContainsString('Do not re-run the setup', $prompt);

        // The one-time steps must not appear, or somebody reinstalls an adapter
        // they already have and overwrites a sign-in layout they have edited.
        foreach (['composer require dashcore/design-kit', 'design:apply', 'CLAUDE.snippet.md'] as $setupOnly) {
            $this->assertStringNotContainsString($setupOnly, $prompt, "the update prompt must not repeat setup: {$setupOnly}");
        }
    }

    #[Test]
    public function the_changelog_has_its_own_page_carrying_the_update_snippet(): void
    {
        $this->get('/changelog')
            ->assertOk()
            ->assertSee(Standard::version())
            ->assertSee('What changed in the standard')
            ->assertSee('Picking a change up')
            ->assertSee('composer require "dashcore/design-kit:^4.0"')
            ->assertSee('php artisan design:update &lt;slug&gt;', false);
    }

    #[Test]
    public function the_standard_page_is_reference_and_links_out_rather_than_carrying_the_changelog(): void
    {
        $this->get('/standard')
            ->assertOk()
            ->assertSee(Standard::version())
            ->assertSee(route('changelog'))
            // Reading what moved is a different job from reading the rules, and
            // the changelog on this page made a long page longer.
            ->assertDontSee('What changed in the standard');
    }

    #[Test]
    public function the_update_snippet_reads_generically_without_a_design(): void
    {
        $this->assertSame([
            'composer require "dashcore/design-kit:^4.0"',
            'php artisan design:update <slug>',
        ], Handoff::updateCommands());

        $this->assertStringContainsString('already on the Dashcore standard', Handoff::updatePrompt());
        $this->assertStringContainsString('already on the Harbor Point', Handoff::updatePrompt(Design::load('harbor-point')));
    }
}
