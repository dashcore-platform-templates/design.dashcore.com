<?php

namespace Dashcore\DesignKit\Tests;

use Dashcore\DesignKit\Report;
use PHPUnit\Framework\Attributes\Test;

class ReportTest extends TestCase
{
    #[Test]
    public function a_report_is_counts_and_two_stamps_and_nothing_else(): void
    {
        $payload = Report::payload(
            ['tap target under 36px' => 6, 'Flux heading at section size' => 44],
            12,
            40,
            '9858e99a',
            'harbor-point',
            'v3.0.0',
        );

        // The style's name and the allowance's total are a word and a number,
        // never source, so they are the only additions since 6.0.
        $this->assertSame([
            'standard_version', 'package_version', 'design_slug', 'style', 'allowance', 'findings',
            'total_findings', 'files_with_findings', 'files_checked', 'reported_at',
        ], array_keys($payload), 'nothing else leaves the repo');

        $this->assertSame(50, $payload['total_findings']);
        $this->assertSame(12, $payload['files_with_findings']);
        $this->assertSame(40, $payload['files_checked']);

        // Worst first, so a truncated view still shows the thing that matters.
        $this->assertSame(['Flux heading at section size', 'tap target under 36px'], array_keys($payload['findings']));
    }

    #[Test]
    public function the_total_is_summed_here_rather_than_taken_on_trust(): void
    {
        $payload = Report::payload(['a' => 3, 'b' => 4], 2, 10, null, null, 'v3.0.0');

        $this->assertSame(7, $payload['total_findings'], 'the number on the page has to agree with the rules beside it');
    }

    #[Test]
    public function nothing_that_is_not_a_count_gets_through(): void
    {
        // @phpstan-ignore-next-line the point of the test is what happens with bad input
        $payload = Report::payload(['real' => 5, 'zero' => 0, 'text' => 'lots', 'negative' => -3], 1, 10, null, null, 'v3.0.0');

        $this->assertSame(['real' => 5], $payload['findings']);
        $this->assertSame(5, $payload['total_findings']);
    }

    #[Test]
    public function an_app_with_nothing_wrong_still_reports(): void
    {
        $payload = Report::payload([], 0, 40, '9858e99a', 'northline', 'v3.0.0');

        // Zero is an answer, and a site that stops reporting is a different
        // thing from a site with nothing to report.
        $this->assertSame([], $payload['findings']);
        $this->assertSame(0, $payload['total_findings']);
        $this->assertSame(40, $payload['files_checked']);
    }

    #[Test]
    public function a_missing_stamp_is_sent_as_missing(): void
    {
        $payload = Report::payload([], 0, 40, null, null, null);

        $this->assertNull($payload['standard_version'], 'an app applied before stamping says so rather than guessing');
        $this->assertNull($payload['design_slug']);
        $this->assertNull($payload['package_version'], 'a path install has no version to give');
    }

    #[Test]
    public function the_time_is_sent_in_utc(): void
    {
        $payload = Report::payload([], 0, 1, null, null, 'v3.0.0');

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/', $payload['reported_at']);
    }

    #[Test]
    public function a_style_and_an_allowance_ride_along_and_default_to_null(): void
    {
        $plain = Report::payload([], 0, 1, null, 'harbor-point', 'v6.1.0');
        $this->assertNull($plain['style']);
        $this->assertNull($plain['allowance']);

        $styled = Report::payload(['x' => 2], 1, 1, null, null, 'v6.1.0', style: 'dashcore', allowance: 40);
        $this->assertSame('dashcore', $styled['style']);
        $this->assertSame(40, $styled['allowance']);
    }
}
