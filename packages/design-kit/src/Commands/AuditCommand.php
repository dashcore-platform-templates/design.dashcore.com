<?php

namespace Dashcore\DesignKit\Commands;

use Dashcore\DesignKit\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * php artisan design:audit
 *
 * Lists everything in the app that is off the standard, per file. This is the
 * map a migration works from. --fix applies only the mechanical half: weights
 * and off-scale spacing, which have no judgment in them. Everything else is
 * reported and left alone.
 */
class AuditCommand extends Command
{
    protected $signature = 'design:audit
        {path=resources/views : Directory to walk}
        {--fix : Apply the mechanical rewrites (weights, off-scale spacing) and report the rest}
        {--full : List every finding rather than the first few per file}';

    protected $description = 'Find what in this app is off the Dashcore Standard';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_string($path) || $path === '') {
            $this->components->error('Give one directory to walk.');

            return self::FAILURE;
        }

        $root = base_path($path);

        if (! File::isDirectory($root)) {
            $this->components->error("No directory at {$path}.");

            return self::FAILURE;
        }

        $files = collect(File::allFiles($root))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.blade.php'))
            // Flux's own published stubs are the framework's, not ours to police.
            ->reject(fn ($f) => str_starts_with(str_replace('\\', '/', $f->getRelativePath()), 'flux'));

        $byRule = [];
        $totalFindings = 0;
        $filesWithFindings = 0;
        $fixedFiles = 0;

        foreach ($files as $file) {
            $before = File::get($file->getPathname());
            $contents = $before;

            if ($this->option('fix')) {
                [$contents, $changed] = Audit::mechanical($contents);
                if ($changed !== []) {
                    File::put($file->getPathname(), $contents);
                    $fixedFiles++;
                }
            }

            $findings = Audit::file($contents);
            if ($findings === []) {
                continue;
            }

            $filesWithFindings++;
            $totalFindings += count($findings);
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());

            $this->line('');
            $this->components->twoColumnDetail("<fg=cyan>{$relative}</>", count($findings).' to look at');

            $shown = $this->option('full') ? $findings : array_slice($findings, 0, 4);
            foreach ($shown as $f) {
                $byRule[$f['rule']] = ($byRule[$f['rule']] ?? 0) + 1;
                $this->line("    <fg=gray>{$f['line']}</>  {$f['rule']}: <fg=yellow>{$f['snippet']}</>");
            }
            if (count($findings) > count($shown)) {
                $this->line('    <fg=gray>'.(count($findings) - count($shown)).' more, run with --full</>');
            }

            foreach (array_slice($findings, count($shown)) as $f) {
                $byRule[$f['rule']] = ($byRule[$f['rule']] ?? 0) + 1;
            }
        }

        $this->line('');
        if ($fixedFiles > 0) {
            $this->components->info("Mechanical rewrites applied to {$fixedFiles} file(s). Check the diff, then run your tests.");
        }

        if ($totalFindings === 0) {
            $this->components->info('Nothing off the standard.');

            return self::SUCCESS;
        }

        arsort($byRule);
        $this->components->twoColumnDetail('<fg=cyan>By rule</>', "{$totalFindings} in {$filesWithFindings} file(s)");
        foreach ($byRule as $rule => $count) {
            $this->components->twoColumnDetail("  {$rule}", (string) $count);
        }

        $this->line('');
        $this->components->warn('These need a person. design/MIGRATION.md in the pack says how to work through them.');

        return self::FAILURE;
    }
}
