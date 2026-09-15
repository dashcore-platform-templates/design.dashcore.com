<?php

namespace Database\Factories;

use App\Design\Standard;
use App\Models\Site;
use App\Models\SiteReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteReport>
 */
class SiteReportFactory extends Factory
{
    /** @return array<string,mixed> */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'standard_version' => Standard::version(),
            'package_version' => 'v3.0.0',
            'design_slug' => 'harbor-point',
            'findings' => [],
            'total_findings' => 0,
            'files_with_findings' => 0,
            'files_checked' => 40,
            'reported_at' => now(),
        ];
    }

    /** A site that has not re-applied since the standard moved. */
    public function behind(): self
    {
        return $this->state(fn () => ['standard_version' => 'deadbeef']);
    }

    /**
     * @param  array<string,int>  $byRule
     */
    public function withFindings(array $byRule): self
    {
        return $this->state(fn () => [
            'findings' => $byRule,
            'total_findings' => array_sum($byRule),
            'files_with_findings' => max(1, count($byRule)),
        ]);
    }
}
