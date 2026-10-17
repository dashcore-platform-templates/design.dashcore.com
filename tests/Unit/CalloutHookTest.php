<?php

namespace Tests\Unit;

use Dashcore\DesignKit\Applier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The callout correction finds a status by the 50 step Flux writes into the
 * callout's own classes. If Flux stops writing it, every callout in the fleet
 * goes back to Tailwind's colours without a word, so this reads Flux's stub.
 */
class CalloutHookTest extends TestCase
{
    #[Test]
    public function flux_still_marks_each_callout_family_by_its_50_step(): void
    {
        $callout = file_get_contents(dirname(__DIR__, 2).'/vendor/livewire/flux/stubs/resources/views/flux/callout/index.blade.php');

        $this->assertIsString($callout);
        foreach (Applier::CALLOUT_FAMILIES as $families) {
            foreach ($families as $family) {
                $this->assertStringContainsString("[--callout-background:var(--color-{$family}-50)]", $callout, "Flux no longer marks a {$family} callout the way the correction expects");
            }
        }

        // The variants an app writes arrive as families.
        $this->assertMatchesRegularExpression("/'danger' => 'red'/", $callout);
        $this->assertMatchesRegularExpression("/'warning' => 'yellow'/", $callout);
        $this->assertMatchesRegularExpression("/'success' => 'green'/", $callout);
    }

    #[Test]
    public function every_status_callout_takes_its_roles(): void
    {
        $css = implode("\n", Applier::fluxCorrections());

        foreach (['danger', 'warning', 'success', 'info'] as $status) {
            $this->assertStringContainsString("--callout-background: var(--ds-{$status}-soft);", $css);
            $this->assertStringContainsString("border-inline-start: 2px solid var(--ds-{$status});", $css);
        }
        $this->assertStringContainsString("[data-flux-callout][class*='var(--color-yellow-50)']", $css);
    }
}
