<?php

namespace Dashcore\DesignKit\Commands;

use Dashcore\DesignKit\Applier;
use Dashcore\DesignKit\Contrast;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;
use LogicException;

/**
 * php artisan design:apply design/design.json
 *
 * Plans every change first, prints it, then writes. --check prints the plan
 * and exits non-zero if anything would change, for CI. Running it twice with
 * the same file is a no-op.
 */
class ApplyCommand extends Command
{
    protected $signature = 'design:apply
        {file=design/design.json : The design file exported from design.dashcore.com}
        {--check : Print what would change and exit 1 if anything would; write nothing}
        {--force : Delete the Lucide icon overrides without asking}';

    protected $description = 'Apply a Dashcore design file to this kit: theme variables, fonts, brand copy, sign-in layout';

    private string $file = 'design/design.json';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_string($file) || $file === '') {
            $this->components->error('Give the path to one design file.');

            return self::FAILURE;
        }
        $this->file = $file;

        $path = base_path($file);
        if (! File::exists($path)) {
            $this->components->error("No file at {$file}. Unzip the pack from design.dashcore.com into the repo root first.");

            return self::FAILURE;
        }

        try {
            $design = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
            $tokens = Applier::tokens($design);
        } catch (JsonException|InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $contrast = Contrast::measure($tokens);
        if (! $contrast['passes']) {
            $this->components->error("This design fails contrast ({$contrast['lowest']['pair']} at {$contrast['lowest']['ratio']}:1). Fix it in design.dashcore.com before applying.");

            return self::FAILURE;
        }

        $changes = $this->plan($design, $tokens);
        $changed = array_filter($changes, fn (array $c) => $c['changed']);

        $this->components->info(sprintf('%s: %s, %s, %s.', $design['brief']['client'] ?? 'Design', $tokens['fonts']['sans']['family'].' + '.$tokens['fonts']['second']['family'], $design['decisions']['temperature']['value'] ?? '', $design['decisions']['motif']['value'] ?? ''));
        foreach ($changes as $c) {
            $this->components->twoColumnDetail($c['path'], $c['changed'] ? "<fg=yellow>{$c['action']}</>" : '<fg=gray>unchanged</>');
        }

        if ($this->option('check')) {
            $this->line('');
            $this->components->{$changed === [] ? 'info' : 'warn'}($changed === [] ? 'Nothing to change.' : count($changed).' file(s) would change.');

            return $changed === [] ? self::SUCCESS : self::FAILURE;
        }

        foreach ($changed as $c) {
            match ($c['action']) {
                'write' => File::put($c['path'], $c['after']),
                'copy' => $this->copy($c['from'], $c['path']),
                'delete' => $this->delete($c['path']),
                default => throw new LogicException("Unknown change action {$c['action']}."),
            };
        }

        $this->line('');
        $this->components->info($changed === [] ? 'Already applied. Nothing changed.' : 'Applied. Run `npm run build` and reload.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string,mixed>  $design
     * @param  array<string,mixed>  $tokens
     * @return list<array<string,mixed>>
     */
    private function plan(array $design, array $tokens): array
    {
        $changes = [];

        $css = base_path('resources/css/app.css');
        $before = File::exists($css) ? File::get($css) : '';
        $after = Applier::applyCss($before, Applier::cssBlock($tokens));
        $changes[] = ['path' => $css, 'action' => 'write', 'after' => $after, 'changed' => $after !== $before];

        // Fonts: a Laravel 13 kit has a fonts array in vite.config.js; a Laravel 12
        // kit has a Bunny <link> in the head partial. Rewrite whichever exists.
        $fontsHandled = false;
        $vite = base_path('vite.config.js');
        if (File::exists($vite) && preg_match('/^\s*fonts:\s*\[/m', File::get($vite))) {
            $before = File::get($vite);
            $after = Applier::applyVite($before, $tokens);
            $changes[] = ['path' => $vite, 'action' => 'write', 'after' => $after, 'changed' => $after !== $before];
            $fontsHandled = true;
        }
        $head = base_path('resources/views/partials/head.blade.php');
        if (! $fontsHandled && File::exists($head) && str_contains(File::get($head), 'fonts.bunny.net')) {
            $before = File::get($head);
            $after = Applier::applyHead($before, $tokens);
            $changes[] = ['path' => $head, 'action' => 'write', 'after' => $after, 'changed' => $after !== $before];
            $fontsHandled = true;
        }
        if (! $fontsHandled) {
            $this->components->warn('No Vite fonts array and no Bunny link in partials/head.blade.php. Load the two faces by hand: '.($tokens['fonts']['stylesheet'] ?? ''));
        }

        foreach (['.env', '.env.example'] as $envFile) {
            $envPath = base_path($envFile);
            if (File::exists($envPath)) {
                $before = File::get($envPath);
                $after = Applier::applyEnv($before, Applier::envValues($design));
                $changes[] = ['path' => $envPath, 'action' => 'write', 'after' => $after, 'changed' => $after !== $before];
            }
        }

        $auth = Applier::authLayoutPath(base_path());
        if ($auth !== null) {
            $before = File::get($auth);
            $after = Applier::authLayout();
            $changes[] = ['path' => $auth, 'action' => 'write', 'after' => $after, 'changed' => $after !== $before];
        } else {
            $this->components->warn('No auth layout found to point at the two-panel sign-in. Use <x-design-kit::layouts.auth> in your auth pages.');
        }

        $logo = $design['brief']['logo'] ?? null;
        if (filled($logo)) {
            $from = base_path(dirname($this->file).'/'.ltrim((string) $logo, '/'));
            $to = public_path(Applier::envValues($design)['BRAND_LOGO']);
            $changes[] = ['path' => $to, 'from' => $from, 'action' => 'copy', 'changed' => File::exists($from) && (! File::exists($to) || md5_file($from) !== md5_file($to))];
        }

        // Lucide names in the kit's own views become Heroicons, then the override
        // files go. The order matters: renaming first keeps every icon resolvable.
        $views = base_path('resources/views');
        if (File::isDirectory($views)) {
            foreach (File::allFiles($views) as $view) {
                if (! str_ends_with($view->getFilename(), '.blade.php') || str_starts_with($view->getRelativePath(), 'flux')) {
                    continue;
                }
                $before = File::get($view->getPathname());
                if (! Applier::mentionsLucide($before)) {
                    continue;
                }
                $after = Applier::renameLucide($before);
                $changes[] = ['path' => $view->getPathname(), 'action' => 'write', 'after' => $after, 'changed' => $after !== $before];
            }
        }

        foreach (Applier::LUCIDE_ICONS as $icon) {
            $iconPath = base_path($icon);
            $changes[] = ['path' => $iconPath, 'action' => 'delete', 'changed' => File::exists($iconPath)];
        }

        return $changes;
    }

    private function copy(string $from, string $to): void
    {
        File::ensureDirectoryExists(dirname($to));
        File::copy($from, $to);
    }

    private function delete(string $path): void
    {
        $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
        if (! $this->option('force') && ! $this->confirm("Delete {$relative}? It is a Lucide-named icon override; the standard is Heroicons only.", true)) {
            $this->components->warn("Kept {$relative}.");

            return;
        }
        File::delete($path);
    }
}
