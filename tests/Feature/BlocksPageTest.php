<?php

namespace Tests\Feature;

use App\Design\Blocks;
use App\Design\Design;
use App\Design\Standard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The blocks: the screens a design does not decide.
 *
 * A block owns its markup, in a component of its own, and both this page and a
 * design page draw the same one. What is worth holding is that the list, the
 * components and the prompts cannot come apart: a block with no component, a
 * prompt pointing at a standard section that is not there, or a prompt naming
 * a design would each be wrong in a way nothing else would report.
 */
class BlocksPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_page_lists_every_block(): void
    {
        $page = $this->actingAs(User::factory()->create())->get('/blocks');

        $page->assertOk();

        foreach (Blocks::ALL as $block) {
            $page->assertSee($block['label']);
        }
    }

    #[Test]
    public function every_block_has_a_component_of_its_own(): void
    {
        // The list and the components are two halves of one thing. A row here
        // with no file behind it is a switcher offering a screen that 500s.
        foreach (array_keys(Blocks::ALL) as $key) {
            $this->assertFileExists(
                resource_path("views/components/block/{$key}.blade.php"),
                "{$key} is on the list with no component behind it",
            );
        }
    }

    #[Test]
    public function a_block_draws_the_same_way_wherever_it_is_drawn(): void
    {
        // The blocks page and a design page render one component, so the
        // picture on a card and the picture on a design page cannot drift.
        $design = Design::load(Design::OWN);

        foreach (array_keys(Blocks::ALL) as $key) {
            $markup = Blocks::draw($key, $design);

            $this->assertNotSame('', trim($markup), "{$key} drew nothing");
        }
    }

    #[Test]
    public function a_key_off_the_url_cannot_reach_a_component_that_is_not_a_block(): void
    {
        // It ends up in a component name, so it is resolved against the list
        // rather than trusted.
        $this->assertSame(Blocks::DEFAULT, Blocks::resolve('pages.designs.show'));
        $this->assertSame(Blocks::DEFAULT, Blocks::resolve('block.parts.shell'));
        $this->assertSame(Blocks::DEFAULT, Blocks::resolve(''));
        $this->assertSame(Blocks::DEFAULT, Blocks::resolve(null));
        $this->assertSame('email', Blocks::resolve('email'));

        $this->actingAs(User::factory()->create())
            ->get('/designs/calder-street-veterinary?screen=pages.designs.show')
            ->assertOk()
            ->assertSee('Recent activity');
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
    public function the_three_nobody_builds_say_why_rather_than_going_quiet(): void
    {
        // A card with no button reads as something forgotten, and asking for a
        // prompt for one of these is a mistake worth an exception.
        $drawnOnly = array_diff_key(Blocks::ALL, Blocks::buildable());

        $this->assertSame(['sign-in', 'dashboard', 'components'], array_keys($drawnOnly));

        $page = $this->actingAs(User::factory()->create())->get('/blocks');

        foreach ($drawnOnly as $key => $block) {
            $this->assertNotNull($block['note'], "{$key} is built by nobody and does not say why");
            $page->assertSee($block['note']);

            try {
                Blocks::prompt($key);
                $this->fail("{$key} handed back a prompt for a screen nobody builds");
            } catch (InvalidArgumentException) {
                // What should happen.
            }
        }
    }

    #[Test]
    public function a_prompt_names_no_design(): void
    {
        // The repo already holds one in design/. A prompt naming a slug would
        // be wrong the moment that app's design changed.
        foreach (array_keys(Blocks::buildable()) as $key) {
            $prompt = Blocks::prompt($key);

            $this->assertStringContainsString('design/design.json', $prompt);
            $this->assertStringNotContainsString('dashcore-marketing', $prompt);
            $this->assertStringNotContainsString('pack.json', $prompt);
        }
    }

    #[Test]
    public function every_section_a_block_names_is_really_in_the_standard(): void
    {
        // Nothing here restates a rule, so a block is only as good as the
        // sections it points at. A renamed or renumbered file would leave them
        // pointing at nothing and nothing would say so.
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

    #[Test]
    public function the_copy_button_carries_the_whole_prompt(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/blocks')->getContent();

        $this->assertStringContainsString('Copy prompt', $html);
        $this->assertStringContainsString(e('do not quietly pick and move on'), $html);
    }
}
