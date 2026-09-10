<?php

namespace Dashcore\DesignKit\Commands;

use Dashcore\DesignKit\Applier;
use Dashcore\DesignKit\Contrast;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use JsonException;

/** php artisan design:check design/design.json — measure contrast on the file this kit carries. */
class CheckCommand extends Command
{
    protected $signature = 'design:check {file=design/design.json : The design file to measure}';

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

        return $result['passes'] ? self::SUCCESS : self::FAILURE;
    }
}
