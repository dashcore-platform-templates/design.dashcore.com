<?php

namespace Tests\Unit;

use App\Design\Design;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The badge is the one custom component with a rule that is easy to get wrong,
 * because the tempting mistake is to give a category a colour. The tool does not
 * boot the package's provider, so the component path is registered here.
 */
class DesignKitBadgeTest extends TestCase
{
    // A design is a row now, and the migration seeds the five in
    // database/seeders/designs as it creates the table.
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Blade::anonymousComponentPath(base_path('packages/design-kit/resources/views/ds'), 'ds');
    }

    #[Test]
    public function a_badge_with_a_status_takes_that_status(): void
    {
        $html = Blade::render('<x-ds::badge status="danger">Failed</x-ds::badge>');

        $this->assertStringContainsString('var(--ds-danger-soft)', $html);
        $this->assertStringContainsString('var(--ds-danger)', $html);
        $this->assertStringContainsString('Failed', $html);
    }

    #[Test]
    public function a_badge_without_a_status_is_neutral(): void
    {
        $html = Blade::render('<x-ds::badge>Admin</x-ds::badge>');

        $this->assertStringContainsString('var(--ds-sunken)', $html);
        $this->assertStringContainsString('var(--ds-text-muted-on-sunken)', $html);
        $this->assertStringNotContainsString('-soft', $html, 'a category must not be painted a status colour');
    }

    #[Test]
    public function a_state_the_standard_has_no_colour_for_is_neutral_rather_than_a_guess(): void
    {
        // The first agent migration hit exactly this: an outcome set of
        // executed, failed, rejected and pending. Rejected is not danger. The
        // old default painted anything unrecognised blue, which said something
        // untrue about it.
        foreach (['rejected', 'retired', 'not-configured', ''] as $state) {
            $html = Blade::render('<x-ds::badge status="'.$state.'">Rejected</x-ds::badge>');
            $this->assertStringContainsString('var(--ds-sunken)', $html, "status={$state}");
            $this->assertStringNotContainsString('var(--ds-info', $html, "status={$state} must not fall back to a colour");
        }
    }

    #[Test]
    public function the_neutral_pair_is_one_the_standard_already_measures(): void
    {
        // Neutral is muted-on-sunken text on the sunken surface, which is a pair
        // in every design's contrast block, so it cannot drift below the floor
        // without design:check failing first.
        $pairs = array_column(Design::load('harbor-point')->tokens()['contrast']['pairs'], 'ratio', 'pair');

        foreach (['light', 'dark'] as $register) {
            $this->assertArrayHasKey("{$register}: text-muted-on-sunken on sunken", $pairs);
            $this->assertGreaterThanOrEqual(4.5, $pairs["{$register}: text-muted-on-sunken on sunken"]);
        }
    }
}
