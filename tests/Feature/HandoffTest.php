<?php

namespace Tests\Feature;

use App\Design\Design;
use App\Design\Handoff;
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
