<?php

use App\Http\Controllers\Api\Feeds\FeedController;
use Illuminate\Support\Facades\Route;

Route::middleware('resolve.current.site')->group(function (): void {
    Route::get('/feeds', [FeedController::class, 'index'])->name('feeds.index');
    Route::get('/feeds/{format}', [FeedController::class, 'show'])->name('feeds.show');
});
