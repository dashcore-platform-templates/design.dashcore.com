<?php

namespace Dashcore\DesignKit\Tests;

use Dashcore\DesignKit\Applier;
use Dashcore\DesignKit\Contrast;
use PHPUnit\Framework\Attributes\Test;

/**
 * The package's applier is pure string work, so it is tested here against the
 * real exported shape of a shipped design rather than a hand-made fixture.
 */
class ApplierTest extends TestCase
{
    #[Test]
    public function the_exported_file_is_accepted_and_its_contrast_agrees_with_the_tool(): void
    {
        $design = $this->exported();
        $tokens = Applier::tokens($design);

        $ours = Contrast::measure($tokens);
        $this->assertTrue($ours['passes']);
        $this->assertSame($design['tokens']['contrast']['passes'], $ours['passes']);

        // The package carries its own copy of the pair list so a kit can check a
        // design without the tool. Two copies drift, so compare them by name: a
        // pair added in one and not the other is the gate quietly weakening.
        $theirs = array_column($design['tokens']['contrast']['pairs'], 'pair');
        $mine = array_column($ours['pairs'], 'pair');
        $this->assertSame([], array_values(array_diff($theirs, $mine, ['light: border-strong on surface', 'dark: border-strong on surface'])), 'the tool measures pairs the kit does not');
        $this->assertSame([], array_values(array_diff($mine, $theirs)), 'the kit measures pairs the tool does not');
    }

    #[Test]
    public function a_file_without_a_tokens_layer_is_refused(): void
    {
        $this->expectExceptionMessage('no tokens layer');
        Applier::tokens(['schema' => 'dashcore-design/1', 'decisions' => []]);
    }

    #[Test]
    public function the_css_block_carries_the_flux_seam_and_the_roles(): void
    {
        $block = Applier::cssBlock($this->exported()['tokens']);

        $this->assertStringStartsWith(Applier::START, $block);
        $this->assertStringEndsWith(Applier::END, $block);
        $this->assertStringContainsString("@source '../../vendor/dashcore/design-kit/resources/views';", $block, 'the kit must scan the package views or none of its classes exist');
        $this->assertStringContainsString('--color-zinc-500: #78716c;', $block);
        $this->assertStringContainsString("--font-sans: 'Atkinson Hyperlegible Next'", $block);
        $this->assertStringContainsString('--radius-lg: 8px;', $block);
        $this->assertStringContainsString('--ds-text-muted: #78716c;', $block);

        // 06 brought onto the Flux table. Without this every app that uses
        // flux:table drifts the same way, because Flux ships a 14px head in
        // body colour with no fill.
        $this->assertStringContainsString('[data-flux-table] thead [data-flux-column]', $block);
        $this->assertStringContainsString('[data-flux-table] tbody [data-flux-cell]', $block);
        $this->assertStringContainsString('height: var(--ds-row);', $block);
        // The table rules must stay outside any @layer. Unlayered styles beat
        // layered ones, which is how they win over Tailwind's utilities without
        // !important. Inside a layer the override would silently do nothing.
        $fromTable = substr($block, (int) strpos($block, '[data-flux-table]'));
        $this->assertStringNotContainsString('@layer', $fromTable);
        // The dark muted-text correction from 04: zinc-500 becomes the 400 step in dark.
        $this->assertMatchesRegularExpression('/\.dark \{[^}]*--color-zinc-500: #a8a29e;/s', $block);
    }

    #[Test]
    public function applying_css_appends_once_then_replaces_in_place(): void
    {
        $kit = "@import 'tailwindcss';\n\n@theme {\n    --font-sans: 'Instrument Sans';\n}\n";
        $tokens = $this->exported()['tokens'];

        $once = Applier::applyCss($kit, Applier::cssBlock($tokens));
        $this->assertStringStartsWith($kit, $once);
        $this->assertSame(1, substr_count($once, Applier::START));

        $twice = Applier::applyCss($once, Applier::cssBlock($tokens));
        $this->assertSame($once, $twice);

        $other = Applier::applyCss($once, Applier::cssBlock($this->exported('northline')['tokens']));
        $this->assertSame(1, substr_count($other, Applier::START));
        $this->assertStringContainsString("--font-sans: 'Hanken Grotesk'", $other);
        $this->assertStringNotContainsString('Atkinson', $other);
    }

    #[Test]
    public function the_vite_fonts_array_is_replaced_with_the_pairing(): void
    {
        $vite = "        laravel({\n            input: ['resources/css/app.css'],\n            refresh: true,\n            fonts: [\n                bunny('Instrument Sans', {\n                    weights: [400, 500, 600],\n                }),\n            ],\n        }),\n";
        $out = Applier::applyVite($vite, $this->exported()['tokens']);

        $this->assertStringNotContainsString('Instrument Sans', $out);
        $this->assertStringContainsString("bunny('Atkinson Hyperlegible Next', {", $out);
        $this->assertStringContainsString("bunny('Spline Sans Mono', {", $out);
        $this->assertStringContainsString('weights: [400, 500],', $out);
        $this->assertStringContainsString("            refresh: true,\n            fonts: [", $out);
        $this->assertSame($out, Applier::applyVite($out, $this->exported()['tokens']), 'second apply is a no-op');
    }

    #[Test]
    public function a_bunny_link_in_the_head_partial_is_pointed_at_the_pairing(): void
    {
        $head = "<link rel=\"preconnect\" href=\"https://fonts.bunny.net\">\n<link href=\"https://fonts.bunny.net/css?family=instrument-sans:400,500,600\" rel=\"stylesheet\" />\n@vite(['resources/css/app.css'])\n";
        $out = Applier::applyHead($head, $this->exported()['tokens']);

        $this->assertStringNotContainsString('instrument-sans', $out);
        $this->assertStringContainsString('family=atkinson-hyperlegible-next:400,500,600|spline-sans-mono:400,500&amp;display=swap" rel="stylesheet"', $out);
        $this->assertStringContainsString('<link rel="preconnect" href="https://fonts.bunny.net">', $out, 'the preconnect link is left alone');
        $this->assertSame($out, Applier::applyHead($out, $this->exported()['tokens']), 'second apply is a no-op');
    }

    #[Test]
    public function the_auth_layout_is_found_in_either_kit_layout(): void
    {
        $base = sys_get_temp_dir().'/design-kit-'.uniqid();
        $this->assertNull(Applier::authLayoutPath($base));

        mkdir("{$base}/resources/views/components/layouts", 0777, true);
        file_put_contents("{$base}/resources/views/components/layouts/auth.blade.php", '<x-layouts.auth.simple>{{ $slot }}</x-layouts.auth.simple>');
        $this->assertStringEndsWith('components'.DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR.'auth.blade.php', str_replace('/', DIRECTORY_SEPARATOR, (string) Applier::authLayoutPath($base)));

        mkdir("{$base}/resources/views/layouts", 0777, true);
        file_put_contents("{$base}/resources/views/layouts/auth.blade.php", '<x-layouts::auth.simple>{{ $slot }}</x-layouts::auth.simple>');
        $this->assertStringEndsWith('views'.DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR.'auth.blade.php', str_replace('/', DIRECTORY_SEPARATOR, (string) Applier::authLayoutPath($base)), 'the Laravel 13 location wins when both exist');
    }

    #[Test]
    public function sign_in_layouts_nothing_references_are_dead_once_the_auth_layout_is_ours(): void
    {
        $base = sys_get_temp_dir().'/design-kit-'.uniqid();
        mkdir("{$base}/resources/views/layouts/auth", 0777, true);
        mkdir("{$base}/resources/views/pages", 0777, true);

        $auth = "{$base}/resources/views/layouts/auth.blade.php";
        file_put_contents($auth, '<x-layouts.auth.simple>{{ $slot }}</x-layouts.auth.simple>');
        foreach (['simple', 'card', 'split'] as $name) {
            file_put_contents("{$base}/resources/views/layouts/auth/{$name}.blade.php", '<div>{{ $slot }}</div>');
        }
        file_put_contents("{$base}/resources/views/pages/legal.blade.php", '<x-layouts.auth.card>{{ $slot }}</x-layouts.auth.card>');

        $dead = array_map(fn (string $p) => basename($p), Applier::deadAuthLayouts($base, [$auth]));
        sort($dead);

        $this->assertSame(['simple.blade.php', 'split.blade.php'], $dead, 'card is still used by a page; simple is only used by the layout about to be replaced');
        $this->assertSame([], Applier::deadAuthLayouts("{$base}/nowhere"), 'no auth layout, nothing to say');
    }

    #[Test]
    public function the_starter_kit_lines_nobody_chose_are_fixed_on_apply(): void
    {
        // Real lines, taken from two kits' history. They were byte-identical in
        // both, which is the whole reason this belongs in the adapter: every
        // app starts from the same starter kit and inherits the same drift.
        $blade = <<<'BLADE'
        {{ $attributes->merge(['class' => 'text-sm text-gray-600']) }}
        <div {{ $attributes->merge(['class' => 'font-medium text-sm text-green-600']) }}>
        <div class="mr-10 w-full pb-4 md:w-[220px]">
        <div class="text-center text-sm text-gray-600">
        <p class="mt-2 text-sm text-gray-800">
        BLADE;

        $this->assertTrue(Applier::hasStarterKitDrift($blade));
        $fixed = Applier::fixStarterKit($blade);

        $this->assertStringContainsString("'class' => 'text-sm text-(--ds-text-muted)'", $fixed);
        $this->assertStringContainsString("'class' => 'text-sm font-medium text-(--ds-success)'", $fixed);
        $this->assertStringContainsString('class="mr-8 w-full pb-4 md:w-[220px]"', $fixed);
        $this->assertStringContainsString('class="mt-2 text-sm text-(--ds-text)"', $fixed);

        $this->assertStringNotContainsString('text-gray-', $fixed);
        $this->assertStringNotContainsString('text-green-', $fixed);
        $this->assertStringNotContainsString('mr-10', $fixed);

        // Applying a design twice changes nothing, and this is part of that.
        $this->assertSame($fixed, Applier::fixStarterKit($fixed));
        $this->assertFalse(Applier::hasStarterKitDrift($fixed));
    }

    #[Test]
    public function a_line_somebody_has_edited_is_left_alone(): void
    {
        // The match is byte for byte because an app that has touched one of
        // these lines has made a decision, and a decision is not drift. The
        // audit still reports it, and a person judges it.
        $edited = '<div class="text-center text-sm text-gray-600 mt-4">';

        $this->assertFalse(Applier::hasStarterKitDrift($edited));
        $this->assertSame($edited, Applier::fixStarterKit($edited));

        // And nothing outside the starter kit's own lines is touched.
        $ours = '<p class="text-sm text-gray-600">a line somebody wrote</p>';
        $this->assertSame($ours, Applier::fixStarterKit($ours));
    }

    #[Test]
    public function lucide_icon_names_become_heroicons_wherever_a_view_names_them(): void
    {
        $blade = "<flux:sidebar.item icon=\"folder-git-2\" href=\"#\">Repo</flux:sidebar.item>\n<flux:icon.book-open-text />\n@php \$icon = 'layout-grid'; @endphp\n<flux:icon name=\"chevrons-up-down\" />\n<p>book-open-text is also just text here</p>\n";

        $this->assertTrue(Applier::mentionsLucide($blade));
        $out = Applier::renameLucide($blade);

        $this->assertStringContainsString('icon="folder"', $out);
        $this->assertStringContainsString('<flux:icon.book-open />', $out);
        $this->assertStringContainsString("\$icon = 'squares-2x2';", $out);
        $this->assertStringContainsString('name="chevron-up-down"', $out);
        $this->assertStringContainsString('<p>book-open-text is also just text here</p>', $out, 'prose is left alone');
        $this->assertSame($out, Applier::renameLucide($out));
    }

    #[Test]
    public function env_lines_are_replaced_or_appended_and_quoted(): void
    {
        $env = "APP_NAME=Laravel\nBRAND_HEADLINE=\"Old words\"\n";
        $out = Applier::applyEnv($env, ['BRAND_HEADLINE' => 'Today\'s schedule, "all" clinics.', 'BRAND_EYEBROW' => 'Harbor Point', 'BRAND_FOOTER' => null]);

        $this->assertStringContainsString("APP_NAME=Laravel\n", $out);
        $this->assertStringContainsString('BRAND_HEADLINE="Today\'s schedule, \"all\" clinics."', $out);
        $this->assertStringContainsString('BRAND_EYEBROW="Harbor Point"', $out);
        $this->assertStringContainsString("BRAND_FOOTER=\n", $out);
        $this->assertSame(1, substr_count($out, 'BRAND_HEADLINE='));
    }

    #[Test]
    public function env_values_carry_the_copy_and_what_the_components_need(): void
    {
        $values = Applier::envValues($this->exported());

        $this->assertSame('Harbor Point Physical Therapy', $values['BRAND_NAME']);
        $this->assertSame("Today's schedule, all three clinics.", $values['BRAND_HEADLINE']);
        $this->assertSame('eyebrow', $values['DESIGN_MOTIF']);
        $this->assertSame('mono', $values['DESIGN_SECOND_KIND']);
        $this->assertNull($values['BRAND_LOGO']);
    }
}
