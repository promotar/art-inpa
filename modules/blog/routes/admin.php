<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\Admin\BlogAdminController;
use Modules\Blog\Http\Controllers\Admin\CategoryController;
use Modules\Blog\Http\Controllers\Admin\PostController;

Route::middleware('permission:blog.view')->group(function (): void {
    Route::get('/', [BlogAdminController::class, 'index'])->name('index');
    Route::get('/settings', [BlogAdminController::class, 'settings'])->name('settings.edit');
    Route::get('posts/{post}/preview', [PostController::class, 'preview'])->name('posts.preview');
    Route::get('posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
});

Route::middleware('permission:blog.create')->group(function (): void {
    Route::post('posts/slug', [PostController::class, 'slug'])->name('posts.slug');
    Route::get('posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('posts', [PostController::class, 'store'])->name('posts.store');
});

Route::middleware('permission:blog.update')->group(function (): void {
    Route::post('posts/autosave', [PostController::class, 'autosave'])->name('posts.autosave');
    Route::get('posts/{post}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::match(['put', 'patch'], 'posts/{post}', [PostController::class, 'update'])->name('posts.update');
});

Route::middleware('permission:blog.delete')->group(function (): void {
    Route::delete('posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::delete('posts/{post}/revisions/{revision}', [PostController::class, 'destroyRevision'])->name('posts.revisions.destroy');
});

Route::post('posts/{post}/revisions/{revision}/restore', [PostController::class, 'restoreRevision'])
    ->middleware('permission:blog.revisions.restore')
    ->name('posts.revisions.restore');

Route::middleware('permission:blog.media.manage')->group(function (): void {
    Route::get('media', [PostController::class, 'mediaLibrary'])->name('media.index');
    Route::post('media', [PostController::class, 'uploadMedia'])->name('media.store');
    Route::patch('media/{media}', [PostController::class, 'updateMedia'])->name('media.update');
    Route::delete('media/{media}', [PostController::class, 'destroyMedia'])->name('media.destroy');
});

Route::middleware('permission:blog.categories.manage')->group(function (): void {
    Route::post('categories/quick', [CategoryController::class, 'quickStore'])->name('categories.quick-store');
    Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::match(['put', 'patch'], 'categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
});
