<?php

namespace Tests\Feature;

use App\Design\Blocks;
use App\Design\Screens;
use App\Design\Standard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The screens a design does not decide, one pasted prompt each.
 *
 * What is worth holding is that a prompt cannot go stale in the two ways it
 * could: naming a standard section that is not there, or naming a design.
 */
class BlocksPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_page_lists_every_block_with_its_prompt(): void
    {
        $page = $this->actingAs(User::factory()->create())->get('/blocks');

        $page->assertOk();

        foreach (Blocks::ALL as $block) {
            $page->assertSee($block['label']);
        }

        // Sign-in has no prompt, and its absence has to read as a decision
        // rather than as something forgotten.
        $page->assertSee('Sign-in is already built');
    }

    #[Test]
    public function every_block_is_drawn_and_not_only_described(): void
    {
        // Nobody chooses what to build next off a paragraph. One frame per
        // block, each a whole document, the same way the design page draws.
        $html = $this->actingAs(User::factory()->create())->get('/blocks')->getContent();

        $this->assertSame(count(Blocks::ALL), substr_count($html, '<iframe'));
    }

    #[Test]
    public function the_register_reaches_every_frame_at_once(): void
    {
        // A dark preview of one block beside a light preview of another would
        // be comparing two things nobody chose to compare.
        $html = Livewire::actingAs(User::factory()->create())
            ->test('pages::blocks')
            ->set('register', 'dark')
            ->html();

        $this->assertSame(
            count(Blocks::ALL),
            substr_count($html, 'lang=&quot;en&quot; class=&quot;dark&quot;'),
        );
    }

    #[Test]
    public function the_register_off_the_url_is_checked(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->withQueryParams(['register' => 'sepia'])
            ->test('pages::blocks')
            ->assertSet('register', 'light')
            ->assertOk();
    }

    #[Test]
    public function the_copy_button_carries_the_whole_prompt(): void
    {
        // The button is the point of the page, so what it holds is worth an
        // assertion rather than a look.
        $html = $this->actingAs(User::factory()->create())->get('/blocks')->getContent();

        $this->assertStringContainsString('Copy prompt', $html);
        $this->assertStringContainsString(e('do not quietly pick and move on'), $html);
    }

    #[Test]
    public function a_prompt_names_no_design(): void
    {
        // The repo already holds one in design/. A prompt naming a slug would
        // be wrong the moment that app's design changed.
        foreach (array_keys(Blocks::ALL) as $key) {
            $prompt = Blocks::prompt($key);

            $this->assertStringContainsString('design/design.json', $prompt);
            $this->assertStringNotContainsString('dashcore-marketing', $prompt);
            $this->assertStringNotContainsString('pack.json', $prompt);
        }
    }

    #[Test]
    public function every_section_a_prompt_names_is_really_in_the_standard(): void
    {
        // Nothing here restates a rule, so a prompt is only as good as the
        // sections it points at. A renamed or renumbered file would leave the
        // prompts pointing at nothing and nothing would say so.
        $files = collect(Standard::sections())->pluck('file');

        foreach (Blocks::ALL as $key => $block) {
            preg_match_all('/\b(\d{2})\b/', (string) $block['rules'], $m);

            $this->assertNotEmpty($m[1], "{$key} names no standard section");

            foreach ($m[1] as $number) {
                $this->assertTrue(
                    $files->contains(fn (string $file) => str_starts_with($file, $number.'-')),
                    "{$key} points at standard section {$number}, which does not exist",
                );
            }
        }
    }

    #[Test]
    public function every_block_previews_on_a_screen_that_exists(): void
    {
        // The Preview button goes to a design page with ?screen=, which is
        // checked against the list before it reaches a Blade include.
        foreach (Blocks::ALL as $key => $block) {
            $this->assertTrue(Screens::has((string) $block['screen']), "{$key} previews on a screen that is not drawn");
        }

        $this->assertTrue(Screens::has(Blocks::SHIPPED_ALREADY));
    }

    #[Test]
    public function a_prompt_carries_the_trap_for_its_own_screen(): void
    {
        // The one mistake each screen invites, which is the part a careful
        // reading of the standard does not hand you.
        $this->assertStringContainsString('never a second main nav', Blocks::prompt('settings'));
        $this->assertStringContainsString('danger colour', Blocks::prompt('errors'));
        $this->assertStringContainsString('@ds-public', Blocks::prompt('landing'));
        $this->assertStringContainsString('@ds-audit-ignore-file', Blocks::prompt('email'));
        $this->assertStringContainsString('settings and sign out', Blocks::prompt('shell'));
    }

    #[Test]
    public function the_screen_with_no_rules_says_so_and_asks_for_them_back(): void
    {
        // Settings is the screen Travis was pointing at. The prompt has to ask
        // for the decisions back, because those reports are how the missing
        // section gets written.
        $prompt = Blocks::prompt('settings');

        $this->assertStringContainsString('no section for settings', $prompt);
        $this->assertStringContainsString('do not quietly pick and move on', $prompt);

        // And nothing else claims a gap it does not have.
        foreach (['shell', 'errors', 'landing', 'email'] as $settled) {
            $this->assertNull(Blocks::ALL[$settled]['gap']);
        }
    }
}
