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
 * The block library: the screens a design does not decide.
 *
 * Two levels, and the split is the point. The index draws one face per
 * category and costs the same however many blocks there are; a category page
 * draws every block in the one you asked for. A flat page of everything is
 * what this replaced, and what it would quietly become again if the index
 * started drawing blocks instead of faces.
 */
class BlocksPageTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): self
    {
        $this->actingAs(User::factory()->create());

        return $this;
    }

    #[Test]
    public function the_index_lists_every_category(): void
    {
        $page = $this->signIn()->get('/blocks')->assertOk();

        foreach (Blocks::categories() as $category) {
            $page->assertSee($category['label']);
        }
    }

    #[Test]
    public function the_index_draws_one_face_per_category_and_not_one_per_block(): void
    {
        // The whole reason for two levels. An index that drew every block
        // would cost three megabytes at a hundred blocks and be dead long
        // before a thousand.
        $html = $this->signIn()->get('/blocks')->getContent();

        // One per category, whatever the blocks add up to. Every category
        // holds one today, so the two numbers happen to match; this starts
        // biting the first time a category holds two.
        $this->assertSame(count(Blocks::categories()), substr_count($html, '<iframe'));
    }

    #[Test]
    public function a_category_page_draws_every_block_in_it(): void
    {
        foreach (Blocks::categories() as $key => $category) {
            $html = $this->signIn()->get("/blocks/{$key}")->assertOk()->getContent();

            $this->assertSame(
                count($category['blocks']),
                substr_count($html, '<iframe'),
                "{$key} did not draw every block in it",
            );

            foreach ($category['blocks'] as $block) {
                $this->assertStringContainsString(e($block['label']), $html);
            }
        }
    }

    #[Test]
    public function a_category_nobody_has_is_a_404(): void
    {
        $this->signIn()->get('/blocks/not-a-category')->assertNotFound();
        $this->signIn()->get('/blocks/errors')->assertOk();
    }

    #[Test]
    public function every_block_sits_in_a_category_that_exists(): void
    {
        // The list of categories and the category on a block are two halves of
        // one thing. A block in a category nobody offers is a block nothing
        // links to and nobody would notice missing.
        foreach (Blocks::ALL as $key => $block) {
            $this->assertTrue(
                Blocks::hasCategory((string) $block['category']),
                "{$key} is in category {$block['category']}, which does not exist",
            );
        }

        foreach (array_keys(Blocks::CATEGORIES) as $category) {
            $this->assertNotEmpty(
                Blocks::inCategory($category),
                "{$category} is offered with nothing in it",
            );
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
        // The library and a design page render one component, so a picture in
        // one place and a picture in the other cannot drift.
        $design = Design::load(Design::OWN);

        foreach (array_keys(Blocks::ALL) as $key) {
            $this->assertNotSame('', trim(Blocks::draw($key, $design)), "{$key} drew nothing");
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

        $this->signIn()
            ->get('/designs/calder-street-veterinary?screen=pages.designs.show')
            ->assertOk()
            ->assertSee('Recent activity');
    }

    #[Test]
    public function a_width_is_per_block_and_checked_against_the_list(): void
    {
        // A landing page and an email are judged at different widths, so one
        // switch for the whole page would make one of them wrong.
        $page = Livewire::actingAs(User::factory()->create())
            ->test('pages::blocks.show', ['category' => 'errors'])
            ->call('setWidth', 'errors', 'mobile');

        $this->assertSame('mobile', $page->instance()->widthFor('errors'));
        $this->assertStringContainsString('width: 390px', $page->html());

        // Neither half is taken at its word: the width reaches a style
        // attribute and the block reaches a component name.
        $page->call('setWidth', 'errors', 'billboard');
        $this->assertSame('mobile', $page->instance()->widthFor('errors'));

        $page->call('setWidth', 'not-a-block', 'desktop');
        $this->assertSame('laptop', $page->instance()->widthFor('not-a-block'));
    }

    #[Test]
    public function the_register_reaches_every_frame_at_once(): void
    {
        // A dark preview of one block beside a light preview of another would
        // be comparing two things nobody chose to compare.
        $html = Livewire::actingAs(User::factory()->create())
            ->test('pages::blocks.index')
            ->set('register', 'dark')
            ->html();

        $this->assertSame(
            count(Blocks::categories()),
            substr_count($html, 'lang=&quot;en&quot; class=&quot;dark&quot;'),
        );
    }

    #[Test]
    public function the_register_off_the_url_is_checked(): void
    {
        foreach ([['pages::blocks.index', []], ['pages::blocks.show', ['category' => 'errors']]] as [$component, $params]) {
            Livewire::actingAs(User::factory()->create())
                ->withQueryParams(['register' => 'sepia'])
                ->test($component, $params)
                ->assertSet('register', 'light')
                ->assertOk();
        }
    }

    #[Test]
    public function the_three_nobody_builds_say_why_rather_than_going_quiet(): void
    {
        // A block with no Copy prompt reads as something forgotten, and asking
        // for a prompt for one of these is a mistake worth an exception.
        $drawnOnly = array_diff_key(Blocks::ALL, Blocks::buildable());

        $this->assertSame(['sign-in', 'dashboard', 'components'], array_keys($drawnOnly));

        foreach ($drawnOnly as $key => $block) {
            $this->assertNotNull($block['note'], "{$key} is built by nobody and does not say why");

            $this->signIn()
                ->get('/blocks/'.$block['category'])
                ->assertSee($block['note']);

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
        $html = $this->signIn()->get('/blocks/settings')->getContent();

        $this->assertStringContainsString('Copy prompt', $html);
        $this->assertStringContainsString(e('do not quietly pick and move on'), $html);
    }
}
