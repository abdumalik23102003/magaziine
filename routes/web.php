<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductController::class, 'index'])->name('home');

// Vaqtincha statik demo sahifalar (keyingi bosqichlarda dinamik bo'ladi)
Route::view('/checkout', 'pages.checkout')->name('checkout');
Route::view('/profile', 'pages.profile')->name('profile');
Route::view('/product', 'pages.product')->name('product');
