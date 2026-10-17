<?php

namespace Dashcore\DesignKit\Tests;

use Dashcore\DesignKit\Audit;
use PHPUnit\Framework\Attributes\Test;

class AuditTest extends TestCase
{
    /** @return list<string> */
    private function rules(string $blade): array
    {
        return array_values(array_unique(array_map(fn (array $f): string => $f['rule'], Audit::file($blade))));
    }

    /** @return list<string> */
    private function rulesAt(string $blade, string $path): array
    {
        return array_values(array_unique(array_map(fn (array $f): string => $f['rule'], Audit::file($blade, $path))));
    }

    #[Test]
    public function a_view_already_on_the_standard_reports_nothing(): void
    {
        $blade = <<<'BLADE'
        <x-ds::page-header :title="__('Today')" :description="__('Everything since yesterday.')">
            <flux:button variant="primary">New item</flux:button>
        </x-ds::page-header>
        <div class="p-4 text-(--ds-text) border border-(--ds-border) gap-6">
            <p class="text-xs font-medium text-(--ds-text-muted)">Label</p>
            <x-ds::badge status="success">Done</x-ds::badge>
        </div>
        BLADE;

        $this->assertSame([], Audit::file($blade));
    }

    #[Test]
    public function it_names_each_way_a_view_leaves_the_standard(): void
    {
        $blade = <<<'BLADE'
        <h1>Today</h1>
        <div class="p-5 mt-10 font-bold text-red-600 bg-amber-50" style="color:#ff0000">
            <span class="text-[10px]">tiny</span>
            <flux:badge color="green">Done</flux:badge>
            <flux:icon name="folder-git-2" />
        </div>
        BLADE;

        $rules = $this->rules($blade);

        foreach (['page title not in the page header', 'spacing off the scale', 'weight off the scale', 'colour off the roles', 'hardcoded colour', 'text under 12px', 'Flux badge', 'Lucide icon name'] as $expected) {
            $this->assertContains($expected, $rules, "should have reported: {$expected}");
        }
    }

    #[Test]
    public function a_line_can_opt_out(): void
    {
        $blade = '<div class="p-5 font-bold"> {{-- @ds-audit-ignore the brand mark is fixed --}}';

        $this->assertSame([], Audit::file($blade));
    }

    #[Test]
    public function the_mechanical_pass_fixes_weights_and_spacing_and_nothing_else(): void
    {
        $blade = '<div class="p-5 mt-10 gap-7 font-bold text-red-600"><flux:badge>x</flux:badge></div>';

        [$out, $changed] = Audit::mechanical($blade);

        $this->assertStringContainsString('p-4', $out);
        $this->assertStringContainsString('mt-8', $out);
        $this->assertStringContainsString('gap-6', $out);
        $this->assertStringContainsString('font-semibold', $out);
        $this->assertNotEmpty($changed);

        // Judgment is left alone: whether a red is a status needs a person.
        $this->assertStringContainsString('text-red-600', $out);
        $this->assertStringContainsString('<flux:badge>', $out);
    }

    #[Test]
    public function the_mechanical_pass_is_a_no_op_the_second_time(): void
    {
        [$once] = Audit::mechanical('<div class="p-5 font-bold">x</div>');
        [$twice] = Audit::mechanical($once);

        $this->assertSame($once, $twice);
    }

    #[Test]
    public function an_app_colour_variable_is_a_finding_and_the_styles_own_are_not(): void
    {
        $blade = '<div class="bg-(--color-surface) text-(--color-yellow-700) bg-[var(--color-navy)]">'
            .'<span class="text-(--color-zinc-500) bg-(--color-accent) text-(--color-accent-content) border-(--color-dc-border) text-(--ds-text)">x</span></div>';

        $found = array_column(array_filter(Audit::file($blade), fn ($f) => $f['rule'] === 'app colour variable'), 'snippet');

        $this->assertSame(['--color-surface', '--color-yellow-700', '--color-navy'], $found);
    }

    #[Test]
    public function a_partial_class_name_is_not_a_finding(): void
    {
        // p-10 is off the scale; padding-10 and p-1 are not, and neither is a
        // word that merely contains one.
        $blade = '<div class="p-1 px-4 gap-2 supports-[display:grid]:p-4">font-semibold</div>';

        $this->assertSame([], Audit::file($blade));
    }

    #[Test]
    public function a_sign_in_view_keeps_its_own_heading(): void
    {
        $blade = '<flux:heading size="xl">Log in to your account</flux:heading>';

        $this->assertSame(['page title not in the page header'], $this->rules($blade), 'anywhere else it is a page title');

        foreach (['resources/views/components/auth-header.blade.php', 'resources/views/pages/auth/login.blade.php', 'resources/views/layouts/auth/card.blade.php', 'resources\\views\\layouts\\auth.blade.php'] as $path) {
            $this->assertSame([], Audit::file($blade, $path), $path);
        }

        $this->assertNotSame([], Audit::file($blade, 'resources/views/pages/oauth/callback.blade.php'), 'only a whole auth segment counts');
        $this->assertNotSame([], Audit::file($blade, 'resources/views/pages/author.blade.php'));
    }

    #[Test]
    public function a_public_page_keeps_its_own_brand(): void
    {
        $hero = '<h1 class="text-5xl font-semibold text-(--ds-text-strong)">Run the clinic, not the paperwork</h1>';

        $this->assertSame(['page title not in the page header'], $this->rules($hero), 'an app screen still has to use the page header');

        // 13: the standard unifies the signed-in back end. A page a signed-out
        // person reaches keeps its own look, says so with the marker rather
        // than its folder, and the audit leaves all of it alone.
        $off = "{{-- @ds-public --}}\n".'<p class="p-5 font-bold text-gray-600 text-[10px]">Best in class solutions</p>'."\n".$hero;
        $this->assertSame([], Audit::file($off));
        $this->assertSame([], Audit::file($hero, 'resources/views/welcome.blade.php'), 'every Laravel app has one and it is always the landing page');
        $this->assertNotSame([], Audit::file($hero, 'resources/views/welcome-back.blade.php'), 'only that filename counts');

        // Sign-in is the door to the back end: on the roles, its heading no title.
        $this->assertSame([], Audit::file($hero, 'resources/views/livewire/auth/login.blade.php'));
        $this->assertSame(['colour off the roles'], $this->rulesAt('<p class="text-gray-600">Forgot it?</p>', 'resources/views/livewire/auth/login.blade.php'));
    }

    #[Test]
    public function an_accent_scale_of_the_apps_own_is_a_finding(): void
    {
        // team's teal accent-50 to accent-950 sat behind every button and
        // focus ring, and read as the style's accent to the palette rule.
        foreach (['<button class="bg-accent-600 hover:bg-accent-700 text-white">', '<input class="focus:ring-accent-500">', '<p class="dark:text-accent-400">', '<div style="color: var(--color-accent-600)">'] as $off) {
            $this->assertContains('brand colour scale', $this->rules($off), $off);
        }

        // The style's own accent names are not a scale.
        foreach (['<button class="bg-(--ds-accent) text-(--ds-on-accent)">', '<p class="text-accent-content">', '<div class="bg-accent text-accent-foreground">', '<i class="accent-(--ds-accent)">'] as $fine) {
            $this->assertNotContains('brand colour scale', $this->rules($fine), $fine);
        }
    }

    #[Test]
    public function a_flux_colour_the_style_cannot_turn_into_a_role_is_a_finding(): void
    {
        // Callouts and progress bars in a status family are corrected by the
        // style, and zinc is its neutral ramp.
        foreach ([
            '<flux:progress :value="$p" color="red" />',
            '<flux:progress :value="$p" :color="$over ? \'red\' : \'green\'" />',
            '<flux:callout color="amber" icon="clock">Late</flux:callout>',
            '<flux:button color="zinc">Cancel</flux:button>',
            '<flux:badge color="violet">Plan</flux:badge>',
        ] as $fine) {
            $this->assertNotContains('Flux colour off the roles', $this->rules($fine), $fine);
        }

        foreach ([
            '<flux:progress :value="$p" color="violet" />' => 'status family',
            '<flux:progress :value="$p" :color="$colour" />' => 'status family',
            '<flux:button variant="primary" color="green">Save</flux:button>' => 'variant="danger"',
            '<flux:navlist.badge color="blue">3</flux:navlist.badge>' => 'Drop color=',
            '<flux:text color="red">Overdue</flux:text>' => 'Drop color=',
        ] as $off => $fix) {
            $finding = array_values(array_filter(Audit::file($off), fn (array $f): bool => $f['rule'] === 'Flux colour off the roles'));
            $this->assertCount(1, $finding, $off);
            $this->assertStringContainsString($fix, $finding[0]['fix'], $off);
            $this->assertStringStartsWith('<flux:', $finding[0]['snippet']);
        }
    }

    #[Test]
    public function a_flux_heading_at_section_size_is_reported(): void
    {
        // Flux renders size="lg" as text-base at font-medium, which is 16px at
        // 500. 03 asks for 18px at 600, and the size is a plain utility class
        // on the element, so a class of your own does not reliably win.
        $this->assertSame(['Flux heading at section size'], $this->rules('<flux:heading size="lg">Recent activity</flux:heading>'));
        $this->assertSame(['Flux heading at section size'], $this->rules('<flux:heading level="2" size="lg" class="mb-2">Delete this?</flux:heading>'));

        // size="xl" is a page title, which the rule further down already names.
        $this->assertSame(['page title not in the page header'], $this->rules('<flux:heading size="xl">Today</flux:heading>'));

        // The default size is 14px at 500, which is the UI emphasis role in 03.
        $this->assertSame([], $this->rules('<flux:heading>Filters</flux:heading>'));
        $this->assertSame([], $this->rules('<x-ds::section-heading>Recent activity</x-ds::section-heading>'));
        $this->assertSame([], $this->rules('<flux:callout.heading>Card on file expires soon</flux:callout.heading>'), 'a callout heading is its own component');
    }

    #[Test]
    public function a_screen_restyling_the_flux_table_is_reported(): void
    {
        // The real one, off a kit built before the adapter carried the table.
        // Its own comment said it belonged in the adapter, and keeping both was
        // worse than either: the screen won on the head and the adapter won on
        // the hover, so the table matched neither.
        $blade = '<flux:table class="[&_[data-flux-cell]]:h-(--ds-row) [&_[data-flux-column]]:bg-(--ds-sunken)">';
        $this->assertSame(['Flux table styled on the screen'], $this->rules($blade));

        $this->assertSame(['Flux table styled on the screen'], $this->rules('<div class="[&_[data-flux-row]]:border-0">'));

        // A plain table is the whole point.
        $this->assertSame([], $this->rules('<flux:table><flux:table.column>Invoice</flux:table.column></flux:table>'));
        $this->assertSame([], $this->rules('<flux:table.cell align="end">{{ $invoice[\'amount\'] }}</flux:table.cell>'));

        // Other Flux internals are somebody else's argument, not 06's.
        $this->assertSame([], $this->rules('<div class="[&_[data-flux-field]]:mt-0">'));
    }

    #[Test]
    public function a_flux_pro_component_is_reported_with_its_free_stand_in(): void
    {
        $this->assertSame(['Flux Pro component'], $this->rules('<flux:date-picker wire:model="due" />'));
        $this->assertSame(['Flux Pro component'], $this->rules('<flux:accordion.item>'));
        $this->assertSame(['Flux Pro component'], $this->rules('<flux:tabs variant="segmented">'));
        $this->assertSame(['Flux Pro component'], $this->rules('<flux:select variant="listbox" wire:model="team">'));

        $finding = Audit::file('<flux:chart :value="$rows">')[0];
        $this->assertStringContainsString('06 has no chart', $finding['fix']);

        // Free components that share a prefix with a Pro one.
        $this->assertSame([], $this->rules('<flux:table><flux:table.column>Invoice</flux:table.column></flux:table>'));
        $this->assertSame([], $this->rules('<flux:input type="date" wire:model="due" />'));
        $this->assertSame([], $this->rules('<flux:select wire:model="team">'));
    }

    #[Test]
    public function the_prose_type_size_used_as_a_width_is_reported(): void
    {
        // Found by writing it. --ds-prose is 14px, so this is a column one word
        // wide, and the name reads like a measure rather than a size.
        $this->assertSame(['--ds-prose used as a length'], $this->rules('<div class="mx-auto max-w-(--ds-prose)">'));
        $this->assertSame(['--ds-prose used as a length'], $this->rules('<div class="w-(--ds-prose) h-(--ds-prose)">'));

        $this->assertSame([], $this->rules('<div class="mx-auto max-w-prose">'), '03 caps prose at 65 characters, which is this');
        $this->assertSame([], $this->rules('<p class="text-(length:--ds-prose)">'), 'as a type size it is what it is for');
        $this->assertSame([], $this->rules('<div class="p-(--ds-card) h-(--ds-row)">'), 'the other density tokens really are lengths');
    }

    #[Test]
    public function an_interactive_element_under_36px_is_reported(): void
    {
        foreach ([
            '<button class="h-8 px-3">Pay</button>',
            '<a href="#" class="size-6 grid place-items-center">x</a>',
            '<input class="h-[32px]" />',
            // Flux's own sizes: base is h-10 at 40px, sm is h-8 at 32px, xs is
            // h-6 at 24px. Both smaller ones are under the floor, and sm being
            // h-8 is the same 32 pixels the first case above already reports.
            '<flux:button size="xs">Pay</flux:button>',
            '<flux:button size="sm" variant="ghost">Cancel</flux:button>',
        ] as $blade) {
            $this->assertContains('tap target under 36px', $this->rules($blade), $blade);
        }

        $this->assertSame([], $this->rules('<flux:button>Pay</flux:button>'), 'the default size is h-10, which is 40px');
        $this->assertSame([], $this->rules('<flux:button size="base">Pay</flux:button>'));
    }

    #[Test]
    public function an_icon_inside_a_button_is_not_the_button(): void
    {
        // The common false positive. The icon is 20px and the button is 40px,
        // and matching the whole line would report every icon button there is.
        $blade = '<button class="h-10 px-3"><flux:icon name="plus" class="size-5" /></button>';

        $this->assertSame([], Audit::file($blade));

        // Prose links carry no height class at all, so they are never reported.
        $this->assertSame([], Audit::file('<p>Read the <a href="#" class="underline">notes</a>.</p>'));
    }

    #[Test]
    public function a_whole_file_can_opt_out(): void
    {
        $blade = "{{-- @ds-audit-ignore-file public landing page, composed outside the app standard --}}\n<h1 class=\"font-bold p-5 text-red-600\">Hello</h1>";

        $this->assertTrue(Audit::optsOut($blade));
        $this->assertSame([], Audit::file($blade));
    }
}
