<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/categories/{category:slug}', [CatalogController::class, 'category'])->name('catalog.category');

Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

// Vaqtincha statik demo sahifalar (keyingi bosqichlarda dinamik bo'ladi)
Route::view('/checkout', 'pages.checkout')->name('checkout');
Route::view('/profile', 'pages.profile')->name('profile');
