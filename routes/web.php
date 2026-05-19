<?php

use Illuminate\Support\Facades\Route;
use Ridgeben\SmartChatbot\Http\Controllers\ChatbotController;

Route::middleware(config('smart-chatbot.route.middleware', ['web']))
    ->prefix(config('smart-chatbot.route.prefix', 'smart-chatbot'))
    ->group(function () {
        Route::post('/ask', [ChatbotController::class, 'ask'])
            ->name('smart-chatbot.ask');
    });