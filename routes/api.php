<?php

use App\Http\Controllers\Api\Catalog\CatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [CatalogController::class, 'index']);

require __DIR__.'/backoffice.php';
require __DIR__.'/feeds.php';
