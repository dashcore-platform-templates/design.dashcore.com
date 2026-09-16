<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retiring a design without breaking the apps wearing it.
 *
 * Delete was the only way to get a design off the board, and a design is not
 * only a row here: every repo that ran `design:apply <slug>` pulls that slug's
 * pack on each update. Archiving takes it off the board and out of the nav and
 * leaves every export answering exactly as before, which is the whole point.
 *
 * A real timestamp, unlike generated_at. This one is an event in the tool and
 * is never exported, so nothing downstream sees it and nothing depends on its
 * format.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('generator');
        });
    }

    public function down(): void
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
