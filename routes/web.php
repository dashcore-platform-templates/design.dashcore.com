<?php

use App\Http\Controllers\Design\ExportController;
use Illuminate\Support\Facades\Route;

// Designs are rows in `designs`; the standard is markdown in standard/. See
// standard/README.md.
//
// The sign-in screens are Fortify's, pointed at this kit's views by
// FortifyServiceProvider. Nothing for them here.

Route::redirect('/', '/designs')->name('home');

/*
| The dashboard. All of it needs a session.
|
| It lists every Dashcore product, the designs behind them and how far off the
| standard each one is. None of that is a public fact, and a tool where half
| the pages are open teaches people to guess which half.
*/
Route::middleware('auth')->group(function () {
    // Step one, and soon a prerequisite: a repository is connected here, gets
    // its token, and only then has anything to report or anything to pull.
    Route::livewire('connect', 'pages::connect')->name('connect');
    Route::livewire('fleet', 'pages::fleet')->name('fleet');

    Route::livewire('account', 'pages::account.index')->name('account');
    // Its own route so it can carry password confirmation: everything on it
    // changes how somebody proves who they are, and a session left open should
    // not be enough to reach it. A name change should not be behind that,
    // which is why the two are not one page.
    Route::livewire('account/security', 'pages::account.security')
        ->middleware('password.confirm')
        ->name('account.security');

    Route::livewire('standard', 'pages::standard')->name('standard');
    Route::livewire('changelog', 'pages::changelog')->name('changelog');

    Route::livewire('designs', 'pages::designs.index')->name('designs.index');
    // Above the {slug} route, or 'new' is read as a slug and 404s.
    Route::livewire('designs/new', 'pages::designs.create')->name('designs.create');
    Route::livewire('designs/{slug}', 'pages::designs.show')->name('designs.show');
});

/*
| The exports, and only the exports, stay open.
|
| A kit's CI has no session. `design:apply` and `design:update` fetch
| /designs/{slug}/pack.json over plain HTTP, so putting this behind the login
| would stop every app in the fleet from updating. The other two serve the same
| bytes by other routes, so closing them alone would buy nothing.
|
| This is the half that is still open, and it is open on purpose rather than by
| oversight: anyone who knows a slug can read a pack, including the brief, which
| names the client. Closing it needs a credential a kit can carry, which is a
| decision of its own rather than a side effect of locking the pages. Until then
| the pages no longer list the slugs, so reading a pack means guessing a name
| rather than picking one off a page.
*/
Route::get('designs/{slug}/design.json', [ExportController::class, 'json'])->name('designs.json');
Route::get('designs/{slug}/pack.zip', [ExportController::class, 'pack'])->name('designs.pack');
Route::get('designs/{slug}/pack.json', [ExportController::class, 'manifest'])->name('designs.manifest');
