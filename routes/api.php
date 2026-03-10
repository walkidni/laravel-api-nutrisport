<?php

use App\Http\Controllers\Api\Catalog\CatalogController;
use Illuminate\Support\Facades\Route;

Route::middleware('resolve.current.site')->group(function (): void {
    Route::get('/products', [CatalogController::class, 'index']);
    Route::get('/products/{product}', [CatalogController::class, 'show']);
});

require __DIR__.'/backoffice.php';
require __DIR__.'/feeds.php';
