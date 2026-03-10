<?php

use App\Http\Controllers\Api\Cart\CartController;
use App\Http\Controllers\Api\Catalog\CatalogController;
use Illuminate\Support\Facades\Route;

Route::middleware('resolve.current.site')->group(function (): void {
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::get('/products', [CatalogController::class, 'index']);
    Route::get('/products/{product}', [CatalogController::class, 'show']);
});

require __DIR__.'/backoffice.php';
require __DIR__.'/feeds.php';
