<?php

use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CatalogController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\WishlistController;
use Illuminate\Support\Facades\Route;

// Public browsing — no auth required, so guests can window-shop.
// throttle:60,1 -> generous limit for normal browsing, but still stops
// scripted scraping/search abuse from hammering the DB.
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/shop', [CatalogController::class, 'index'])->name('shop.index');
    Route::get('/shop/{product:slug}', [CatalogController::class, 'show'])->name('shop.show');
});

// Everything below requires login — cart/wishlist/profile are personal to the account.
Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{product}', [CartController::class, 'destroy'])->name('cart.destroy');

    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{product}', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
        ->middleware('throttle:5,1') // password change is a sensitive action — extra throttle layer
        ->name('profile.password');
});
