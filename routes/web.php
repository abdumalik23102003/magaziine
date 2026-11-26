<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CheckoutGeocodeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/categories/{category:slug}', [CatalogController::class, 'category'])->name('catalog.category');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::get('/brands/{brand:slug}', [BrandController::class, 'show'])->name('brands.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

// Savat (mehmon ham, foydalanuvchi ham)
Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
Route::get('/cart/summary', [CartController::class, 'summary'])->name('cart.summary');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{variantId}', [CartController::class, 'update'])->whereNumber('variantId')->name('cart.items.update');
Route::delete('/cart/items/{variantId}', [CartController::class, 'destroy'])->whereNumber('variantId')->name('cart.items.destroy');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

// Sevimlilar (mehmon ham, foydalanuvchi ham)
Route::get('/wishlist', [WishlistController::class, 'show'])->name('wishlist.show');
Route::get('/wishlist/summary', [WishlistController::class, 'summary'])->name('wishlist.summary');
Route::post('/wishlist/move-to-cart', [WishlistController::class, 'moveToCart'])->name('wishlist.move');
Route::delete('/wishlist', [WishlistController::class, 'clear'])->name('wishlist.clear');
Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->whereNumber('product')->name('wishlist.toggle');
Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy'])->whereNumber('productId')->name('wishlist.destroy');

// Joylashuv (header tugmasi)
Route::post('/location/resolve', [LocationController::class, 'resolve'])->middleware('throttle:10,1')->name('location.resolve');

// Mehmonlar uchun
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register.store');
});

// Faqat tizimga kirganlar
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('/checkout/geocode/reverse', [CheckoutGeocodeController::class, 'reverse'])->middleware('throttle:30,1')->name('checkout.geocode.reverse');
    Route::post('/checkout/geocode/search', [CheckoutGeocodeController::class, 'search'])->middleware('throttle:12,1')->name('checkout.geocode.search');

    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/reorder', [OrderController::class, 'reorder'])->name('orders.reorder');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
});

require __DIR__.'/support.php';

require __DIR__.'/engagement.php';
