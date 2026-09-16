<?php

namespace App\Console\Commands;

use App\Design\Generation\DesignGenerator;
use App\Design\Generation\GenerationFailed;
use Illuminate\Console\Command;

/**
 * The generate button, from a terminal. Same brief fields as the form, so the
 * acceptance test can be scripted: two briefs in, compare the decisions, run
 * design:check on both.
 */
class DesignGenerateCommand extends Command
{
    protected $signature = 'design:generate
        {client : Who the app is for}
        {product : One or two sentences: what the app does and who uses it day to day}
        {--shell=sidebar : sidebar or top-nav}
        {--brand-colour= : A hex the client already owns}
        {--notes= : Anything else}
        {--slug= : The name the design is saved under; derived from the client when omitted}
        {--dry-run : Print the decisions without saving}';

    protected $description = 'Generate the seven design decisions for a brief and save them as a design';

    public function handle(DesignGenerator $generator): int
    {
        $brief = [
            'client' => $this->argument('client'),
            'product' => $this->argument('product'),
            'shell' => $this->option('shell'),
            'brand_colour' => $this->option('brand-colour'),
            'notes' => $this->option('notes'),
        ];

        $this->components->info('Asking '.config('design.model').' for the seven decisions.');

        try {
            $design = $generator->generate($brief, $this->option('slug') ?: null);
        } catch (GenerationFailed $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($design->decisions as $key => $decision) {
            $value = $key === 'copy'
                ? implode(' / ', array_filter([$decision['eyebrow'] ?? null, $decision['headline'] ?? null, $decision['tagline'] ?? null]))
                : (string) $decision['value'];
            $this->components->twoColumnDetail("<fg=cyan>{$key}</>", $value);
            $this->line("    <fg=gray>{$decision['why']}</>");
        }

        $contrast = $design->tokens()['contrast'];
        $this->line('');
        $this->components->twoColumnDetail('Contrast', $contrast['passes'] ? '<fg=green>passes</> (lowest '.$contrast['lowest']['ratio'].':1, '.$contrast['lowest']['pair'].')' : '<fg=red>FAILS</>');
        $this->components->twoColumnDetail('Model', $design->generator['model'].' in '.$design->generator['duration_ms'].' ms');
        $usage = [];
        foreach ((array) ($design->generator['usage'] ?? []) as $name => $count) {
            if (is_int($count)) {
                $usage[] = "{$name} {$count}";
            }
        }
        if ($usage !== []) {
            $this->components->twoColumnDetail('Tokens', implode(', ', $usage));
        }

        if ($this->option('dry-run')) {
            $this->components->warn('Dry run, nothing saved.');

            return self::SUCCESS;
        }

        $design->save();
        $this->components->info("Saved {$design->slug}. Open ".url('/designs/'.$design->slug));

        return self::SUCCESS;
    }
}
