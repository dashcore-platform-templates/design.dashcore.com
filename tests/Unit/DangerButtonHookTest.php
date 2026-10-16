<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 06's danger button is an outline, written into a kit's CSS against the fill
 * class Flux gives its danger variant, because Flux marks the variant no other
 * way. A Flux release that renames that class would bring the red fill back on
 * every app with no error anywhere, so the hook is checked against the Flux
 * this repository installs.
 */
class DangerButtonHookTest extends TestCase
{
    #[Test]
    public function flux_still_marks_its_danger_button_with_the_class_the_outline_hooks_onto(): void
    {
        $button = file_get_contents(dirname(__DIR__, 2).'/vendor/livewire/flux/stubs/resources/views/flux/button/index.blade.php');

        $this->assertIsString($button);
        $this->assertMatchesRegularExpression("/'danger' => 'bg-red-500[ ']/", $button);
    }
}
