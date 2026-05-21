<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Mobile\MobileAuthController;
use App\Http\Controllers\Mobile\MobileDashboardController;

use App\Http\Controllers\Mobile\Admin\MobileAdminDriverController;
use App\Http\Controllers\Mobile\Admin\MobileAdminGuideController;
use App\Http\Controllers\Mobile\Admin\MobileAdminVehiculeController;
use App\Http\Controllers\Mobile\Admin\MobileAdminSupplierClientController;
use App\Http\Controllers\Mobile\Admin\MobileAdminSupplierVehiculeController;
use App\Http\Controllers\Mobile\Admin\MobileAdminPlanningController;

Route::prefix('mobile')->group(function () {

    Route::post('/login', [MobileAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/me', [MobileAuthController::class, 'me']);

        Route::get('/dashboard', [MobileDashboardController::class, 'index']);

        Route::post('/logout', [MobileAuthController::class, 'logout']);

        /*
        |--------------------------------------------------------------------------
        | Admin Mobile API
        |--------------------------------------------------------------------------
        */

        Route::prefix('admin')->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Drivers
            |--------------------------------------------------------------------------
            */
            Route::get('/drivers', [MobileAdminDriverController::class, 'index']);
            Route::get('/drivers/{driver}', [MobileAdminDriverController::class, 'show']);

            /*
            |--------------------------------------------------------------------------
            | Guides
            |--------------------------------------------------------------------------
            */
            Route::get('/guides', [MobileAdminGuideController::class, 'index']);
            Route::get('/guides/{guide}', [MobileAdminGuideController::class, 'show']);

            /*
            |--------------------------------------------------------------------------
            | Vehicules
            |--------------------------------------------------------------------------
            */
            Route::get('/vehicules', [MobileAdminVehiculeController::class, 'index']);
            Route::get('/vehicules/{vehicule}', [MobileAdminVehiculeController::class, 'show']);

            /*
            |--------------------------------------------------------------------------
            | Supplier Clients
            |--------------------------------------------------------------------------
            */
            Route::get('/supplier-clients', [MobileAdminSupplierClientController::class, 'index']);
            Route::get('/supplier-clients/{supplierClient}', [MobileAdminSupplierClientController::class, 'show']);

            /*
            |--------------------------------------------------------------------------
            | Supplier Vehicules
            |--------------------------------------------------------------------------
            */
            Route::get('/supplier-vehicules', [MobileAdminSupplierVehiculeController::class, 'index']);
            Route::get('/supplier-vehicules/{supplierVehicule}', [MobileAdminSupplierVehiculeController::class, 'show']);
            Route::get('/supplier-clients/{supplierClient}/replace-options', [MobileAdminSupplierClientController::class, 'replaceOptions']);
            Route::post('/supplier-clients/{supplierClient}/replace', [MobileAdminSupplierClientController::class, 'replace']);

            /*
            |--------------------------------------------------------------------------
            | Plannings
            |--------------------------------------------------------------------------
            */
            Route::get('/plannings', [MobileAdminPlanningController::class, 'index']);
            Route::get('/plannings/{planning}', [MobileAdminPlanningController::class, 'show']);
        });
    });
});
