<?php

namespace Tests\Feature;

use App\Design\Design;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Two designs on one screen, for choosing between directions.
 *
 * The thing worth testing is that both frames really are drawn from their own
 * design and at the same settings. A page that quietly rendered one design
 * twice, or two designs at different widths, would look like it was working.
 */
class CompareDesignsTest extends TestCase
{
    use RefreshDatabase;

    private function page(array $params = [])
    {
        return Livewire::actingAs(User::factory()->create())
            ->withQueryParams($params)
            ->test('pages::designs.compare');
    }

    #[Test]
    public function both_designs_are_drawn_from_their_own_tokens(): void
    {
        // Two accents that are nothing alike, so finding both in the output
        // cannot be one design rendered twice.
        $this->page(['a' => 'dashcore', 'b' => 'northline'])
            ->assertSet('a', 'dashcore')
            ->assertSet('b', 'northline')
            // The accent each one resolves to, inside its own frame's CSS.
            ->assertSee('#274db4', escape: false)
            ->assertSee('#b5542d', escape: false);
    }

    #[Test]
    public function it_opens_on_the_two_directions_on_the_table(): void
    {
        $this->page()
            ->assertSet('a', 'northline')
            ->assertSet('b', 'dashcore-marketing');
    }

    #[Test]
    public function archiving_one_of_those_leaves_it_opening_on_something(): void
    {
        // The opening pair is an editorial choice about a question being asked
        // this week, so the page has to survive the answer.
        Design::load('northline')->archive();

        $page = $this->page();

        $page->assertSet('a', 'dashcore');
        $this->assertNotSame('dashcore', $page->get('b'));
        $this->assertNotSame('', $page->get('b'));
    }

    #[Test]
    public function a_link_naming_one_design_opens_it_beside_its_sibling(): void
    {
        // Two designs sharing a client are two answers to one question, so a
        // link to one of them lands on the pair rather than on the default.
        $this->page(['a' => 'dashcore'])
            ->assertSet('a', 'dashcore')
            ->assertSet('b', 'dashcore-marketing');
    }

    #[Test]
    public function choosing_the_design_already_opposite_swaps_the_two(): void
    {
        // Rather than leaving the page comparing something with itself.
        $this->page(['a' => 'dashcore', 'b' => 'northline'])
            ->set('a', 'northline')
            ->assertSet('a', 'northline')
            ->assertSet('b', 'dashcore');

        $this->page(['a' => 'dashcore', 'b' => 'northline'])
            ->set('b', 'dashcore')
            ->assertSet('b', 'dashcore')
            ->assertSet('a', 'northline');
    }

    #[Test]
    public function the_swap_button_reverses_them(): void
    {
        $this->page(['a' => 'dashcore', 'b' => 'northline'])
            ->call('swap')
            ->assertSet('a', 'northline')
            ->assertSet('b', 'dashcore');
    }

    #[Test]
    public function both_frames_are_the_same_screen_at_the_same_width(): void
    {
        // The comparison is worthless if the two sides differ in anything but
        // the design, so the screen and the width are page-wide, not per side.
        $page = $this->page(['a' => 'dashcore', 'b' => 'northline', 'screen' => 'sign-in', 'viewport' => 'mobile']);

        $page->assertSet('screen', 'sign-in')->assertSet('viewport', 'mobile');

        // One width, named twice: once per frame.
        $this->assertSame(2, substr_count($page->html(), 'width: 390px'));
    }

    #[Test]
    public function everything_off_the_url_is_checked_against_its_list(): void
    {
        // The screen reaches a Blade include and the slugs reach the database,
        // so none of these may be taken at their word.
        $this->page([
            'a' => '../../etc/passwd',
            'b' => 'no-such-design',
            'screen' => '../../../config/app',
            'register' => 'sepia',
            'viewport' => 'billboard',
        ])
            ->assertSet('a', 'northline')
            ->assertSet('screen', 'dashboard')
            ->assertSet('register', 'light')
            ->assertSet('viewport', 'desktop')
            ->assertOk();
    }

    #[Test]
    public function the_dark_register_applies_to_both_sides(): void
    {
        $html = $this->page(['a' => 'dashcore', 'b' => 'northline', 'register' => 'dark'])->html();

        // Each frame is a document of its own, and both carry the dark class.
        $this->assertSame(2, substr_count($html, 'lang=&quot;en&quot; class=&quot;dark&quot;'));
    }

    #[Test]
    public function the_marketing_direction_is_here_and_holds_the_websites_blue(): void
    {
        $this->page(['a' => 'dashcore', 'b' => 'dashcore-marketing'])
            ->assertSet('b', 'dashcore-marketing')
            ->assertSee('#2f81ff', escape: false);
    }
}
