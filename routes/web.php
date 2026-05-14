<?php

use App\Http\Controllers\AllUsersController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverFuelInvoiceController;
use App\Http\Controllers\DriverFuelInvoicePlanningController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\PlanningClientController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SupplierClientController;
use App\Http\Controllers\SupplierVehiculeController;
use App\Http\Controllers\SupplierVehiculeInvoiceController;
use App\Http\Controllers\SupplierVehiculeInvoicePlanningController;
use App\Http\Controllers\TypeServiceController;
use App\Http\Controllers\VehiculeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Profile
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Protected Application Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */
    Route::get('/reports', function () {
        $stats = [
            'total' => 120,
            'month' => 18,
        ];

        return Inertia::render('Reports/Index', [
            'stats' => $stats,
        ]);
    })->name('reports.index');

    /*
    |--------------------------------------------------------------------------
    | Plannings
    |--------------------------------------------------------------------------
    */
    Route::resource('plannings', PlanningController::class)
        ->except(['show', 'edit', 'create']);
    /*
    |--------------------------------------------------------------------------
    | Planning Clients
    |--------------------------------------------------------------------------
    */
    Route::resource('planning-clients', PlanningClientController::class);

    /*
    |--------------------------------------------------------------------------
    | destinations
    |--------------------------------------------------------------------------
    */
    Route::resource('destinations', DestinationController::class);

    /*
    |--------------------------------------------------------------------------
    | vehicules
    |--------------------------------------------------------------------------
    */
    Route::resource('vehicules', VehiculeController::class);
    /*
    |--------------------------------------------------------------------------
    | destinations
    |--------------------------------------------------------------------------
    */
    Route::resource('supplier-vehicules', SupplierVehiculeController::class);

    /*
    |--------------------------------------------------------------------------
    | supplier-clients
    |--------------------------------------------------------------------------
    */
    Route::resource('supplier-clients', SupplierClientController::class);

    /*
    |--------------------------------------------------------------------------
    | Clients
    |--------------------------------------------------------------------------
    */
    Route::resource('clients', ClientController::class);

    /*
    |--------------------------------------------------------------------------
    | Suppliers
    |--------------------------------------------------------------------------
    */
    // Route::resource('suppliers', SupplierController::class);

    /*
    |--------------------------------------------------------------------------
    | Drivers
    |--------------------------------------------------------------------------
    */
    Route::resource('drivers', DriverController::class);

    /*
    |--------------------------------------------------------------------------
    | Guides
    |--------------------------------------------------------------------------
    */
    Route::resource('guides', GuideController::class);

    /*
    |--------------------------------------------------------------------------
    | Services
    |--------------------------------------------------------------------------
    */
    Route::resource('services', ServiceController::class);

    /*
    |--------------------------------------------------------------------------
    | Type Suppliers
    |--------------------------------------------------------------------------
    */
    // Route::resource('type-suppliers', TypeSupplierController::class);

    /*
    |--------------------------------------------------------------------------
    | Type Services
    |--------------------------------------------------------------------------
    */
    Route::resource('type-services', TypeServiceController::class);

    /*
    |--------------------------------------------------------------------------
    | Historique
    |--------------------------------------------------------------------------
    */
    Route::get('/historique', [AuditController::class, 'myHistory'])
        ->name('historique.index');

    /*
    |--------------------------------------------------------------------------
    | Import Excel Plannings
    |--------------------------------------------------------------------------
    */
    Route::post('/plannings/import-excel', [PlanningController::class, 'importExcel'])
        ->name('plannings.importExcel');

    /*
    |--------------------------------------------------------------------------
    | Driver Fuel Invoices
    |--------------------------------------------------------------------------
    */
    Route::get('/driver-fuel-invoices', [DriverFuelInvoiceController::class, 'index'])
        ->name('driver-fuel-invoices.index');

    Route::get('/driver-fuel-invoices/create', [DriverFuelInvoiceController::class, 'create'])
        ->name('driver-fuel-invoices.create');

    Route::post('/driver-fuel-invoices', [DriverFuelInvoiceController::class, 'store'])
        ->name('driver-fuel-invoices.store');

    Route::get('/driver-fuel-invoices/{id}', [DriverFuelInvoiceController::class, 'show'])
        ->name('driver-fuel-invoices.show');

    Route::get('/driver-fuel-invoices/{id}/edit', [DriverFuelInvoiceController::class, 'edit'])
        ->name('driver-fuel-invoices.edit');

    Route::put('/driver-fuel-invoices/{id}', [DriverFuelInvoiceController::class, 'update'])
        ->name('driver-fuel-invoices.update');

    Route::delete('/driver-fuel-invoices/{id}', [DriverFuelInvoiceController::class, 'destroy'])
        ->name('driver-fuel-invoices.destroy');

    /*
    |--------------------------------------------------------------------------
    | Driver Fuel Invoice - helper routes
    |--------------------------------------------------------------------------
    */
    Route::get('/driver-fuel-invoices-driver-plannings', [DriverFuelInvoiceController::class, 'getDriverPlannings'])
        ->name('driver-fuel-invoices.driver-plannings');

    Route::post('/driver-fuel-invoice-plannings', [DriverFuelInvoicePlanningController::class, 'store'])
        ->name('driver-fuel-invoice-plannings.store');

    Route::put('/driver-fuel-invoice-plannings/{id}', [DriverFuelInvoicePlanningController::class, 'update'])
        ->name('driver-fuel-invoice-plannings.update');

    Route::delete('/driver-fuel-invoice-plannings/{id}', [DriverFuelInvoicePlanningController::class, 'destroy'])
        ->name('driver-fuel-invoice-plannings.destroy');

    Route::get('/driver-fuel-invoice-plannings/by-invoice/{invoiceId}', [DriverFuelInvoicePlanningController::class, 'byInvoice'])
        ->name('driver-fuel-invoice-plannings.by-invoice');

    /*
    |--------------------------------------------------------------------------
    | Users - admin only
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::resource('all-users', AllUsersController::class)
            ->names('all-users');

        Route::patch('/all-users/{user}/toggle-status', [AllUsersController::class, 'toggleStatus'])
            ->name('all-users.toggle-status');
    });


    // CRUD
    Route::resource('supplier-vehicule-invoices', SupplierVehiculeInvoiceController::class);

    Route::get(
        '/supplier-vehicule-invoices-plannings',
        [SupplierVehiculeInvoiceController::class, 'getSupplierVehiculePlannings']
    );

    // planning pivot
    Route::post(
        '/supplier-vehicule-invoice-plannings',
        [SupplierVehiculeInvoicePlanningController::class, 'store']
    );

    Route::put(
        '/supplier-vehicule-invoice-plannings/{id}',
        [SupplierVehiculeInvoicePlanningController::class, 'update']
    );

    Route::delete(
        '/supplier-vehicule-invoice-plannings/{id}',
        [SupplierVehiculeInvoicePlanningController::class, 'destroy']
    );

    Route::post('/supplier-clients/{supplierClient}/replace', [SupplierClientController::class, 'replace'])
        ->name('supplier-clients.replace');
});

Route::get('/plannings/print/supplier-clients', [PlanningController::class, 'printSupplierClients'])
    ->name('plannings.print.supplier-clients');

Route::get('/plannings/print/supplier-vehicules', [PlanningController::class, 'printSupplierVehicules'])
    ->name('plannings.print.supplier-vehicules');

require __DIR__ . '/auth.php';
