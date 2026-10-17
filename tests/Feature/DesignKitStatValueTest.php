<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * x-ds::stat-value is the value line of x-ds::stat for a tile an app builds
 * itself. The two have to set a value the same way, or a dashboard of hand
 * built tiles and one of x-ds::stat tiles stop matching.
 */
class DesignKitStatValueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Blade::anonymousComponentPath(base_path('packages/design-kit/resources/views/ds'), 'ds');
    }

    /** @return list<string> */
    private function classesOf(string $html, string $marker): array
    {
        preg_match('/<p class="([^"]*)"[^>]*>\s*'.preg_quote($marker, '/').'/', $html, $m);
        $this->assertNotEmpty($m, "no value line around {$marker}");

        $classes = array_values(array_filter(explode(' ', $m[1])));
        sort($classes);

        return $classes;
    }

    #[Test]
    public function it_sets_a_value_the_way_the_stat_tile_does(): void
    {
        foreach ([null, 'display', 'mono'] as $kind) {
            config(['brand.second_kind' => $kind]);

            $tile = $this->classesOf(Blade::render('<x-ds::stat label="Open" value="148" />'), '148');
            $alone = $this->classesOf(Blade::render('<x-ds::stat-value>148</x-ds::stat-value>'), '148');

            // The tile spaces its value from its label, and the value alone can
            // wrap where a long figure meets a narrow column. Nothing else may
            // differ.
            $this->assertSame(array_values(array_diff($alone, ['[overflow-wrap:anywhere]'])), array_values(array_diff($tile, ['mt-2'])), "second face kind {$kind}");
        }
    }

    #[Test]
    public function a_tone_is_a_status_and_anything_else_is_text_strong(): void
    {
        $this->assertContains('text-(--ds-danger)', $this->classesOf(Blade::render('<x-ds::stat-value tone="danger">-5</x-ds::stat-value>'), '-5'));
        $this->assertContains('text-(--ds-text-strong)', $this->classesOf(Blade::render('<x-ds::stat-value tone="red">7</x-ds::stat-value>'), '7'));
    }
}
