<?php

namespace Tests\Unit;

use App\Design\Color;
use App\Design\Generation\Rules;
use App\Design\Generation\Schema;
use App\Design\Pairings;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeDecisionSource;

class GenerationRulesTest extends TestCase
{
    #[Test]
    public function the_schema_enumerates_the_same_lists_the_resolver_uses(): void
    {
        $schema = Schema::decisions();

        $this->assertSame(['pure', 'cool', 'warm'], $schema['properties']['temperature']['properties']['value']['enum']);
        $this->assertSame(array_keys(Pairings::all()), $schema['properties']['pairing']['properties']['value']['enum']);
        $this->assertSame(['accent-bar', 'hairline', 'eyebrow', 'filled-stat'], $schema['properties']['motif']['properties']['value']['enum']);
        $this->assertFalse($schema['additionalProperties']);
        $this->assertCount(7, $schema['required']);
    }

    #[Test]
    public function a_clean_answer_passes_through_unchanged(): void
    {
        $in = FakeDecisionSource::sample();
        $out = Rules::apply($in, ['brand_colour' => null]);

        $this->assertSame($in, $out);
    }

    #[Test]
    public function an_accent_in_the_red_zone_is_moved_and_the_reason_says_so(): void
    {
        $in = FakeDecisionSource::sample();
        $in['accent'] = ['value' => '#d9342b', 'why' => 'A bold red.'];

        $out = Rules::apply($in, ['brand_colour' => null]);
        [$h] = Color::hexToHsl($out['accent']['value']);

        $this->assertNotSame('#d9342b', $out['accent']['value']);
        $this->assertTrue($h >= 70 && $h <= 330, "hue {$h} still inside the red or amber zone");
        $this->assertStringContainsString('hue moved', $out['accent']['why']);
        $this->assertStringStartsWith('A bold red.', $out['accent']['why']);
    }

    #[Test]
    public function a_brand_colour_from_the_brief_is_kept_even_in_the_red_zone(): void
    {
        $in = FakeDecisionSource::sample();
        $in['accent'] = ['value' => '#B5542D', 'why' => 'The client\'s terracotta.'];

        $out = Rules::apply($in, ['brand_colour' => '#b5542d']);

        $this->assertSame('#b5542d', $out['accent']['value']);
        $this->assertSame('The client\'s terracotta.', $out['accent']['why']);
    }

    #[Test]
    public function saturation_is_clamped_to_the_standards_range(): void
    {
        $in = FakeDecisionSource::sample();
        $in['accent'] = ['value' => '#8a8f99', 'why' => 'A quiet grey-blue.'];

        $out = Rules::apply($in, ['brand_colour' => null]);
        [, $s] = Color::hexToHsl($out['accent']['value']);

        $this->assertGreaterThanOrEqual(44.5, $s);
        $this->assertStringContainsString('saturation raised', $out['accent']['why']);
    }

    #[Test]
    public function off_list_values_fall_back_and_are_noted(): void
    {
        $in = FakeDecisionSource::sample();
        $in['temperature']['value'] = 'lukewarm';
        $in['pairing']['value'] = 42;
        $in['motif']['value'] = 'gradient';

        $out = Rules::apply($in, []);

        $this->assertSame('pure', $out['temperature']['value']);
        $this->assertSame(1, $out['pairing']['value']);
        $this->assertSame('accent-bar', $out['motif']['value']);
        $this->assertStringContainsString('not on the list', $out['temperature']['why']);
    }

    #[Test]
    public function copy_is_cut_to_the_word_limits_and_loses_exclamation_marks(): void
    {
        $in = FakeDecisionSource::sample();
        $in['copy']['headline'] = 'One two three four five six seven eight nine ten!';
        $in['copy']['eyebrow'] = '';

        $out = Rules::apply($in, []);

        $this->assertSame('One two three four five six seven eight', $out['copy']['headline']);
        $this->assertNull($out['copy']['eyebrow']);
        $this->assertStringContainsString('cut to 8 words', $out['copy']['why']);
    }
}
