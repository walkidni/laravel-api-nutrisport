<?php

use App\Http\Controllers\Api\Backoffice\BackofficeAuthController;
use App\Http\Controllers\Api\Backoffice\BackofficeOrderController;
use App\Http\Controllers\Api\Backoffice\BackofficeProductController;
use Illuminate\Support\Facades\Route;

Route::post('/backoffice/auth/login', [BackofficeAuthController::class, 'login']);
Route::post('/backoffice/auth/refresh', [BackofficeAuthController::class, 'refresh']);
Route::post('/backoffice/auth/logout', [BackofficeAuthController::class, 'logout']);

Route::middleware('auth:backoffice')->group(function (): void {
    Route::get('/backoffice/orders', [BackofficeOrderController::class, 'index']);
    Route::post('/backoffice/products', [BackofficeProductController::class, 'store']);
});
