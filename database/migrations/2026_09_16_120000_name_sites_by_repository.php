<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A site is identified by its repository, not by a hostname.
 *
 * The column was `host`, and the only thing it was used for was building a
 * link to that repo's Actions secrets, by assuming every repo is named after
 * the site it serves. That holds across the fleet today and is a convention
 * rather than a fact: there are repos with names that are not hostnames.
 *
 * Reports come from a repository's CI, the link goes to a repository, and
 * nothing ever verified the hostname, so the repository is the real key. The
 * `name` column already carries the label a person reads.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->renameColumn('host', 'repository');
        });

        // Rows written before this hold a bare hostname, which was always read
        // as a repo under the organisation. Say so outright rather than leaving
        // the assumption buried in a URL built at render time.
        DB::table('sites')
            ->where('repository', 'not like', '%/%')
            ->update(['repository' => DB::raw("'dashcoretech/' || repository")]);
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->renameColumn('repository', 'host');
        });
    }
};
