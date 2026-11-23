<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Templates ran out of GitHub's included minutes, so its private repositories
 * run on our own runner, picked by the org variable CI_RUNNER (REQ-1322). Every
 * job reads it, falling back to ubuntu-latest where it is unset: dashcoretech,
 * and this public repository, which CI_RUNNER is never visible to.
 */
class WorkflowRunnerTest extends TestCase
{
    private const RUNS_ON = "runs-on: \${{ fromJSON(vars.CI_RUNNER || '\"ubuntu-latest\"') }}";

    #[Test]
    public function every_job_picks_its_runner_from_ci_runner_with_github_as_the_fallback(): void
    {
        $files = glob(base_path('.github/workflows/*.yml')) ?: [];

        $this->assertEqualsCanonicalizing(
            ['design-report.yml', 'phpstan.yml', 'publish-design-kit.yml'],
            array_map('basename', $files),
        );

        foreach ($files as $file) {
            $contents = (string) file_get_contents($file);
            preg_match_all('/^  [a-z0-9_-]+:\s*$/m', explode("\njobs:\n", $contents, 2)[1] ?? '', $names);
            $jobs = count($names[0]);

            preg_match_all('/^\s*runs-on:.*$/m', $contents, $lines);

            $this->assertGreaterThan(0, $jobs, basename($file).' has no jobs');
            $this->assertCount($jobs, $lines[0], basename($file).': one runs-on per job');

            foreach ($lines[0] as $line) {
                $this->assertSame(self::RUNS_ON, trim($line), basename($file));
            }
        }
    }
}
