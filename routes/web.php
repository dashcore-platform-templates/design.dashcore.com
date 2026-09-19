<?php

use App\Http\Controllers\Design\ExportController;
use App\Http\Middleware\CanPullADesign;
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

    // Who can get in. There is no sign-up form, so somebody already in has to
    // do it, and until now that meant a terminal on the production host.
    Route::livewire('people', 'pages::people')->name('people');
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
    // The screens a design does not decide. An index of categories, then every
    // block in the one you open: the index costs the same however many blocks
    // there are, and only the category you asked for draws them.
    Route::livewire('blocks', 'pages::blocks.index')->name('blocks.index');
    Route::livewire('blocks/{category}', 'pages::blocks.show')->name('blocks.show');
    Route::livewire('changelog', 'pages::changelog')->name('changelog');

    Route::livewire('designs', 'pages::designs.index')->name('designs.index');
    // Both above the {slug} route, or 'new' and 'compare' are read as slugs
    // and 404.
    Route::livewire('designs/new', 'pages::designs.create')->name('designs.create');
    Route::livewire('designs/compare', 'pages::designs.compare')->name('designs.compare');
    Route::livewire('designs/{slug}', 'pages::designs.show')->name('designs.show');
});

/*
| The exports. Not open, and not behind the login either.
|
| A pack carries the brief, which names the client and says what their product
| does, so a slug should not be enough to read one. But these are fetched by
| `design:apply` from a terminal, which has no session, so the page gate cannot
| serve here.
|
| CanPullADesign takes either: a signed-in person clicking Download pack, or the
| token from that person's account in DESIGN_TOKEN. Not a repository's
| credential, because a repository never pulls: a workflow reports and stops.
*/
Route::middleware(CanPullADesign::class)->group(function () {
    Route::get('designs/{slug}/design.json', [ExportController::class, 'json'])->name('designs.json');
    Route::get('designs/{slug}/pack.zip', [ExportController::class, 'pack'])->name('designs.pack');
    Route::get('designs/{slug}/pack.json', [ExportController::class, 'manifest'])->name('designs.manifest');
});
