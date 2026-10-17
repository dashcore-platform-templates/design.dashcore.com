<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An app on the dashcore style reports the style rather than a design, and an
 * app held by the ratchet reports the total its allowance permits, so the
 * board can show both. See docs/fleet-design-plan.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_reports', function (Blueprint $table) {
            $table->string('style')->nullable()->after('design_slug');
            $table->unsignedInteger('allowance')->nullable()->after('total_findings');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->string('style')->nullable()->after('design_slug');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('style');
        });

        Schema::table('site_reports', function (Blueprint $table) {
            $table->dropColumn(['style', 'allowance']);
        });
    }
};
