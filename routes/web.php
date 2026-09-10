<?php

use App\Http\Controllers\Design\ExportController;
use Illuminate\Support\Facades\Route;

// The viewer. No auth, no database: designs are JSON files in resources/designs
// and the standard is markdown in standard/. See standard/README.md.

Route::redirect('/', '/designs')->name('home');

Route::livewire('standard', 'pages::standard')->name('standard');

Route::livewire('designs', 'pages::designs.index')->name('designs.index');
Route::livewire('designs/{slug}', 'pages::designs.show')->name('designs.show');

Route::get('designs/{slug}/design.json', [ExportController::class, 'json'])->name('designs.json');
Route::get('designs/{slug}/pack.zip', [ExportController::class, 'pack'])->name('designs.pack');
