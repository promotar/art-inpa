<?php

use Illuminate\Support\Facades\Route;
use Modules\AiCore\AiCoreAdminController;

Route::get('/', [AiCoreAdminController::class, 'index'])->name('index');
