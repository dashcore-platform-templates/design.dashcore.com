<?php

namespace Tests\Feature;

use App\Design\Design;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Reading a design is open. Changing one is not.
 *
 * Both pages shipped with no gate at all, so anyone who found the site could
 * repaint a design every kit pulls, or spend a model call per press on the
 * generator.
 */
class DesignWritesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function looking_stays_open(): void
    {
        // A kit pulls its pack without a session, so none of this may move
        // behind the login with the writes.
        $this->get('/designs')->assertOk();
        $this->get('/designs/harbor-point')->assertOk();
        $this->get('/designs/harbor-point/pack.json')->assertOk();
        $this->get('/designs/harbor-point/design.json')->assertOk();
    }

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
    public function the_controls_are_only_offered_to_people_who_can_use_them(): void
    {
        $this->get('/designs/harbor-point')
            ->assertOk()
            ->assertDontSee('wire:click="save"', false)
            ->assertDontSee('wire:click="regenerate"', false);

        $this->get('/designs')->assertOk()->assertDontSee('>New design<', false);

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
