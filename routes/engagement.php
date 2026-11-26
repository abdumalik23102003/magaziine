<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:20,1'])->group(function () {
    Route::post('/products/{product:slug}/comments', [CommentController::class, 'storeProduct'])->name('products.comments.store');
    Route::post('/blog/{post}/comments', [CommentController::class, 'storePost'])->name('blog.comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
});

Route::get('/{page}', PageController::class)
    ->whereIn('page', ['refund-policy', 'delivery-area', 'privacy-policy', 'terms-of-service', 'security'])
    ->name('pages.show');
