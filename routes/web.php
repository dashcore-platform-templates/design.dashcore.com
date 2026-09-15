<?php

use App\Http\Controllers\Design\ExportController;
use Illuminate\Support\Facades\Route;

// The viewer. No auth, no database: designs are JSON files in resources/designs
// and the standard is markdown in standard/. See standard/README.md.

Route::redirect('/', '/designs')->name('home');

// The sign-in screens are Fortify's, pointed at this kit's views by
// FortifyServiceProvider. Nothing for them here.
//
// The one page behind sign-in. It lists every Dashcore product and how far
// off the standard each one is, which is not a public fact. Everything below
// stays open, because kits pull their pack over HTTP without a session.
Route::livewire('fleet', 'pages::fleet')->middleware('auth')->name('fleet');

Route::livewire('standard', 'pages::standard')->name('standard');
Route::livewire('changelog', 'pages::changelog')->name('changelog');

Route::livewire('designs', 'pages::designs.index')->name('designs.index');
Route::livewire('designs/new', 'pages::designs.create')->name('designs.create');
Route::livewire('designs/{slug}', 'pages::designs.show')->name('designs.show');

Route::get('designs/{slug}/design.json', [ExportController::class, 'json'])->name('designs.json');
Route::get('designs/{slug}/pack.zip', [ExportController::class, 'pack'])->name('designs.pack');
Route::get('designs/{slug}/pack.json', [ExportController::class, 'manifest'])->name('designs.manifest');
