<?php

namespace App\Console\Commands;

use App\Design\Design;
use Illuminate\Console\Command;

/**
 * Principle 4 of the standard: contrast is measured, not eyeballed. Resolves a
 * design and prints every checked pair. Exits non-zero if any fails, so it can
 * gate a commit.
 */
class DesignCheckCommand extends Command
{
    protected $signature = 'design:check {slug? : A design in resources/designs, or all when omitted}';

    protected $description = 'Measure contrast for every text role in a design, both registers';

    public function handle(): int
    {
        $designs = $this->argument('slug')
            ? collect([Design::load($this->argument('slug'))])
            : Design::all();

        $failed = false;

        foreach ($designs as $design) {
            $contrast = $design->tokens()['contrast'];
            $this->line('');
            $this->components->twoColumnDetail("<fg=cyan>{$design->slug}</>", $contrast['passes'] ? '<fg=green>passes</>' : '<fg=red>FAILS</>');

            foreach ($contrast['pairs'] as $pair) {
                $status = $pair['passes'] ? '<fg=green>ok</>' : '<fg=red>fail</>';
                $this->components->twoColumnDetail("  {$pair['pair']}", sprintf('%s  %5.2f (min %.1f)', $status, $pair['ratio'], $pair['min']));
            }

            foreach ($contrast['notes'] as $note) {
                $this->components->info($note);
            }

            $failed = $failed || ! $contrast['passes'];
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
