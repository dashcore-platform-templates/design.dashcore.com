<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ship this app's inbound call log to the control plane, or it is absent from
// the traffic graph rather than idle.
Schedule::command('bridge:report-calls')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Report this app's own errors and route timings to health. Five past,
// because only closed windows are shipped.
Schedule::command('bridge:report-telemetry --prune')
    ->hourlyAt(5)
    ->withoutOverlapping()
    ->runInBackground();
