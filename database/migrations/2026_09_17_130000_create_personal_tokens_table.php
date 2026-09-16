<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A person's own credential, for the things a person runs.
 *
 * CI proves which repository it is with a token GitHub mints for the job, so
 * it needs nothing stored here. But `design:apply` and `design:update` are run
 * by a person or by an agent working for one, on a laptop, where there is no
 * such proof. This is what those carry.
 *
 * One per machine rather than one per person, so a laptop can be cut off
 * without taking the rest with it. Named by whoever made it, because a list of
 * identical rows is a list nobody can act on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // sha256, like a site's. A token is 40 random characters, so there
            // is nothing to brute force and every pull would otherwise pay for
            // a slow hash.
            $table->string('token_hash', 64)->unique();
            // So a list of tokens can be read for the one nobody recognises,
            // which is the only way somebody notices a token they did not make.
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_tokens');
    }
};
