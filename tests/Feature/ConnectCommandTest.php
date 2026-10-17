<?php

namespace Tests\Feature;

use Dashcore\DesignKit\Commands\ConnectCommand;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

/**
 * The command a kit runs to find out whether it can reach the board.
 *
 * The tool is not a kit and does not register this command for real, so the
 * test registers it. It runs against a scratch repository with this one's
 * remote and no workflows: the state a fresh app is in. That used to be this
 * repository itself, until it started reporting to its own board, which the
 * last test checks for real. The git remote is still read by git and the
 * workflows from disk.
 */
class ConnectCommandTest extends TestCase
{
    private string $fresh;

    private string $realBase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app[Kernel::class]->registerCommand(new ConnectCommand);

        $this->fresh = sys_get_temp_dir().'/design-kit-connect-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->fresh.'/.github/workflows');
        Process::path($this->fresh)->run('git init -q');
        Process::path($this->fresh)->run('git remote add origin https://github.com/dashcoretech/design.dashcore.com.git');

        $this->realBase = $this->app->basePath();
        $this->app->setBasePath($this->fresh);
    }

    protected function tearDown(): void
    {
        $this->app->setBasePath($this->realBase);
        File::deleteDirectory($this->fresh);

        parent::tearDown();
    }

    #[Test]
    public function it_names_the_repository_it_would_report_as(): void
    {
        // Read off the git remote, because that is what somebody means by
        // "this repo" and it cannot be typed into somebody else's name.
        $this->artisan('design:connect')
            ->expectsOutputToContain('dashcoretech/design.dashcore.com')
            ->assertFailed();
    }

    #[Test]
    public function a_repository_with_no_reporting_step_is_told_what_to_add(): void
    {
        // A fresh app: a GitHub remote and no reporting step yet.
        $this->artisan('design:connect')
            ->expectsOutputToContain('No workflow reports to the board')
            // The fix, not a pointer to documentation.
            ->expectsOutputToContain('design:audit --report')
            ->expectsOutputToContain('id-token: write')
            // Naming any permission turns the defaults off, so this has to be
            // in the snippet or the job cannot check itself out.
            ->expectsOutputToContain('contents: read')
            ->assertFailed();
    }

    #[Test]
    public function an_explicit_repository_wins_over_the_remote(): void
    {
        $this->artisan('design:connect', ['--repository' => 'dashcoretech/somewhere-else'])
            ->expectsOutputToContain('dashcoretech/somewhere-else')
            ->assertFailed();
    }

    #[Test]
    public function it_says_which_board_it_would_report_to(): void
    {
        // Set here rather than read back out of config: asserting a value
        // against itself passes even when it is the empty string, which is
        // exactly what this test did before.
        config(['design-kit.source' => 'https://design.dashcore.com']);

        $this->artisan('design:connect')
            ->expectsOutputToContain('https://design.dashcore.com')
            ->assertFailed();
    }

    #[Test]
    public function the_snippets_keep_their_indentation(): void
    {
        // Laravel's console style trims each line it prints, so the YAML came
        // out flat and unusable. Somebody would have pasted it.
        $output = new BufferedOutput;

        $command = new ConnectCommand;
        $command->setLaravel($this->app);
        $command->run(new ArrayInput([]), $output);

        $printed = $output->fetch();

        $this->assertStringContainsString('      if: github.ref_name', $printed);
        $this->assertStringContainsString('        DESIGN_SOURCE:', $printed);
        $this->assertStringContainsString('      id-token: write', $printed);
    }

    #[Test]
    public function this_repository_reports_to_its_own_board(): void
    {
        $this->app->setBasePath($this->realBase);

        $this->artisan('design:connect')
            ->expectsOutputToContain('.github/workflows/tests.yml')
            ->doesntExpectOutputToContain('No workflow reports to the board')
            ->assertSuccessful();
    }
}
