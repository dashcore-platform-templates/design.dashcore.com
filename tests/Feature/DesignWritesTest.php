<?php

namespace Tests\Feature;

use App\Design\Design;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Reaching a design page is one gate. Changing a design is another.
 *
 * Both pages shipped with no gate at all, so anyone who found the site could
 * repaint a design every kit pulls, or spend a model call per press on the
 * generator. The pages went behind the login afterwards; these hold the gate
 * underneath it, because a Livewire method can be called past the markup.
 */
class DesignWritesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_stranger_cannot_repaint_a_design(): void
    {
        $before = Design::load('harbor-point')->decision('accent');

        Livewire::test('pages::designs.show', ['slug' => 'harbor-point'])
            ->set('decisions.accent.value', '#ff0000')
            ->call('save')
            ->assertForbidden();

        $this->assertSame($before, Design::load('harbor-point')->decision('accent'));
    }

    #[Test]
    public function a_stranger_cannot_spend_a_model_call(): void
    {
        Livewire::test('pages::designs.show', ['slug' => 'harbor-point'])
            ->call('regenerate')
            ->assertForbidden();

        $this->get('/designs/new')->assertRedirect('/login');
    }

    #[Test]
    public function the_controls_are_drawn_for_the_people_who_reach_the_page(): void
    {
        // Nobody signed out reaches the page at all now, which
        // DashboardIsPrivateTest holds. The checks above are the second gate,
        // and the one that matters: a Livewire method is callable past the
        // markup that hides its button.
        $this->actingAs(User::factory()->create())
            ->get('/designs/harbor-point')
            ->assertOk()
            ->assertSee('wire:click="save"', false)
            ->assertSee('>New design<', false);
    }

    #[Test]
    public function signed_in_the_save_goes_through(): void
    {
        $before = Design::load('harbor-point')->decision('accent');

        try {
            Livewire::actingAs(User::factory()->create())
                ->test('pages::designs.show', ['slug' => 'harbor-point'])
                ->set('decisions.accent.value', '#0b5d57')
                ->call('save')
                ->assertOk();

            $this->assertSame('#0b5d57', Design::load('harbor-point')->decision('accent'));
        } finally {
            // The file is real and in git, so put it back whatever happened.
            $design = Design::load('harbor-point');
            $design->decisions['accent']['value'] = $before;
            $design->save();
        }
    }
}
