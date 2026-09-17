<?php

namespace Dashcore\DesignKit\Commands;

use Dashcore\DesignKit\Applier;
use Dashcore\DesignKit\Contrast;
use Dashcore\DesignKit\Source;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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
                    $this->components->error("No file at {$source}. Pass the design's slug or its pack URL instead, or unzip the pack into the repo root first.");

                    return self::FAILURE;
                }
                $design = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
                $this->file = $source;
            } else {
                $url = Source::kind($source) === 'url' ? $source : Source::url($source, (string) config('design-kit.source'));
                $response = Http::timeout(30)->withToken((string) config('design-kit.token'))->acceptJson()->get($url);
                if ($response->failed()) {
                    $this->reportFetchFailure($url, $response->status(), $source);

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

        $this->reportStandardMoved($packFiles);
        $this->reportFluxTier();

        if ($this->option('check')) {
            $this->line('');
            $this->components->{$changed === [] ? 'info' : 'warn'}($changed === [] ? 'Nothing to change.' : count($changed).' '.Str::plural('file', count($changed)).' would change.');

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

                if (! Applier::mentionsLucide($before) && ! Applier::hasStarterKitDrift($before)) {
                    continue;
                }

                // Both are exact rewrites of lines nobody chose, so they run in
                // one pass and the file is written once.
                $after = Applier::fixStarterKit(Applier::renameLucide($before));
                $changes[] = ['path' => $file->getPathname(), 'action' => 'write', 'after' => $after, 'changed' => $after !== $before];
            }
        }

        foreach (Applier::LUCIDE_ICONS as $icon) {
            $iconPath = base_path($icon);
            $changes[] = ['path' => $iconPath, 'action' => 'delete', 'why' => 'It is a Lucide-named icon override; the standard is Heroicons only.', 'changed' => File::exists($iconPath)];
        }

        return $changes;
    }

    /**
     * Say when the standard moved since this app last applied a design.
     *
     * Without this the rules change silently. The files are overwritten, the
     * command reports a handful of paths, and nobody reads a 50,000 character
     * markdown file to find the two paragraphs that are new.
     *
     * @param  array<string,string>  $packFiles  Empty when applying a local file, which cannot have moved.
     */
    private function reportStandardMoved(array $packFiles): void
    {
        if (! isset($packFiles['STANDARD.md'])) {
            return;
        }

        $local = base_path('design/STANDARD.md');
        $before = File::exists($local) ? Source::standardVersion(File::get($local)) : null;
        $after = Source::standardVersion($packFiles['STANDARD.md']);

        if ($after === null || $before === $after) {
            return;
        }

        $this->line('');

        if ($before === null) {
            // An unstamped copy predates stamping, so there is no before to
            // compare and no way to say how far it had drifted.
            $this->line("  <fg=gray>Standard version {$after}. The previous copy was unstamped, so this is the first one to compare against.</>");

            return;
        }

        $this->components->warn("The standard moved since you last applied: {$before} is now {$after}.");
        $this->line('  <fg=gray>design/CHANGELOG.md says what changed and whether anything needs fixing.</>');
        // A tightening only reaches existing screens through the audit; a
        // relaxation needs nothing. The changelog marks which is which.
        $this->line('  <fg=gray>Then run `php artisan design:audit` to find the screens it affects.</>');
    }

    /**
     * Say which Flux the app has.
     *
     * Section 06 names 19 Pro components and a free stand-in for eleven of
     * them, so an app without Pro is still on the standard. Worth saying out
     * loud all the same: the alternative is finding out later, when a screen
     * wants a component that is not installed.
     *
     * The repository line comes first because Flux Pro is not on Packagist, and
     * the Dashcore licence covers every site. Both belong here rather than in
     * the output, where they would be two sentences between somebody and the
     * command they came for. The ordering against `npm run build` does reach
     * the screen, because getting it wrong ships a build with no Pro classes in
     * the stylesheet and nothing says so.
     */
    private function reportFluxTier(): void
    {
        $this->line('');

        if (File::isDirectory(base_path('vendor/livewire/flux-pro'))) {
            $this->line('  <fg=gray>Flux Pro installed. Every component in standard section 06 is available.</>');

            return;
        }

        $this->components->warn('Flux Pro is not installed. 19 components in standard section 06 are unavailable, 11 of them with a free stand-in.');
        $this->line('  <fg=gray>To install it, before `npm run build`:</>');
        $this->line('  <fg=gray>  composer config repositories.flux-pro composer https://composer.fluxui.dev</>');
        $this->line('  <fg=gray>  composer require livewire/flux-pro</>');
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

    /**
     * Say what to do about it, not that it happened.
     *
     * "Check the slug" is not help: the slug came out of design/design.json
     * without anybody typing it, so being told to check it leaves somebody
     * looking at a file they did not write for a mistake they did not make.
     * A 404 has one meaning here, and there is one command that fixes it.
     */
    private function reportFetchFailure(string $url, int $status, string $source): void
    {
        $site = rtrim((string) config('design-kit.source'), '/');

        if ($status === 401) {
            // The one failure with a fix that is not in this repo at all.
            $this->components->error('This needs your token.');
            $this->line('');
            $this->line("  <fg=gray>Make one at {$site}/account, named after this machine, then put it in</>");
            $this->line("  <fg=gray>this app's .env:</>");
            $this->line('');
            $this->line('    <fg=cyan>DESIGN_TOKEN=dsn_…</>');
            $this->line('');
            $this->line("  <fg=gray>It is yours rather than this repository's, so one token covers every app</>");
            $this->line('  <fg=gray>you work on. It never goes in CI: a workflow reports and never pulls.</>');

            return;
        }

        if ($status !== 404) {
            $this->components->error("{$url} answered HTTP {$status}.");
            $this->line('');
            $this->line('  <fg=gray>The design was not fetched, so nothing has changed here. If that site is up,</>');
            $this->line('  <fg=gray>this is usually a network between you and it rather than anything in this repo.</>');

            return;
        }

        $named = Source::kind($source) === 'slug' ? $source : null;

        $this->components->error($named === null
            ? "Nothing is published at {$url}."
            : "There is no design called {$named} at {$site}.");

        $this->line('');

        if ($named !== null && $this->cameFromTheDesignFile($named)) {
            // The common one, and the one with no fault in it: the app was set
            // up against a design that has since been renamed or removed.
            $this->line("  <fg=gray>design/design.json says this app is on {$named}, so it was applied when that</>");
            $this->line('  <fg=gray>design existed. It has been renamed or removed since.</>');
            $this->line('');
        }

        $this->line("  <fg=gray>Open {$site}/designs and read the slug out of the design's address bar,</>");
        $this->line('  <fg=gray>then pass it once. It is written into design/design.json and you will not</>');
        $this->line('  <fg=gray>need it again.</>');
        $this->line('');
        $this->line('    <fg=cyan>php artisan design:update <slug></>');
        $this->line('');
        $this->line('  <fg=gray>Applying a different design repaints this app, so check the mockup first.</>');
    }

    /** Whether the slug was read out of the app rather than typed on the command line. */
    private function cameFromTheDesignFile(string $slug): bool
    {
        $file = base_path('design/design.json');

        if (! File::exists($file)) {
            return false;
        }

        $design = json_decode(File::get($file), true);

        return is_array($design) && Source::designSlug($design) === $slug;
    }
}
