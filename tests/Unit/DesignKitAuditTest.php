<?php

namespace Tests\Unit;

use Dashcore\DesignKit\Audit;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DesignKitAuditTest extends TestCase
{
    /** @return list<string> */
    private function rules(string $blade): array
    {
        return array_values(array_unique(array_map(fn (array $f): string => $f['rule'], Audit::file($blade))));
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
    public function a_partial_class_name_is_not_a_finding(): void
    {
        // p-10 is off the scale; padding-10 and p-1 are not, and neither is a
        // word that merely contains one.
        $blade = '<div class="p-1 px-4 gap-2 supports-[display:grid]:p-4">font-semibold</div>';

        $this->assertSame([], Audit::file($blade));
    }
}
