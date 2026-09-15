<?php

use App\Http\Controllers\Fleet\ReportController;
use Illuminate\Support\Facades\Route;

/*
| Machine callers, authenticated by a site token rather than a session.
|
| Separate from web.php so these carry no session and no CSRF check: a kit's
| CI has neither, and excluding one route from the web middleware would be the
| same thing said less clearly.
*/

Route::post('reports', [ReportController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('api.reports.store');
