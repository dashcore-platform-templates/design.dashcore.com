<?php

namespace Tests\Unit;

use App\Design\Color;
use App\Design\Neutrals;
use App\Design\Resolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ResolverTest extends TestCase
{
    private function decisions(string $accent = '#2d5bd7', string $temperature = 'pure', int $pairing = 1, string $radius = 'soft', string $density = 'compact', string $motif = 'eyebrow'): array
    {
        return [
            'accent' => ['value' => $accent, 'why' => ''],
            'temperature' => ['value' => $temperature, 'why' => ''],
            'pairing' => ['value' => $pairing, 'why' => ''],
            'radius' => ['value' => $radius, 'why' => ''],
            'density' => ['value' => $density, 'why' => ''],
            'motif' => ['value' => $motif, 'why' => ''],
            'copy' => ['eyebrow' => null, 'headline' => 'x', 'tagline' => 'y', 'footer' => null, 'why' => ''],
        ];
    }

    #[Test]
    public function contrast_matches_the_wcag_reference_values(): void
    {
        $this->assertSame(21.0, Color::contrast('#ffffff', '#000000'));
        $this->assertSame(1.0, Color::contrast('#777777', '#777777'));
        // Flux's default muted grey on its default dark surface, the number 04 cites.
        $this->assertSame(3.19, Color::contrast('#737373', '#262626'));
    }

    #[Test]
    #[DataProvider('temperatures')]
    public function every_text_role_passes_in_both_registers(string $temperature): void
    {
        $tokens = Resolver::resolve($this->decisions(temperature: $temperature));

        $this->assertTrue($tokens['contrast']['passes'], $temperature.' failed: '.json_encode(array_filter($tokens['contrast']['pairs'], fn ($p) => ! $p['passes'])));
        $this->assertGreaterThanOrEqual(4.5, $tokens['contrast']['lowest']['ratio']);
    }

    public static function temperatures(): array
    {
        return array_map(fn (string $t) => [$t], Neutrals::TEMPERATURES);
    }

    #[Test]
    public function dark_muted_text_is_the_400_step_not_flux_default_500(): void
    {
        $tokens = Resolver::resolve($this->decisions(temperature: 'cool'));

        $this->assertSame(Neutrals::scale('cool')[400], $tokens['dark']['text-muted']);
        $this->assertGreaterThanOrEqual(4.5, Color::contrast($tokens['dark']['text-muted'], $tokens['dark']['surface']));
    }

    #[Test]
    #[DataProvider('accents')]
    public function accent_text_roles_pass_for_any_accent(string $accent): void
    {
        $tokens = Resolver::resolve($this->decisions(accent: $accent));

        foreach (['light', 'dark'] as $register) {
            $r = $tokens[$register];
            $this->assertGreaterThanOrEqual(4.5, Color::contrast($r['on-accent'], $r['accent']), "$accent $register on-accent");
            $this->assertGreaterThanOrEqual(4.5, Color::contrast($r['accent-content'], $r['page']), "$accent $register accent-content");
            if ($register === 'dark') {
                $this->assertGreaterThanOrEqual(3.0, Color::contrast($r['accent'], $r['page']), "$accent dark accent as a fill on page");
            }
        }
    }

    public static function accents(): array
    {
        // Dark, mid, light, saturated, near-grey, and the band where neither white nor near-black text passes as given.
        return [['#1e1b4b'], ['#8fbc3f'], ['#f4c542'], ['#0f766e'], ['#6b7280'], ['#7c7c7c'], ['#b5542d']];
    }

    #[Test]
    public function a_very_dark_accent_is_lightened_for_the_dark_page_and_says_so(): void
    {
        $tokens = Resolver::resolve($this->decisions(accent: '#1e1b4b'));

        $this->assertNotSame('#1e1b4b', $tokens['dark']['accent']);
        $this->assertNotEmpty($tokens['contrast']['notes']);
    }

    #[Test]
    public function radius_and_density_come_from_the_standard_tables(): void
    {
        $tokens = Resolver::resolve($this->decisions(radius: 'sharp', density: 'comfortable'));

        $this->assertSame(['sm' => '2px', 'md' => '3px', 'lg' => '4px', 'xl' => '6px', '2xl' => '8px'], $tokens['radius']);
        $this->assertSame('32px', $tokens['density']['gutter']);
        $this->assertSame('44px', $tokens['density']['row']);
        $this->assertSame('16px', $tokens['density']['prose']);
    }

    #[Test]
    public function status_colours_are_fixed_and_pass_as_text_in_their_register(): void
    {
        $tokens = Resolver::resolve($this->decisions(temperature: 'warm'));

        foreach ($tokens['status'] as $name => $s) {
            $this->assertGreaterThanOrEqual(4.5, Color::contrast($s['text-light'], '#ffffff'), "$name light");
            $this->assertGreaterThanOrEqual(4.5, Color::contrast($s['text-dark'], $tokens['dark']['page']), "$name dark");
            $this->assertGreaterThanOrEqual(4.5, Color::contrast('#ffffff', $s['fill']), "$name fill");
        }
    }

    #[Test]
    #[DataProvider('temperatures')]
    public function text_on_a_status_tint_is_measured_and_passes(string $temperature): void
    {
        $tokens = Resolver::resolve($this->decisions(temperature: $temperature));
        $measured = array_column($tokens['contrast']['pairs'], 'ratio', 'pair');

        foreach (['success', 'warning', 'danger', 'info'] as $name) {
            foreach (['light', 'dark'] as $register) {
                foreach (["{$register}: {$name} text on {$name}-soft", "{$register}: text-muted-on-sunken on {$name}-soft"] as $pair) {
                    $this->assertArrayHasKey($pair, $measured, "not measured: {$pair}");
                    $this->assertGreaterThanOrEqual(4.5, $measured[$pair], "{$pair} in {$temperature}");
                }
            }
        }
    }

    #[Test]
    #[DataProvider('temperatures')]
    public function accent_text_passes_on_the_tint_it_actually_sits_on(string $temperature): void
    {
        $tokens = Resolver::resolve($this->decisions(temperature: $temperature));
        $measured = array_column($tokens['contrast']['pairs'], 'ratio', 'pair');

        foreach (['light' => 0.10, 'dark' => 0.16] as $register => $alpha) {
            $r = $tokens[$register];
            $tint = Color::over($r['accent'], $r['surface'], $alpha);

            // 04 puts this tint behind selected rows and active list items, so
            // accent text sits on it more often than on anything else. Deriving
            // against the page left dark at 3.40:1 with every design passing.
            $this->assertGreaterThanOrEqual(4.5, Color::contrast($r['accent-content'], $tint), "{$register} accent-content on the accent tint");
            $this->assertGreaterThanOrEqual(4.5, Color::contrast($r['text-muted-on-sunken'], $tint), "{$register} muted text on the accent tint");

            foreach (["{$register}: accent-content on accent-soft", "{$register}: text-muted-on-sunken on accent-soft"] as $pair) {
                $this->assertArrayHasKey($pair, $measured, "not measured: {$pair}");
            }

            // Passing on the tint has to mean passing on the lighter surfaces too.
            $this->assertGreaterThanOrEqual(4.5, Color::contrast($r['accent-content'], $r['page']));
            $this->assertGreaterThanOrEqual(4.5, Color::contrast($r['accent-content'], $r['surface']));
        }
    }

    #[Test]
    public function plain_muted_text_is_the_wrong_role_on_a_tint_which_is_why_the_rule_exists(): void
    {
        $tokens = Resolver::resolve($this->decisions(temperature: 'pure'));

        // The finding from the first agent migration: 04 sanctions a status tint
        // as a row background, and muted text at the 500 step fails on it. If
        // this ever starts passing, the tints or the ramp moved and the extra
        // role in 04 can be reconsidered.
        $this->assertLessThan(4.5, Color::contrast($tokens['light']['text-muted'], $tokens['status']['danger']['soft-light']));
        $this->assertGreaterThanOrEqual(4.5, Color::contrast($tokens['light']['text-muted-on-sunken'], $tokens['status']['danger']['soft-light']));
    }
}
