<?php

use App\Http\Controllers\Api\CustomerAuth\CustomerAuthController;
use App\Http\Controllers\Api\CustomerProfile\CustomerProfileController;
use App\Http\Controllers\Api\Cart\CartController;
use App\Http\Controllers\Api\Catalog\CatalogController;
use App\Http\Controllers\Api\Checkout\BankTransferDetailsController;
use App\Http\Controllers\Api\Checkout\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::middleware('resolve.current.site')->group(function (): void {
    Route::post('/auth/login', [CustomerAuthController::class, 'login']);
    Route::post('/auth/logout', [CustomerAuthController::class, 'logout']);
    Route::post('/auth/refresh', [CustomerAuthController::class, 'refresh']);
    Route::post('/auth/register', [CustomerAuthController::class, 'register']);
    Route::get('/me', [CustomerProfileController::class, 'show'])->middleware('auth:customer');
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::patch('/cart/items/{product}', [CartController::class, 'setItemQuantity']);
    Route::delete('/cart/items/{product}', [CartController::class, 'removeItem']);
    Route::get('/bank-transfer-details', [BankTransferDetailsController::class, 'show']);
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('auth:customer');
    Route::get('/products', [CatalogController::class, 'index']);
    Route::get('/products/{product}', [CatalogController::class, 'show']);
});

require __DIR__.'/backoffice.php';
require __DIR__.'/feeds.php';
