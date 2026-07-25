<?php

use Illuminate\Support\Facades\Route;
use Modules\ProfessionalProgrammer\ProfessionalProgrammerAdminController;
use Modules\ProfessionalProgrammer\ProfessionalProgrammerController;

Route::get('/', [ProfessionalProgrammerAdminController::class, 'index'])->name('index');
Route::patch('/settings', [ProfessionalProgrammerAdminController::class, 'update'])->name('settings.update');
Route::post('/learn', [ProfessionalProgrammerAdminController::class, 'learn'])->name('learn');
Route::post('/verify-learning', [ProfessionalProgrammerAdminController::class, 'verifyLearning'])->name('verify-learning');
Route::post('/scan', [ProfessionalProgrammerAdminController::class, 'scan'])->name('scan');
Route::patch('/incidents/{incident}/resolve', [ProfessionalProgrammerAdminController::class, 'resolve'])->name('incidents.resolve');

Route::get('/alerts', [ProfessionalProgrammerController::class, 'alerts'])
    ->middleware('throttle:60,1')
    ->name('alerts');
Route::post('/message', [ProfessionalProgrammerController::class, 'message'])
    ->middleware('throttle:30,1')
    ->name('message');
Route::post('/approve', [ProfessionalProgrammerController::class, 'approve'])
    ->middleware('throttle:20,1')
    ->name('approve');
