<?php

use App\Http\Controllers\Tma\AuthController;
use App\Http\Controllers\Tma\CheckoutController;
use App\Http\Controllers\Tma\OrderController;
use App\Http\Controllers\Tma\ShopController;
use App\Http\Middleware\EnsureTelegramUser;
use Illuminate\Support\Facades\Route;

Route::prefix('tma')->name('tma.')->group(function () {
    Route::view('login', 'tma.login')->name('login');
    Route::post('auth', [AuthController::class, 'store'])->middleware('throttle:20,1,tma-auth')->name('auth');

    Route::middleware(EnsureTelegramUser::class)->group(function () {
        Route::get('/', [ShopController::class, 'catalog'])->name('home');
        Route::get('products/{product:slug}', [ShopController::class, 'product'])->name('products.show');
        Route::get('cart', [ShopController::class, 'cart'])->name('cart');

        Route::get('checkout', [CheckoutController::class, 'show'])->name('checkout');
        Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    });
});
