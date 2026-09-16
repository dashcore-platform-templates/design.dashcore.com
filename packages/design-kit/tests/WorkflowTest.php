<?php

namespace Dashcore\DesignKit\Tests;

use Dashcore\DesignKit\Workflow;
use PHPUnit\Framework\Attributes\Test;

/**
 * Reading a repository's workflows to say whether it can reach the board.
 *
 * The point of proving identity with GitHub rather than a pasted secret is
 * that what is missing sits in a file here. These are the checks that make
 * that true.
 */
class WorkflowTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/design-kit-workflows-'.bin2hex(random_bytes(6));
        mkdir($this->directory, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }

        parent::tearDown();
    }

    #[Test]
    public function a_repository_with_no_workflows_reports_nothing(): void
    {
        $this->assertSame(
            ['file' => null, 'proves' => false, 'guarded' => false],
            Workflow::inspect($this->directory)
        );

        // A directory that is not there at all is the same answer, not a crash.
        $this->assertNull(Workflow::inspect($this->directory.'/nope')['file']);
    }

    #[Test]
    public function a_workflow_that_reports_and_can_prove_itself_is_found(): void
    {
        $this->write('tests.yml', <<<'YAML'
        name: tests
        on: [push]
        jobs:
          ci:
            permissions:
              id-token: write
              contents: read
            steps:
              - run: composer install
              - name: Report to design.dashcore.com
                if: github.ref_name == github.event.repository.default_branch
                run: php artisan design:audit --report
        YAML);

        $this->assertSame(
            ['file' => 'tests.yml', 'proves' => true, 'guarded' => true],
            Workflow::inspect($this->directory)
        );
    }

    #[Test]
    public function a_workflow_missing_the_permission_is_the_mistake_worth_naming(): void
    {
        // Everything looks right and the job runs and sends nothing, because
        // GitHub never hands it a token to prove itself with.
        $this->write('tests.yml', <<<'YAML'
        jobs:
          ci:
            steps:
              - run: php artisan design:audit --report
        YAML);

        $state = Workflow::inspect($this->directory);

        $this->assertSame('tests.yml', $state['file']);
        $this->assertFalse($state['proves']);
    }

    #[Test]
    public function a_step_with_no_default_branch_guard_is_reported(): void
    {
        // Without it a pull request build overwrites the row with a branch
        // nobody has merged, and the board quietly stops meaning anything.
        $this->write('tests.yml', "jobs:\n  ci:\n    permissions:\n      id-token: write\n    steps:\n      - run: php artisan design:audit --report\n");

        $this->assertFalse(Workflow::inspect($this->directory)['guarded']);
    }

    #[Test]
    public function workflows_that_do_not_report_are_passed_over(): void
    {
        $this->write('deploy.yml', "jobs:\n  deploy:\n    steps:\n      - run: ./deploy.sh\n");
        $this->write('lint.yaml', "jobs:\n  lint:\n    permissions:\n      id-token: write\n    steps:\n      - run: php artisan design:audit --report\n");

        // Both extensions are read, and the one that reports is the one found.
        $this->assertSame('lint.yaml', Workflow::inspect($this->directory)['file']);
    }

    #[Test]
    public function the_shipped_step_and_permissions_are_what_the_checks_look_for(): void
    {
        // The command prints these as the fix. If they ever stopped satisfying
        // the checks, it would be telling people to do something that does not
        // work.
        $this->write('tests.yml', Workflow::PERMISSIONS."\n".Workflow::STEP);

        $this->assertSame(
            ['file' => 'tests.yml', 'proves' => true, 'guarded' => true],
            Workflow::inspect($this->directory)
        );
    }

    #[Test]
    public function a_remote_becomes_owner_slash_name(): void
    {
        foreach ([
            'git@github.com:dashcoretech/newproject.dashcore.com.git',
            'https://github.com/dashcoretech/newproject.dashcore.com.git',
            'https://github.com/dashcoretech/newproject.dashcore.com',
            "  https://github.com/dashcoretech/newproject.dashcore.com/  \n",
            'dashcoretech/newproject.dashcore.com',
        ] as $remote) {
            $this->assertSame('dashcoretech/newproject.dashcore.com', Workflow::repository($remote), $remote);
        }
    }

    #[Test]
    public function a_remote_that_is_not_github_has_no_repository(): void
    {
        // The board is keyed by GitHub repositories, and only GitHub can vouch
        // for a job, so anything else cannot report whatever it is called.
        foreach ([
            'git@gitlab.com:dashcoretech/thing.git',
            'https://bitbucket.org/dashcoretech/thing',
            'no-owner',
            '',
        ] as $remote) {
            $this->assertNull(Workflow::repository($remote), $remote);
        }
    }

    private function write(string $name, string $body): void
    {
        file_put_contents($this->directory.'/'.$name, $body);
    }
}
