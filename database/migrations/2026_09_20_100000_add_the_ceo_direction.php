<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * The ceo direction: the house look as ceo.dashcore.com wears it today,
 * transcribed into the seven decisions so it can be compared with the blue
 * and brown directions and pulled into the rest of the fleet.
 *
 * Same shape as the migrations that carried the blue and brown ones: the
 * seeds are globbed by the migration that made the table, which has already
 * run everywhere that matters, so a new file there reaches a fresh clone and
 * nothing else.
 *
 * It inserts only when the slug is free, because a design is edited on the
 * site and overwriting would throw away whatever somebody had settled on.
 */
return new class extends Migration
{
    private const SLUG = 'dashcore-ceo';

    private const SEED = __DIR__.'/../seeders/designs/'.self::SLUG.'.json';

    public function up(): void
    {
        if (! File::exists(self::SEED) || DB::table('designs')->where('slug', self::SLUG)->exists()) {
            return;
        }

        $data = json_decode(File::get(self::SEED), true, 512, JSON_THROW_ON_ERROR);
        $now = now();

        DB::table('designs')->insert([
            'slug' => self::SLUG,
            'brief' => json_encode($data['brief'] ?? []),
            'decisions' => json_encode($data['decisions'] ?? []),
            'generated_at' => $data['generated_at'] ?? null,
            'generator' => isset($data['generator']) ? json_encode($data['generator']) : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Left alone on the way down. Rolling a migration back should not take a
     * design somebody has since pointed an app at.
     */
    public function down(): void {}
};
