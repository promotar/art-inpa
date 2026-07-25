<?php

use Illuminate\Support\Facades\Route;
use Modules\AiAssistant\AiAssistantController;

Route::get('/ai-assistant/chat', [AiAssistantController::class, 'chat'])->name('ai-assistant.chat');
Route::get('/ai-assistant/messages', [AiAssistantController::class, 'messages'])
    ->middleware('throttle:60,1')
    ->name('ai-assistant.messages');
Route::post('/ai-assistant/message', [AiAssistantController::class, 'message'])
    ->middleware('throttle:30,1')
    ->name('ai-assistant.message');
Route::delete('/ai-assistant/conversation', [AiAssistantController::class, 'close'])
    ->middleware('throttle:30,1')
    ->name('ai-assistant.close');
