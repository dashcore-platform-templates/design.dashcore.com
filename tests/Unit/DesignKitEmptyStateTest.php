<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The empty state has two sizes. The first migration of Executive OS kept a
 * plain sentence on the dashboard rather than use this component, because one
 * fixed height pushed everything below it off the screen.
 */
class DesignKitEmptyStateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Blade::anonymousComponentPath(base_path('packages/design-kit/resources/views/ds'), 'ds');
    }

    #[Test]
    public function the_default_size_fills_a_region_and_keeps_its_button(): void
    {
        $html = Blade::render('<x-ds::empty title="Nothing here yet."><flux:button>New item</flux:button></x-ds::empty>');

        $this->assertStringContainsString('min-h-[320px]', $html);
        $this->assertStringContainsString('Nothing here yet.', $html);
        $this->assertStringContainsString('New item', $html);
    }

    #[Test]
    public function the_compact_size_drops_the_height_and_the_button(): void
    {
        $html = Blade::render('<x-ds::empty size="compact" title="Nothing waiting."><flux:button>New item</flux:button></x-ds::empty>');

        $this->assertStringNotContainsString('min-h-[320px]', $html);
        $this->assertStringContainsString('Nothing waiting.', $html);
        // 06 gives the button to the full size only, so a slot passed to compact
        // is dropped rather than squeezed into a panel that has no room for it.
        $this->assertStringNotContainsString('New item', $html);
    }

    #[Test]
    public function an_unknown_size_is_the_default_rather_than_a_broken_layout(): void
    {
        $html = Blade::render('<x-ds::empty size="small" title="Nothing here yet." />');

        $this->assertStringContainsString('min-h-[320px]', $html);
    }
}
