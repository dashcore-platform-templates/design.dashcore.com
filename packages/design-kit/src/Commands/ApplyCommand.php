<?php

namespace Dashcore\DesignKit\Commands;

use Dashcore\DesignKit\Applier;
use Dashcore\DesignKit\Contrast;
use Dashcore\DesignKit\Source;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use JsonException;
use LogicException;

/**
 * php artisan design:apply meridian-credit-union
 * php artisan design:apply https://design.dashcore.com/designs/meridian-credit-union/pack.json
 * php artisan design:apply design/design.json
 *
 * A slug or a URL pulls the pack from design.dashcore.com into design/ first,
 * so the repo ends up with the same four files a downloaded zip would give it.
 * Then it plans every change, prints it, and writes. --check prints the plan
 * and exits non-zero if anything would change, for CI. Running it twice with
 * the same source is a no-op.
 */
class ApplyCommand extends Command
{
    protected $signature = 'design:apply
        {source=design/design.json : A design slug, a pack URL from design.dashcore.com, or a local design file}
        {--check : Print what would change and exit 1 if anything would; write nothing}
        {--force : Delete the Lucide icon overrides and the unused sign-in layouts without asking}';

    protected $description = 'Apply a Dashcore design file to this kit: theme variables, fonts, brand copy, sign-in layout';

    private string $file = 'design/design.json';

    public function handle(): int
    {
        $source = $this->argument('source');
        if (! is_string($source) || $source === '') {
            $this->components->error('Give a design slug, a pack URL, or the path to a design file.');

            return self::FAILURE;
        }

        $packFiles = [];

        try {
            if (Source::kind($source) === 'file') {
                $path = base_path($source);
                if (! File::exists($path)) {
                    $this->components->error("No file at {$source}. Give the design's slug or its pack URL from design.dashcore.com instead, or unzip the pack into the repo root first.");

                    return self::FAILURE;
                }
                $design = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
                $this->file = $source;
            } else {
                $url = Source::kind($source) === 'url' ? $source : Source::url($source, (string) config('design-kit.source'));
                $response = Http::timeout(30)->acceptJson()->get($url);
                if ($response->failed()) {
                    $this->components->error("Could not fetch {$url} (HTTP {$response->status()}). Check the slug, and that design.dashcore.com is reachable from here.");

                    return self::FAILURE;
                }
                $document = $response->json();
                if (! is_array($document)) {
                    $this->components->error("{$url} did not return a design pack.");

                    return self::FAILURE;
                }
                $packFiles = Source::files($document);
                $design = json_decode($packFiles['design.json'], true, 512, JSON_THROW_ON_ERROR);
                $this->file = 'design/design.json';
                $this->line("  <fg=gray>Fetched {$url}</>");
            }

            $tokens = Applier::tokens($design);
        } catch (JsonException|InvalidArgumentException|ConnectionException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $contrast = Contrast::measure($tokens);
        if (! $contrast['passes']) {
            $this->components->error("This design fails contrast ({$contrast['lowest']['pair']} at {$contrast['lowest']['ratio']}:1). Fix it in design.dashcore.com before applying.");

            return self::FAILURE;
        }

        $changes = $this->plan($design, $tokens, $packFiles);
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
                'write' => $this->write($c['path'], $c['after']),
                'copy' => $this->copy($c['from'], $c['path']),
                'delete' => $this->delete($c['path'], $c['why']),
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
     * @param  array<string,string>  $packFiles  Pulled pack files to land under design/, keyed by name.
     * @return list<array<string,mixed>>
     */
    private function plan(array $design, array $tokens, array $packFiles = []): array
    {
        $changes = [];

        // A pulled pack lands in design/ first, so the repo commits the same
        // four files a downloaded zip would have given it.
        foreach ($packFiles as $name => $contents) {
            $path = base_path('design/'.$name);
            $changes[] = ['path' => $path, 'action' => 'write', 'after' => $contents, 'changed' => ! File::exists($path) || File::get($path) !== $contents];
        }

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

            // The kit's own sign-in layouts under layouts/auth/ have nothing
            // pointing at them once the layout above is ours. Left in place they
            // are dead code the audit keeps reporting.
            foreach (Applier::deadAuthLayouts(base_path(), [$auth]) as $dead) {
                $changes[] = ['path' => $dead, 'action' => 'delete', 'why' => 'Nothing references it now that the sign-in layout is the two-panel one.', 'changed' => true];
            }
        } else {
            $this->components->warn('No auth layout found to point at the two-panel sign-in. Use <x-design-kit::layouts.auth> in your auth pages.');
        }

        $logo = $design['brief']['logo'] ?? null;
        if (filled($logo)) {
            $from = base_path(dirname($this->file).'/'.ltrim((string) $logo, '/'));
            $to = public_path(Applier::envValues($design)['BRAND_LOGO']);
            $changes[] = ['path' => $to, 'from' => $from, 'action' => 'copy', 'changed' => File::exists($from) && (! File::exists($to) || md5_file($from) !== md5_file($to))];
        }

        // Lucide names become Heroicons wherever the app names one, then the
        // override files go. The order matters: renaming first keeps every icon
        // resolvable. Names live in Blade views and also in PHP arrays (nav
        // definitions, Livewire classes, config), so all three trees are scanned.
        foreach (['resources/views', 'app', 'config', 'routes'] as $dir) {
            $root = base_path($dir);
            if (! File::isDirectory($root)) {
                continue;
            }
            foreach (File::allFiles($root) as $file) {
                if (! str_ends_with($file->getFilename(), '.php') || ($dir === 'resources/views' && str_starts_with($file->getRelativePath(), 'flux'))) {
                    continue;
                }
                $before = File::get($file->getPathname());
                if (! Applier::mentionsLucide($before)) {
                    continue;
                }
                $after = Applier::renameLucide($before);
                $changes[] = ['path' => $file->getPathname(), 'action' => 'write', 'after' => $after, 'changed' => $after !== $before];
            }
        }

        foreach (Applier::LUCIDE_ICONS as $icon) {
            $iconPath = base_path($icon);
            $changes[] = ['path' => $iconPath, 'action' => 'delete', 'why' => 'It is a Lucide-named icon override; the standard is Heroicons only.', 'changed' => File::exists($iconPath)];
        }

        return $changes;
    }

    private function write(string $path, string $contents): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
    }

    private function copy(string $from, string $to): void
    {
        File::ensureDirectoryExists(dirname($to));
        File::copy($from, $to);
    }

    private function delete(string $path, string $why): void
    {
        $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
        if (! $this->option('force') && ! $this->confirm("Delete {$relative}? {$why}", true)) {
            $this->components->warn("Kept {$relative}.");

            return;
        }
        File::delete($path);
    }
}
