<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Mobile\MobileAuthController;
use App\Http\Controllers\Mobile\MobileDashboardController;
use App\Http\Controllers\Mobile\MobileDeviceTokenController;
use App\Http\Controllers\Mobile\MobileRoadSheetController;

use App\Http\Controllers\Mobile\Admin\MobileAdminDriverController;
use App\Http\Controllers\Mobile\Admin\MobileAdminGuideController;
use App\Http\Controllers\Mobile\Admin\MobileAdminVehiculeController;
use App\Http\Controllers\Mobile\Admin\MobileAdminSupplierClientController;
use App\Http\Controllers\Mobile\Admin\MobileAdminSupplierVehiculeController;
use App\Http\Controllers\Mobile\Admin\MobileAdminPlanningController;
use App\Http\Controllers\Mobile\Admin\MobileAdminMailboxController;
use App\Http\Controllers\Mobile\Admin\MobileAdminVehicleMaintenanceController;
use App\Http\Controllers\Mobile\Admin\MobileAdminSupplierVehiculeInvoiceController;
use App\Http\Controllers\Mobile\Admin\MobileAdminDashboardController;

Route::prefix('mobile')->group(function () {

    Route::post('/login', [MobileAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'mobile.active'])->group(function () {

        Route::get('/me', [MobileAuthController::class, 'me']);

        Route::get('/dashboard', [MobileDashboardController::class, 'index']);

        Route::post('/device-tokens', [MobileDeviceTokenController::class, 'store']);
        Route::delete('/device-tokens', [MobileDeviceTokenController::class, 'destroy']);

        Route::post('/logout', [MobileAuthController::class, 'logout']);

        Route::get('/plannings', [MobileAdminPlanningController::class, 'index']);
        Route::get('/plannings/{planning}', [MobileAdminPlanningController::class, 'show']);

        Route::prefix('driver')->group(function () {
            Route::get('/road-sheets/plannings', [MobileRoadSheetController::class, 'index']);
            Route::get('/road-sheets/{planning}', [MobileRoadSheetController::class, 'show']);
            Route::post('/road-sheets/{planning}', [MobileRoadSheetController::class, 'store']);
        });

        /*
        |--------------------------------------------------------------------------
        | Admin Mobile API
        |--------------------------------------------------------------------------
        */

        Route::prefix('admin')->middleware('mobile.admin')->group(function () {

            Route::get('/dashboard', [MobileAdminDashboardController::class, 'index']);

            /*
            |--------------------------------------------------------------------------
            | Drivers
            |--------------------------------------------------------------------------
            */
            Route::get('/drivers', [MobileAdminDriverController::class, 'index'])->middleware('mobile.admin:drivers');
            Route::get('/drivers/{driver}', [MobileAdminDriverController::class, 'show'])->middleware('mobile.admin:drivers');

            /*
            |--------------------------------------------------------------------------
            | Guides
            |--------------------------------------------------------------------------
            */
            Route::get('/guides', [MobileAdminGuideController::class, 'index'])->middleware('mobile.admin:guides');
            Route::get('/guides/{guide}', [MobileAdminGuideController::class, 'show'])->middleware('mobile.admin:guides');

            /*
            |--------------------------------------------------------------------------
            | Vehicules
            |--------------------------------------------------------------------------
            */
            Route::get('/vehicules', [MobileAdminVehiculeController::class, 'index'])->middleware('mobile.admin:vehicles');
            Route::get('/vehicules/{vehicule}', [MobileAdminVehiculeController::class, 'show'])->middleware('mobile.admin:vehicles');

            /*
            |--------------------------------------------------------------------------
            | Supplier Clients
            |--------------------------------------------------------------------------
            */
            Route::get('/supplier-clients', [MobileAdminSupplierClientController::class, 'index'])->middleware('mobile.admin:supplier_clients');
            Route::get('/supplier-clients/{supplierClient}', [MobileAdminSupplierClientController::class, 'show'])->middleware('mobile.admin:supplier_clients');

            /*
            |--------------------------------------------------------------------------
            | Supplier Vehicules
            |--------------------------------------------------------------------------
            */
            Route::get('/supplier-vehicules', [MobileAdminSupplierVehiculeController::class, 'index'])->middleware('mobile.admin:supplier_vehicles');
            Route::get('/supplier-vehicules/{supplierVehicule}', [MobileAdminSupplierVehiculeController::class, 'show'])->middleware('mobile.admin:supplier_vehicles');
            Route::get('/supplier-clients/{supplierClient}/replace-options', [MobileAdminSupplierClientController::class, 'replaceOptions'])->middleware('mobile.admin:supplier_clients');
            Route::post('/supplier-clients/{supplierClient}/replace', [MobileAdminSupplierClientController::class, 'replace'])->middleware('mobile.admin:supplier_clients');

            /*
            |--------------------------------------------------------------------------
            | Plannings
            |--------------------------------------------------------------------------
            */
            Route::get('/plannings', [MobileAdminPlanningController::class, 'index'])->middleware('mobile.admin:plannings');
            Route::post('/plannings', [MobileAdminPlanningController::class, 'store'])->middleware('mobile.admin:plannings');
            Route::get('/plannings/{planning}', [MobileAdminPlanningController::class, 'show'])->middleware('mobile.admin:plannings');

            /*
            |--------------------------------------------------------------------------
            | Mailbox
            |--------------------------------------------------------------------------
            */
            Route::get('/mailbox', [MobileAdminMailboxController::class, 'index'])->middleware('mobile.admin:mailbox');
            Route::get('/mailbox/{message}', [MobileAdminMailboxController::class, 'show'])->middleware('mobile.admin:mailbox');

            /*
            |--------------------------------------------------------------------------
            | Vehicle Maintenances
            |--------------------------------------------------------------------------
            */
            Route::get('/vehicle-maintenances', [MobileAdminVehicleMaintenanceController::class, 'index'])->middleware('mobile.admin:maintenance');

            /*
            |--------------------------------------------------------------------------
            | Supplier Vehicule Invoices
            |--------------------------------------------------------------------------
            */
            Route::get('/supplier-vehicule-invoices', [MobileAdminSupplierVehiculeInvoiceController::class, 'index'])->middleware('mobile.admin:supplier_invoices');
            Route::get('/supplier-vehicule-invoices/{invoice}', [MobileAdminSupplierVehiculeInvoiceController::class, 'show'])->middleware('mobile.admin:supplier_invoices');
        });
    });
});
