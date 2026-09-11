<?php

namespace Dashcore\DesignKit\Commands;

use Dashcore\DesignKit\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SplFileInfo;

/**
 * php artisan design:audit [path]
 *
 * Lists everything in the app that is off the standard, per file. This is the
 * map a migration works from, and the check an agent runs on one screen after
 * changing it: the path can be a directory or a single Blade file. --fix
 * applies only the mechanical half, weights and off-scale spacing, which have
 * no judgment in them. Everything else is reported and left alone.
 */
class AuditCommand extends Command
{
    protected $signature = 'design:audit
        {path=resources/views : A directory to walk, or one Blade file}
        {--fix : Apply the mechanical rewrites (weights, off-scale spacing) and report the rest}
        {--full : List every finding rather than the first few per file}';

    protected $description = 'Find what in this app is off the Dashcore Standard';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_string($path) || $path === '') {
            $this->components->error('Give one directory or one Blade file.');

            return self::FAILURE;
        }

        $root = base_path($path);

        if (File::isFile($root)) {
            $files = collect([new SplFileInfo($root)]);
        } elseif (File::isDirectory($root)) {
            $files = collect(File::allFiles($root))
                ->filter(fn ($f) => str_ends_with($f->getFilename(), '.blade.php'))
                // Flux's own published stubs are the framework's, not ours to police.
                ->reject(fn ($f) => str_starts_with(str_replace('\\', '/', $f->getRelativePath()), 'flux'));
        } else {
            $this->components->error("Nothing at {$path}.");

            return self::FAILURE;
        }

        $byRule = [];
        $totalFindings = 0;
        $filesWithFindings = 0;
        $fixedFiles = 0;
        $skipped = [];

        foreach ($files as $file) {
            $relative = $this->relative($file->getPathname());
            $before = File::get($file->getPathname());
            $contents = $before;

            if (Audit::optsOut($contents)) {
                $skipped[] = $relative;

                continue;
            }

            if ($this->option('fix')) {
                [$contents, $changed] = Audit::mechanical($contents);
                if ($changed !== []) {
                    File::put($file->getPathname(), $contents);
                    $fixedFiles++;
                }
            }

            $findings = Audit::file($contents, $relative);
            if ($findings === []) {
                continue;
            }

            $filesWithFindings++;
            $totalFindings += count($findings);

            // A plain line rather than a padded two-column detail. Livewire 4
            // names single-file components with a lightning bolt, which the
            // console's width maths count as one column and the terminal draws
            // as two, so a padded right column comes out garbled.
            $this->line('');
            $this->line("  <fg=cyan>{$relative}</>  <fg=gray>".count($findings).' to look at</>');

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

        if ($skipped !== []) {
            $this->components->twoColumnDetail('<fg=gray>Skipped by request</>', count($skipped).' file(s)');
            foreach ($skipped as $s) {
                $this->line("    <fg=gray>{$s}</>");
            }
            $this->line('');
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
        $this->components->warn('These need a person or an agent. design/MIGRATION.md in the pack says how to work through them.');

        return self::FAILURE;
    }

    /** The path from the repo root, with forward slashes on every platform. */
    private function relative(string $path): string
    {
        $base = str_replace('\\', '/', base_path()).'/';
        $path = str_replace('\\', '/', $path);

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }
}
