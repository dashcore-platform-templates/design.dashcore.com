<?php

use App\Design\Design;
use Illuminate\Contracts\Console\Kernel;

/**
 * Rewrites the fixtures from the real designs in the tool.
 *
 * Run from the design repo root, never from a kit:
 *   php packages/design-kit/tests/fixtures/refresh.php
 *
 * A fixture is a copy of what design.dashcore.com exports today. When the
 * export shape changes, refresh these and the package tests fail honestly
 * instead of passing against a file nothing produces any more.
 */
$root = dirname(__DIR__, 4);

if (! is_file("{$root}/artisan")) {
    fwrite(STDERR, "run this from the design repo, not from a kit\n");
    exit(1);
}

require "{$root}/vendor/autoload.php";
$app = require "{$root}/bootstrap/app.php";
$app->make(Kernel::class)->bootstrap();

foreach (['harbor-point', 'northline'] as $slug) {
    $design = Design::load($slug)->toArray();
    $path = __DIR__."/{$slug}.json";

    file_put_contents($path, json_encode($design, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
    printf("%-14s %d bytes\n", $slug, (int) filesize($path));
}
