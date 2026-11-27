<?php

use App\Http\Controllers\TelegramBotWebhookController;
use Illuminate\Support\Facades\Route;

// "web" guruhidan tashqarida: sessiya ham, CSRF ham yo'q (Telegram serveri chaqiradi).
Route::post('/telegram/bot/webhook', TelegramBotWebhookController::class)
    ->middleware('throttle:600,1')
    ->name('telegram.bot.webhook');
