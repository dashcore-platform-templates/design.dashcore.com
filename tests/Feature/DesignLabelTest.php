<?php

namespace Tests\Feature;

use App\Design\Design;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A design has two names. The client is who it is for, which the mockups
 * wear and an applied app starts with. The label is what the board calls it,
 * so two designs for one client can be told apart without reading a slug.
 */
class DesignLabelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_label_is_the_name_when_one_is_given_and_the_client_otherwise(): void
    {
        $named = new Design('x', ['client' => 'Dashcore', 'name' => 'Dashcore CEO'], []);
        $unnamed = new Design('y', ['client' => 'Harbor Point Physical Therapy'], []);
        $blank = new Design('z', ['client' => 'Dashcore', 'name' => '  '], []);

        $this->assertSame('Dashcore CEO', $named->label());
        $this->assertSame('Dashcore', $named->name(), 'the brand the app wears is still the client');
        $this->assertSame('Harbor Point Physical Therapy', $unnamed->label());
        $this->assertSame('Dashcore', $blank->label(), 'whitespace is not a name');
    }

    #[Test]
    public function every_design_on_the_board_has_a_label_of_its_own(): void
    {
        // Five house directions share a client. The board is only useful if
        // none of them shares a label.
        $labels = Design::all()->map->label();

        $this->assertSame($labels->unique()->values()->all(), $labels->values()->all(), 'two designs answer to the same label');
    }

    #[Test]
    public function the_nav_and_the_board_show_labels_not_clients(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('designs.index'))
            ->assertOk()
            ->assertSee('Dashcore CEO')
            ->assertSee('Dashcore Brown')
            ->assertSee('ExecutiveOS');
    }

    #[Test]
    public function the_name_is_typed_on_the_design_page_and_kept_on_save(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test('pages::designs.show', ['slug' => 'harbor-point'])
            ->set('brief.name', 'Harbor Point, front desk')
            ->assertSet('dirty', true)
            ->call('save');

        $design = Design::load('harbor-point');

        $this->assertSame('Harbor Point, front desk', $design->label());
        $this->assertSame('Harbor Point Physical Therapy', $design->name(), 'the client did not move');
    }
}
