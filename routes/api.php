<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Mobile\MobileAuthController;

Route::prefix('mobile')->group(function () {

    Route::post('/login', [MobileAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/me', [MobileAuthController::class, 'me']);

        Route::post('/logout', [MobileAuthController::class, 'logout']);
    });
});
