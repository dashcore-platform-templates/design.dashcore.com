<?php

namespace Tests\Feature;

use App\Design\Design;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteDesignTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_asks_first_and_names_what_it_is_about_to_take(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test('pages::designs.index')
            ->assertSet('pending', null)
            ->call('ask', 'harbor-point')
            ->assertSet('pending', 'harbor-point')
            ->assertSee('Delete Harbor Point Physical Therapy?')
            // No fleet rows in this test, so the modal says what it knows rather
            // than warning about apps it has no evidence for.
            ->assertSee('Nothing reports using this');

        // Asking is not doing.
        $this->assertTrue(Design::exists('harbor-point'));
    }

    #[Test]
    public function confirming_removes_it(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test('pages::designs.index')
            ->call('ask', 'harbor-point')
            ->call('forget')
            ->assertSet('pending', null)
            ->assertDontSee('Harbor Point Physical Therapy');

        $this->assertFalse(Design::exists('harbor-point'));
    }

    #[Test]
    public function nothing_happens_without_a_confirmation_to_act_on(): void
    {
        // forget() is a public Livewire method, so it can be called straight at
        // without ask() ever running.
        $before = Design::all()->count();

        Livewire::actingAs(User::factory()->create())
            ->test('pages::designs.index')
            ->call('forget');

        // Counted rather than fixed at the number that ships today, which is a
        // number that moves every time a design is added.
        $this->assertCount($before, Design::all());
    }

    #[Test]
    public function a_stranger_cannot_delete_anything(): void
    {
        Livewire::test('pages::designs.index')
            ->call('ask', 'harbor-point')
            ->assertForbidden();

        Livewire::test('pages::designs.index')
            ->call('forget')
            ->assertForbidden();

        $this->assertTrue(Design::exists('harbor-point'));
    }

    #[Test]
    public function the_control_is_on_the_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/designs')
            ->assertOk()
            ->assertSee('wire:click="ask(', false);
    }

    #[Test]
    public function the_design_the_tool_uses_cannot_be_deleted(): void
    {
        // Every layout puts its tokens on :root, so deleting it would 500 every
        // page in the app, sign-in included.
        $this->assertFalse(Design::load(Design::OWN)->deletable());

        $this->actingAs(User::factory()->create())
            ->get('/designs')
            ->assertOk()
            ->assertDontSee('wire:click="ask(\''.Design::OWN.'\')"', false);

        Livewire::actingAs(User::factory()->create())
            ->test('pages::designs.index')
            ->call('ask', Design::OWN)
            ->assertSet('pending', null);

        $this->assertTrue(Design::exists(Design::OWN));
    }

    #[Test]
    public function the_guard_holds_below_the_page_as_well(): void
    {
        // Not only hidden in the markup. Anything reaching for it directly gets
        // the same answer.
        $this->expectException(InvalidArgumentException::class);

        Design::load(Design::OWN)->delete();
    }

    #[Test]
    public function the_site_still_stands_after_a_delete(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test('pages::designs.index')
            ->call('ask', 'northline')
            ->call('forget');

        $this->get('/designs')->assertOk();
        $this->get('/designs/northline')->assertNotFound();
        // The pack a kit pulls goes with it, which is the thing the modal warns
        // about.
        $this->get('/designs/northline/pack.json')->assertNotFound();
        // Another page on the same layout, which is where a missing design
        // would take the whole app down.
        $this->get('/standard')->assertOk();
    }
}
