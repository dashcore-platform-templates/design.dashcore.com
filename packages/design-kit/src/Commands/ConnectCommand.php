<?php

namespace Dashcore\DesignKit\Commands;

use Dashcore\DesignKit\ActionsToken;
use Dashcore\DesignKit\Workflow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * php artisan design:connect
 *
 * What it takes for this repository to reach the fleet board, and which of it
 * is not done yet.
 *
 * There is nothing to register. A repository joins the board the first time it
 * reports, and it reports by proving which one it is with a token GitHub mints
 * for the job, so there is no form here and no secret to paste over there.
 * What is left is a workflow step, and this says whether it exists.
 *
 * Run it again whenever the board looks wrong. It answers the question the
 * board cannot: whether this repository is set up to speak at all.
 */
class ConnectCommand extends Command
{
    protected $signature = 'design:connect
        {--repository= : owner/name, when the git remote is not what this reports as}';

    protected $description = 'Check what this repository needs to reach the fleet board';

    public function handle(): int
    {
        $source = rtrim((string) config('design-kit.source'), '/');
        $repository = $this->repository();

        $this->line('');
        $this->components->twoColumnDetail('<fg=cyan>Repository</>', $repository ?? '<fg=yellow>unknown</>');
        $this->components->twoColumnDetail('<fg=cyan>Reporting to</>', $source);

        $ready = true;

        if ($repository === null) {
            $this->line('');
            $this->components->warn('No GitHub remote found. Pass --repository=owner/name, or run this inside the repository.');
            $ready = false;
        }

        if (! File::exists(base_path('design/design.json'))) {
            // Not fatal. A repository can be on the board with nothing applied
            // yet, and the board says so rather than going quiet.
            $this->line('');
            $this->components->warn('No design applied yet, so the board will list this repository with nothing against it.');
        }

        $ready = $this->reportsToTheBoard($source) && $ready;

        $this->line('');

        if (! $ready) {
            $this->components->error('Not connected. Fix the above, commit, and push to the default branch.');

            return self::FAILURE;
        }

        $this->components->info('Ready. The row appears on '.$source.'/fleet after the next run on the default branch.');

        return self::SUCCESS;
    }

    private function reportsToTheBoard(string $source): bool
    {
        $state = Workflow::inspect(base_path('.github/workflows'));

        if ($state['file'] === null) {
            $this->line('');
            $this->components->error('No workflow reports to the board.');
            $this->block('Add this to the workflow that already installs the app, after composer install:', Workflow::STEP);
            $this->block('And on that job, so it can prove which repository it is:', Workflow::PERMISSIONS);

            return false;
        }

        $this->components->twoColumnDetail('<fg=cyan>Reporting step</>', '.github/workflows/'.$state['file']);

        if (! $state['proves']) {
            // The one mistake worth naming outright: everything else looks
            // right, and the job runs and sends nothing.
            $this->components->twoColumnDetail('<fg=cyan>Can prove itself</>', '<fg=red>no</>');
            $this->block('Add to the job that runs that step:', Workflow::PERMISSIONS);
            $this->line('  <fg=gray>Naming any permission turns the defaults off, so keep contents: read.</>');

            return false;
        }

        $this->components->twoColumnDetail('<fg=cyan>Can prove itself</>', '<fg=green>yes</>');

        if (! $state['guarded']) {
            $this->line('');
            $this->components->warn('That step has no default-branch guard, so a pull request build will overwrite the row.');
        }

        if (ActionsToken::inActions() && ! ActionsToken::hasPermission()) {
            // Running inside Actions is the one place this can be checked for
            // real rather than read off a file.
            $this->line('');
            $this->components->error('This job has no id-token permission, so it cannot report.');

            return false;
        }

        return true;
    }

    /**
     * Written to the underlying output rather than through line().
     *
     * Laravel's console style trims each line, and YAML that has lost its
     * indentation is not a snippet somebody can paste, it is a wrong answer
     * printed confidently.
     */
    private function block(string $lead, string $yaml): void
    {
        $out = $this->output->getOutput();

        $out->writeln('');
        $out->writeln('  <fg=gray>'.$lead.'</>');
        $out->writeln('');

        foreach (explode("\n", $yaml) as $line) {
            $out->writeln('    <fg=cyan>'.$line.'</>');
        }

        $out->writeln('');
    }

    /** owner/name from the GitHub remote, or whatever was passed. */
    private function repository(): ?string
    {
        $given = (string) $this->option('repository');

        if ($given !== '') {
            return Workflow::repository($given);
        }

        // Inside Actions GitHub says it outright, and that is the name that
        // will actually be reported.
        $inActions = (string) (getenv('GITHUB_REPOSITORY') ?: '');

        if ($inActions !== '') {
            return Workflow::repository($inActions);
        }

        $result = Process::path(base_path())->run('git remote get-url origin');

        return $result->successful() ? Workflow::repository($result->output()) : null;
    }
}
