<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The apps running the standard.
 *
 * A row is created here first and its token goes into that app's CI. Reports
 * never create rows, so the table is the list of sites somebody put on it
 * rather than the list of things that have posted at it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();

            // What it is called and where it lives. The host is the thing a
            // person recognises in a list of fifteen.
            $table->string('name');
            $table->string('host')->unique();

            // Which design it took. Filled from the first report that names
            // one, and null until then, because a design file written before
            // tonight does not carry its slug.
            $table->string('design_slug')->nullable();

            // The sha256 of the token, never the token. High entropy, so a
            // fast hash is the right one: there is nothing to brute force.
            $table->string('token_hash', 64)->unique();

            $table->timestamp('last_reported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
