<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

//Route::view('/', 'pages.home')->name('home');
Route::get('/', [ProductController::class, 'index'])->name('home');
Route::view('/checkout', 'pages.checkout')->name('checkout');
Route::view('/profile', 'pages.profile')->name('profile');
Route::view('/product', 'pages.product')->name('product');
