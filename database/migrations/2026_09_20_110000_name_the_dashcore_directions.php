<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Give each house direction a name of its own.
 *
 * Five designs answer "Dashcore" as their client, which is right: they are
 * all Dashcore's, and the client is the brand an applied app starts with.
 * But the board and the nav showed that same word five times, with only the
 * slug and a line of product copy to tell them apart. A design now carries an
 * optional `name` in its brief, which is what the board calls it, and the
 * client stays what the mockups wear.
 *
 * Only rows that have no name yet, and only these five: a name somebody has
 * since typed on the design page is theirs.
 */
return new class extends Migration
{
    private const NAMES = [
        'dashcore' => 'Dashcore Tool',
        'dashcore-marketing' => 'Dashcore Blue',
        'dashcore-brown' => 'Dashcore Brown',
        'dashcore-ceo' => 'Dashcore CEO',
        'dashcore-product-executiveos' => 'ExecutiveOS',
    ];

    public function up(): void
    {
        foreach (self::NAMES as $slug => $name) {
            $row = DB::table('designs')->where('slug', $slug)->first(['id', 'brief']);

            if ($row === null) {
                continue;
            }

            $brief = json_decode((string) $row->brief, true) ?: [];

            if (trim((string) ($brief['name'] ?? '')) !== '') {
                continue;
            }

            // Client first, then the name, then the rest as it was.
            $brief = ['client' => $brief['client'] ?? null, 'name' => $name] + $brief;

            DB::table('designs')->where('id', $row->id)->update([
                'brief' => json_encode($brief, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Left alone on the way down. A name is a label, and taking it back
     * would put five cards called Dashcore on the board again.
     */
    public function down(): void {}
};
