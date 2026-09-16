<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Designs move out of the repo and into the database.
 *
 * They were JSON files under resources/designs, written by the New design page
 * and the controls on a design page. Laravel Cloud does not keep a container's
 * filesystem across a deploy, so a design made on the site was gone on the next
 * one, and invisible to any other instance in the meantime. Nothing was lost:
 * production listed exactly the five files that were in git, because nobody had
 * pressed the button.
 *
 * Those five are imported here rather than by a seeder, so a fresh database has
 * them whether it was made by a deploy, by a new clone or by a test run. The
 * files stay in database/seeders/designs as the seed. They are not the live copy
 * any more, and the directory name is meant to say so.
 */
return new class extends Migration
{
    private const SEEDS = __DIR__.'/../seeders/designs';

    public function up(): void
    {
        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            // The name a kit knows a design by. Every export, every route and
            // every applied repo carries it, so it is the natural key.
            $table->string('slug')->unique();
            // jsonb rather than json on Postgres: it is the one with operators
            // and indexes, and the brief is the obvious thing to search later.
            $table->jsonb('brief');
            $table->jsonb('decisions');
            // When the model chose, not when a person last hit Save, and null
            // for a design written by hand. A string, not a timestamp: it is a
            // record of what the generator reported and it goes back out in the
            // export byte for byte. One of the seeds carries +08:00, which a
            // timestamp column would flatten to +00:00 and move by eight hours.
            // created_at and updated_at are the times worth querying.
            $table->string('generated_at')->nullable();
            $table->jsonb('generator')->nullable();
            $table->timestamps();
        });

        $this->importSeeds();
    }

    public function down(): void
    {
        Schema::dropIfExists('designs');
    }

    /**
     * Raw inserts rather than the model, because a migration has to keep
     * working after the class it was written against has moved on. If the seed
     * directory is ever removed this quietly does nothing, which is the right
     * answer for a database that already has its designs.
     */
    private function importSeeds(): void
    {
        $now = now();

        foreach (File::glob(self::SEEDS.'/*.json') as $path) {
            $data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

            DB::table('designs')->insert([
                'slug' => basename($path, '.json'),
                'brief' => json_encode($data['brief'] ?? []),
                'decisions' => json_encode($data['decisions'] ?? []),
                'generated_at' => $data['generated_at'] ?? null,
                'generator' => isset($data['generator']) ? json_encode($data['generator']) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
