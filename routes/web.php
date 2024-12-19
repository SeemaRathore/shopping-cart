<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;

Route::get('/', function () {
    return view('welcome');
});

// Product and Cart routes
Route::post('/cart/add/{id}', [CartController::class, 'addToCart'])->name('cart.add');
Route::get('/cart', [CartController::class, 'viewCart'])->name('viewCart');
Route::patch('/cart/update/{id}', [CartController::class, 'updateQuantity'])->name('cart.update');
Route::delete('/cart/remove/{id}', [CartController::class, 'removeProduct'])->name('cart.remove');

// Authenticated routes with GuestCartMiddleware
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'App\Http\Middleware\GuestCartMiddleware',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'processCheckout'])->name('checkout.process');
    Route::get('/order/complete/{orderId}', [CheckoutController::class, 'orderComplete'])->name('order.complete');
});



