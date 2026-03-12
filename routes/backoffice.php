<?php

use App\Http\Controllers\Api\Backoffice\BackofficeAuthController;
use Illuminate\Support\Facades\Route;

Route::post('/backoffice/auth/login', [BackofficeAuthController::class, 'login']);
Route::post('/backoffice/auth/refresh', [BackofficeAuthController::class, 'refresh']);
Route::post('/backoffice/auth/logout', [BackofficeAuthController::class, 'logout']);
