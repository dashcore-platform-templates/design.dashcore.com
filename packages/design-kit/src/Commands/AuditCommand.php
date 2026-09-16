<?php

namespace Dashcore\DesignKit\Commands;

use Dashcore\DesignKit\ActionsToken;
use Dashcore\DesignKit\Audit;
use Dashcore\DesignKit\Report;
use Dashcore\DesignKit\Source;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
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
        {--full : List every finding rather than the first few per file}
        {--report : Also send the counts to design.dashcore.com, for the fleet view}';

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

        $this->report($byRule, $filesWithFindings, $files->count());

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

    /**
     * Send the counts to the tool, so the fleet view has a row for this app.
     *
     * It never fails the audit. The audit's answer is about this repo, and a
     * tool that is down or a token that was never set does not change it. A
     * build that goes red because a dashboard could not be updated teaches
     * people to pass --report less often, which is the opposite of the point.
     *
     * @param  array<string,int>  $byRule
     */
    private function report(array $byRule, int $filesWithFindings, int $filesChecked): void
    {
        if (! $this->option('report')) {
            return;
        }

        $source = rtrim((string) config('design-kit.source'), '/');

        // GitHub's word first. A workflow with id-token permission proves which
        // repository this is without anybody pasting a secret into it, and a
        // repository proving itself that way joins the board by reporting.
        $token = ActionsToken::forAudience($source)
            // Still supported, because every app in the fleet is on one today.
            ?? (string) config('design-kit.token');

        if ((string) $token === '') {
            $this->line('');

            if (ActionsToken::inActions() && ! ActionsToken::hasPermission()) {
                // The one mistake worth naming outright. Everything else is in
                // place and one line is missing from the workflow.
                $this->line('  <fg=gray>This job cannot prove which repository it is. Add to the workflow:</>');
                $this->line('');
                $this->line('    <fg=cyan>permissions:</>');
                $this->line('      <fg=cyan>id-token: write</>');
                $this->line('      <fg=cyan>contents: read</>');
                $this->line('');
                $this->line('  <fg=gray>Naming any permission turns the defaults off, so keep contents: read.</>');

                return;
            }

            $this->line('  <fg=gray>--report runs in GitHub Actions, where it proves which repository it is. Nothing was sent.</>');

            return;
        }

        $design = File::exists(base_path('design/design.json'))
            ? json_decode(File::get(base_path('design/design.json')), true)
            : null;

        $standard = File::exists(base_path('design/STANDARD.md'))
            ? Source::standardVersion(File::get(base_path('design/STANDARD.md')))
            : null;

        $payload = Report::payload(
            $byRule,
            $filesWithFindings,
            $filesChecked,
            $standard,
            is_array($design) ? Source::designSlug($design) : null,
        );

        $url = $source.'/api/reports';

        $this->line('');

        try {
            $response = Http::timeout(15)->withToken($token)->acceptJson()->post($url, $payload);
        } catch (ConnectionException $e) {
            $this->line("  <fg=gray>Could not reach {$url}, so nothing was reported. ({$e->getMessage()})</>");

            return;
        }

        if ($response->failed()) {
            $this->line("  <fg=gray>{$url} answered HTTP {$response->status()}, so nothing was reported.</>");

            if ($response->status() === 401) {
                $this->line('  <fg=gray>That is the credential. If this app still uses DESIGN_TOKEN, reissue it on design.dashcore.com; otherwise check the workflow asks for id-token: write.</>');
            }

            return;
        }

        // The tool knows what the standard reads today, so it says so on the
        // way back. An app therefore learns it is behind by reporting, without
        // a second call and without anybody asking.
        $current = $response->json('standard_version');
        $this->line('  <fg=gray>Reported to '.$url.'.</>');

        if (is_string($current) && $standard !== null && $current !== $standard) {
            $this->components->warn("The standard has moved since this app applied: {$standard} is now {$current}.");
        }
    }

    /** The path from the repo root, with forward slashes on every platform. */
    private function relative(string $path): string
    {
        $base = str_replace('\\', '/', base_path()).'/';
        $path = str_replace('\\', '/', $path);

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }
}
