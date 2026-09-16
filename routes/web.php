<?php

use App\Http\Controllers\Design\ExportController;
use Illuminate\Support\Facades\Route;

// The viewer. No auth, no database: designs are JSON files in resources/designs
// and the standard is markdown in standard/. See standard/README.md.

Route::redirect('/', '/designs')->name('home');

// The sign-in screens are Fortify's, pointed at this kit's views by
// FortifyServiceProvider. Nothing for them here.
//
// The fleet lists every Dashcore product and how far off the standard each
// one is, which is not a public fact. The design pages stay open, because kits
// pull their pack over HTTP without a session.
Route::livewire('fleet', 'pages::fleet')->middleware('auth')->name('fleet');

// The account, behind the same gate. Security is its own route so it can carry
// password confirmation: everything on it changes how somebody proves who they
// are, and a session left open should not be enough to reach it. A name change
// should not be behind that, which is why the two are not one page.
Route::middleware('auth')->group(function () {
    Route::livewire('account', 'pages::account.index')->name('account');
    Route::livewire('account/security', 'pages::account.security')
        ->middleware('password.confirm')
        ->name('account.security');
});

Route::livewire('standard', 'pages::standard')->name('standard');
Route::livewire('changelog', 'pages::changelog')->name('changelog');

Route::livewire('designs', 'pages::designs.index')->name('designs.index');

// Above the {slug} route, or 'new' is read as a slug and 404s. Making one is
// not reading one: the generator costs a model call per press, and the result
// is a design every kit that pulls the pack will wear.
Route::livewire('designs/new', 'pages::designs.create')
    ->middleware('auth')
    ->name('designs.create');

Route::livewire('designs/{slug}', 'pages::designs.show')->name('designs.show');

Route::get('designs/{slug}/design.json', [ExportController::class, 'json'])->name('designs.json');
Route::get('designs/{slug}/pack.zip', [ExportController::class, 'pack'])->name('designs.pack');
Route::get('designs/{slug}/pack.json', [ExportController::class, 'manifest'])->name('designs.manifest');
