<?php

namespace Dashcore\DesignKit\Tests;

use Dashcore\DesignKit\Applier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ApplierStyleTest extends TestCase
{
    private const LINES = "@import '../../vendor/dashcore/design-kit/dist/dashcore.css';\n@source '../../vendor/dashcore/design-kit/resources/views';";

    #[Test]
    public function the_style_goes_straight_after_tailwind_and_twice_is_a_no_op(): void
    {
        $kit = "@import 'tailwindcss';\n@import '../../vendor/livewire/flux/dist/flux.css';\n\nbody { color: red; }\n";

        $once = Applier::applyStyleCss($kit, 'dashcore');

        $this->assertStringStartsWith("@import 'tailwindcss';\n".self::LINES."\n@import '../../vendor/livewire/flux", $once);
        $this->assertSame($once, Applier::applyStyleCss($once, 'dashcore'));
    }

    #[Test]
    public function an_applied_design_block_goes_because_the_style_carries_it(): void
    {
        $kit = "@import 'tailwindcss';\n\n".Applier::START."\n@theme { --color-accent: red; }\n".Applier::END."\n\nbody {}\n";

        $css = Applier::applyStyleCss($kit, 'dashcore');

        $this->assertStringNotContainsString(Applier::START, $css);
        $this->assertStringNotContainsString('--color-accent: red', $css);
        $this->assertStringContainsString('body {}', $css);
    }

    #[Test]
    public function dashcore_ui_lines_are_replaced_not_doubled(): void
    {
        $kit = "@import 'tailwindcss';\n@import '../../vendor/dashcore/ui/resources/css/ui.css';\n@source '../../vendor/dashcore/ui/resources/views';\n";

        $css = Applier::applyStyleCss($kit, 'dashcore');

        $this->assertStringNotContainsString('dashcore/ui', $css);
        $this->assertSame("@import 'tailwindcss';\n".self::LINES."\n", $css);
    }

    #[Test]
    public function a_file_with_no_tailwind_import_gets_one(): void
    {
        $this->assertStringStartsWith("@import 'tailwindcss';\n".self::LINES, Applier::applyStyleCss("body {}\n", 'dashcore'));
    }

    #[Test]
    public function the_frame_script_replaces_dashcore_uis_and_is_added_once(): void
    {
        $line = "import '../../vendor/dashcore/design-kit/resources/js/frame.js';";

        $moved = Applier::applyStyleJs("import './bootstrap';\nimport '../../vendor/dashcore/ui/resources/js/ui.js';\n");
        $this->assertSame("import './bootstrap';\n{$line}\n", $moved);
        $this->assertSame($moved, Applier::applyStyleJs($moved));

        $this->assertSame("import './bootstrap';\n{$line}\n", Applier::applyStyleJs("import './bootstrap';\n"));
    }

    #[Test]
    public function the_env_carries_what_the_components_read_and_no_copy(): void
    {
        $tokens = json_decode((string) file_get_contents(__DIR__.'/../styles/dashcore.json'), true)['tokens'];

        $this->assertSame(
            ['DESIGN_MOTIF' => 'accent-bar', 'DESIGN_SECOND_KIND' => 'serif', 'DESIGN_CONDENSED' => 'false'],
            Applier::styleEnvValues($tokens, 'accent-bar'),
        );
    }
}
