<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * jsonb was the wrong type for a design, and it shipped.
 *
 * Postgres `jsonb` stores a decomposed binary form and does not keep the order
 * of object keys. A design's key order is part of a contract: the export is
 * byte-compared by every kit that pulls the pack, and a kit that sees different
 * bytes has a change to review that nobody made. Production deployed with jsonb
 * and reordered every design on the way out, at the same byte length.
 *
 * `json` keeps an exact copy of the input text, so the order survives. It gives
 * up the operators and indexes jsonb has, which is the right trade here: a
 * design is read whole, written whole and handed straight back out. site_reports
 * keeps jsonb, because its findings are aggregated and queried and never
 * re-exported verbatim.
 *
 * The seeds are written again afterwards, because the order in the rows was
 * already lost. Only the five in database/seeders/designs are touched, and only
 * their three JSON columns, so anything made since is left alone.
 *
 * The earlier migration is left as it was. It ran in production, and rewriting
 * it would hide why this one exists.
 */
return new class extends Migration
{
    private const SEEDS = __DIR__.'/../seeders/designs';

    private const COLUMNS = ['brief', 'decisions', 'generator'];

    public function up(): void
    {
        $this->retype('json');
        $this->rewriteSeeds();
    }

    public function down(): void
    {
        $this->retype('jsonb');
    }

    /**
     * Postgres only. SQLite holds all of it as TEXT and keeps the order either
     * way, which is exactly why this got through the local suite.
     */
    private function retype(string $type): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::COLUMNS as $column) {
            DB::statement("ALTER TABLE designs ALTER COLUMN {$column} TYPE {$type} USING {$column}::text::{$type}");
        }
    }

    private function rewriteSeeds(): void
    {
        foreach (File::glob(self::SEEDS.'/*.json') as $path) {
            $data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

            DB::table('designs')->where('slug', basename($path, '.json'))->update([
                'brief' => json_encode($data['brief'] ?? []),
                'decisions' => json_encode($data['decisions'] ?? []),
                'generator' => isset($data['generator']) ? json_encode($data['generator']) : null,
            ]);
        }
    }
};
