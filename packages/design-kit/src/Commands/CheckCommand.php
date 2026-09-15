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

/** php artisan design:check design/design.json — measure contrast on the file this kit carries. */
class CheckCommand extends Command
{
    protected $signature = 'design:check
        {file=design/design.json : The design file to measure}
        {--against= : A design slug or pack URL, to find out whether the standard moved since this app applied}';

    protected $description = 'Measure contrast for every text role in a design file, both registers';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_string($file) || $file === '') {
            $this->components->error('Give the path to one design file.');

            return self::FAILURE;
        }

        $path = base_path($file);
        if (! File::exists($path)) {
            $this->components->error("No file at {$file}.");

            return self::FAILURE;
        }

        try {
            $tokens = Applier::tokens(json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR));
        } catch (JsonException|InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $result = Contrast::measure($tokens);
        $this->components->twoColumnDetail("<fg=cyan>{$file}</>", $result['passes'] ? '<fg=green>passes</>' : '<fg=red>FAILS</>');
        foreach ($result['pairs'] as $pair) {
            $this->components->twoColumnDetail("  {$pair['pair']}", sprintf('%s  %5.2f', $pair['passes'] ? '<fg=green>ok</>' : '<fg=red>fail</>', $pair['ratio']));
        }

        $this->reportStandardMoved();

        return $result['passes'] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Say whether the standard moved, without applying anything.
     *
     * design:apply already reports this, but only while it rewrites the app.
     * An app that just wants to know where it stands had to re-apply to find
     * out, which is a change to answer a question. This asks the question on
     * its own: the local stamp against the one the tool is publishing now.
     *
     * It never changes the exit code. Contrast is the gate here, and a moved
     * standard is not a failure: the changelog marks half of them relaxations
     * with nothing to do.
     */
    private function reportStandardMoved(): void
    {
        $against = $this->option('against');
        $this->line('');

        if (! is_string($against) || $against === '') {
            $this->line('  <fg=gray>Pass --against=<slug> to also find out whether the standard moved since this app applied.</>');

            return;
        }

        $local = base_path('design/STANDARD.md');
        if (! File::exists($local)) {
            $this->line('  <fg=gray>No design/STANDARD.md here, so there is no applied version to compare against.</>');

            return;
        }

        $before = Source::standardVersion(File::get($local));
        $url = Source::kind($against) === 'url' ? $against : Source::url($against, (string) config('design-kit.source'));

        try {
            $response = Http::timeout(15)->acceptJson()->get($url);
        } catch (ConnectionException $e) {
            $this->line("  <fg=gray>Could not reach {$url}, so the standard was not compared. ({$e->getMessage()})</>");

            return;
        }

        if ($response->failed()) {
            $this->line("  <fg=gray>Could not fetch {$url} (HTTP {$response->status()}), so the standard was not compared.</>");

            return;
        }

        $document = $response->json();
        $after = is_array($document) ? Source::publishedStandardVersion($document) : null;

        if ($after === null) {
            $this->line("  <fg=gray>{$url} did not state a standard version, so there was nothing to compare.</>");

            return;
        }

        if ($before === null) {
            $this->line("  <fg=gray>Standard version {$after} is current. This app's copy is not stamped, so it was applied before stamping and may be well behind.</>");

            return;
        }

        if ($before === $after) {
            $this->line("  <fg=gray>Standard version {$before}, which is current.</>");

            return;
        }

        $this->components->warn("The standard moved since this app applied: {$before} is now {$after}.");
        $this->line('  <fg=gray>design/CHANGELOG.md says what changed, as of when this app applied.</>');
        $this->line("  <fg=gray>To pick it up: composer require the current major, then php artisan design:update {$against}.</>");
    }
}
