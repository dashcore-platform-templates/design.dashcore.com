<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a site said about itself, each time it said it.
 *
 * Every report is kept rather than one row per site overwritten, because the
 * useful question is not only "how far off is it" but "is it getting better".
 * A row is small and a site reports on push, so this grows slowly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();

            // The two versions that decide whether a site is behind: the
            // standard's stamp out of design/STANDARD.md, and the package that
            // carries the rules the counts below were produced by. A count
            // means nothing without knowing which rules produced it.
            $table->string('standard_version')->nullable();
            $table->string('package_version')->nullable();
            $table->string('design_slug')->nullable();

            // Counts by rule, not the findings themselves. The tool does not
            // need to know which line of somebody's view is wrong, and sending
            // that would put other people's source in this database.
            // jsonb rather than json on Postgres: it is the one with operators
            // and indexes, and the impact preview will ask questions inside
            // this column rather than only reading it back whole. SQLite has
            // no JSON type either way, so nothing changes locally.
            $table->jsonb('findings');
            $table->unsignedInteger('total_findings')->default(0);
            $table->unsignedInteger('files_with_findings')->default(0);
            $table->unsignedInteger('files_checked')->default(0);

            $table->timestamp('reported_at');
            $table->timestamps();

            // The fleet view reads the newest report per site, and the trend
            // reads a site's reports in order. Both are this index.
            $table->index(['site_id', 'reported_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_reports');
    }
};
