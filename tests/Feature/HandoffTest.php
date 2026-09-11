<?php

namespace Tests\Feature;

use App\Design\Design;
use App\Design\Handoff;
use App\Design\Pack;
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
            ->assertSee('Prompt for the agent')
            ->assertSee('Once per machine')
            ->assertSee('Download pack');
    }
}
