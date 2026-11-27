<?php

use App\Http\Controllers\Auth\EmailCodeController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\TelegramWidgetController;
use App\Services\SocialAuthService;
use Illuminate\Support\Facades\Route;

// Parolsiz kirish usullari: email kod, Google/GitHub, Telegram. web.php oxirida ulanadi.
Route::middleware('guest')->group(function () {
    Route::get('/login/code', [EmailCodeController::class, 'create'])->name('login.code');
    Route::post('/login/code', [EmailCodeController::class, 'store'])->middleware('throttle:6,1,login-code-send')->name('login.code.store');
    Route::get('/login/code/verify', [EmailCodeController::class, 'verifyForm'])->name('login.code.verify');
    Route::post('/login/code/verify', [EmailCodeController::class, 'verify'])->middleware('throttle:10,1,login-code-verify')->name('login.code.confirm');

    Route::get('/auth/telegram/callback', [TelegramWidgetController::class, 'callback'])->middleware('throttle:20,1,telegram-widget')->name('auth.telegram.callback');

    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->whereIn('provider', array_keys(SocialAuthService::PROVIDERS))
        ->middleware('throttle:20,1,social-redirect')
        ->name('auth.social.redirect');

    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->whereIn('provider', array_keys(SocialAuthService::PROVIDERS))
        ->middleware('throttle:20,1,social-callback')
        ->name('auth.social.callback');
});
