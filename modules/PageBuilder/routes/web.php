<?php

use Illuminate\Support\Facades\Route;
use Modules\PageBuilder\Http\Controllers\PublicPageController;

Route::get('/pages/{slug}', [PublicPageController::class, 'show'])->name('pages.show');
Route::get('/page/{slug}', fn (string $slug) => redirect()->route('pages.show', $slug, 301))
    ->name('front-builder.pages.show');
