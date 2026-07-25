<?php

use Illuminate\Support\Facades\Route;
use Modules\AiAssistant\AiAssistantAdminController;

Route::get('/settings', [AiAssistantAdminController::class, 'edit'])->name('settings.edit');
Route::patch('/settings', [AiAssistantAdminController::class, 'update'])->name('settings.update');
