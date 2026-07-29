<?php

use Illuminate\Support\Facades\Route;
use Modules\PageBuilder\Http\Controllers\Admin\PageController;

Route::middleware('permission:pages.manage')->group(function (): void {
    Route::get('/admin/theme-builder', [PageController::class, 'themeBuilder'])->name('admin.theme-builder.index');
    Route::put('/admin/theme-builder', [PageController::class, 'updateThemeBuilder'])->name('admin.theme-builder.update');
    Route::get('/admin/pages/editor-ui.css', [PageController::class, 'editorUiCss'])->name('admin.pages.editor-ui-css');
    Route::get('/admin/pages/theme-builder', [PageController::class, 'themeBuilder'])->name('admin.pages.theme-builder');
    Route::put('/admin/pages/theme-builder', [PageController::class, 'updateThemeBuilder'])->name('admin.pages.theme-builder.update');
    Route::get('/admin/pages', [PageController::class, 'index'])->name('admin.pages.index');
    Route::get('/admin/pages/{page}/edit', [PageController::class, 'edit'])->name('admin.pages.edit');
    Route::get('/admin/pages/{page}/preview', [PageController::class, 'preview'])->name('admin.pages.preview');
    Route::get('/admin/pages/{page}/editor-preview.css', [PageController::class, 'editorPreviewCss'])->name('admin.pages.editor-preview-css');
    Route::post('/admin/pages/{page}/editor-component-preview', [PageController::class, 'editorComponentPreview'])->name('admin.pages.editor-component-preview');
    Route::get('/admin/pages/{page}/revisions', [PageController::class, 'revisions'])->name('admin.pages.revisions.index');
    Route::post('/admin/pages/{page}/revisions/{revision}/restore', [PageController::class, 'restoreRevision'])->name('admin.pages.revisions.restore');
    Route::get('/admin/pages/{page}/template/export', [PageController::class, 'exportTemplate'])->name('admin.pages.template.export');
    Route::post('/admin/pages/{page}/template/import', [PageController::class, 'importTemplate'])->name('admin.pages.template.import');
    Route::post('/admin/pages', [PageController::class, 'store'])->name('admin.pages.store');
    Route::patch('/admin/pages/{page}/builder-save', [PageController::class, 'builderSave'])->name('admin.pages.builder-save');
    Route::patch('/admin/pages/{page}/autosave', [PageController::class, 'autosave'])->name('admin.pages.autosave');
    Route::patch('/admin/pages/{page}/template-edit-save', [PageController::class, 'templateEditSave'])->name('admin.pages.template-edit-save');
    Route::patch('/admin/pages/{page}', [PageController::class, 'update'])->name('admin.pages.update');
    Route::delete('/admin/pages/bulk-delete', [PageController::class, 'bulkDestroy'])->name('admin.pages.bulk-destroy');
    Route::delete('/admin/pages/{page}', [PageController::class, 'destroy'])->name('admin.pages.destroy');

    Route::redirect('/admin/front-builder/pages', '/admin/pages')
        ->name('admin.front-builder.pages.index');
    Route::get('/admin/plugins/page-builder', [PageController::class, 'index'])
        ->name('admin.plugins.page-builder.index');
    Route::redirect('/admin/plugins/page-builder/pages', '/admin/pages')
        ->name('admin.plugins.page-builder.pages.index');
});
