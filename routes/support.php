<?php

use App\Http\Controllers\SupportChatController;
use Illuminate\Support\Facades\Route;

Route::prefix('support/chat')->name('support.chat.')->group(function () {
    Route::get('/', [SupportChatController::class, 'show'])
        ->middleware('throttle:60,1,support-poll')->name('show');
    Route::get('messages', [SupportChatController::class, 'messages'])
        ->middleware('throttle:60,1,support-poll')->name('messages');
    Route::post('messages', [SupportChatController::class, 'store'])
        ->middleware('throttle:20,1,support-send')->name('store');
    Route::post('close', [SupportChatController::class, 'close'])
        ->middleware('throttle:20,1,support-send')->name('close');
});
