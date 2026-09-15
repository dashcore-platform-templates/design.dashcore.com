<?php

namespace Dashcore\DesignKit\Commands;

use Dashcore\DesignKit\Source;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * php artisan design:update <slug>
 *
 * Pick up a change to the standard on an app that is already on it. Setting an
 * app up is a different job with one-time steps in it, so this is not that:
 * nothing here installs the adapter, replaces the sign-in layout, or touches
 * CLAUDE.md.
 *
 * It does not run `composer update` for you. Updating the package whose code is
 * currently executing is not something a command should do to itself, and the
 * new audit rules arrive with that package, so it has to happen first. The
 * command says so rather than doing it.
 */
class UpdateCommand extends Command
{
    protected $signature = 'design:update
        {source? : The design slug or pack URL. Defaults to whatever design:apply defaults to.}
        {--force : Answer yes to the questions design:apply would ask}';

    protected $description = 'Pick up a change to the standard on an app already using it';

    public function handle(): int
    {
        $file = base_path('design/design.json');

        if (! File::exists($file)) {
            $this->components->error('This app has no design/design.json, so it is not on the standard yet.');
            $this->line('  <fg=gray>Use the Use this design panel on design.dashcore.com to set it up first.</>');

            return self::FAILURE;
        }

        $source = $this->argument('source');

        // The design file names its own slug, so an app on the standard can be
        // updated without anybody remembering which design it took.
        if (! is_string($source) || $source === '') {
            $design = json_decode(File::get($file), true);
            $source = is_array($design) ? Source::designSlug($design) : null;

            if ($source === null) {
                $this->components->error('Give the design slug or its pack URL, the same one you applied with.');
                $this->line('  <fg=gray>This app\'s design/design.json does not name one, which means it was applied</>');
                $this->line('  <fg=gray>before design files carried their slug. Passing it once writes it in.</>');
                $this->line('  <fg=gray>Example: php artisan design:update harbor-point</fg=gray>');

                return self::FAILURE;
            }

            $this->line("  <fg=gray>Using {$source}, which is the design this app already has.</>");
        }

        $this->components->info('Run `composer update dashcore/design-kit` before this if you have not. New audit rules arrive with the package.');

        $applied = $this->call('design:apply', array_filter([
            'source' => $source,
            '--force' => (bool) $this->option('force'),
        ]));

        if ($applied !== self::SUCCESS) {
            $this->components->error('The design did not apply, so nothing was audited. Fix that first.');

            return self::FAILURE;
        }

        $this->line('');
        $this->components->info('Now what in this app is off the standard as it reads today.');

        // The audit exits non-zero while anything is off, which is right for the
        // audit and wrong here. An update that reports work to do has worked.
        $this->call('design:audit');

        $this->line('');
        $this->components->info('Updated. Read design/CHANGELOG.md for what moved, then work the findings one screen per commit, as design/MIGRATION.md says.');

        return self::SUCCESS;
    }
}
