<?php

namespace Dashcore\DesignKit\Tests;

use Dashcore\DesignKit\Stylesheet;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StylesheetTest extends TestCase
{
    /** @return array<string,mixed> */
    private function tokens(): array
    {
        return json_decode((string) file_get_contents(__DIR__.'/../styles/dashcore.json'), true)['tokens'];
    }

    #[Test]
    public function the_shipped_stylesheet_is_what_the_shipped_tokens_build(): void
    {
        $this->assertSame(
            file_get_contents(__DIR__.'/../dist/dashcore.css'),
            Stylesheet::build('dashcore', $this->tokens()),
        );
    }

    #[Test]
    public function dark_arrives_three_ways_and_light_beats_the_os(): void
    {
        $css = Stylesheet::build('dashcore', $this->tokens());

        $this->assertStringContainsString('@media (prefers-color-scheme: dark)', $css);
        $this->assertStringContainsString(":root:not(.light):not([data-theme='light'])", $css);
        $this->assertStringContainsString(":root.dark, :root[data-theme='dark']", $css);
    }

    #[Test]
    public function the_ceiling_reaches_rounded_full_but_not_a_radio(): void
    {
        $css = Stylesheet::build('dashcore', $this->tokens());

        $this->assertStringContainsString('.rounded-full:not([data-flux-radio-indicator]', $css);
        $this->assertStringContainsString('[data-flux-switch] *) { border-radius: 4px; }', $css);
    }

    #[Test]
    public function the_app_keeps_the_source_line(): void
    {
        // Tailwind resolves @source against the file that says it. Only the
        // app's app.css knows where vendor/ is.
        $this->assertStringNotContainsString('@source', Stylesheet::build('dashcore', $this->tokens()));
    }

    #[Test]
    public function a_style_without_a_ceiling_does_not_clamp(): void
    {
        $tokens = $this->tokens();
        unset($tokens['radius_ceiling']);

        $this->assertStringNotContainsString('.rounded-full:not(', Stylesheet::build('plain', $tokens));
    }
}
