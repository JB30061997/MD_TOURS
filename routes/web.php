<?php

use App\Http\Controllers\AllUsersController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardMissingServiceController;
use App\Http\Controllers\DashboardMissingSupplierController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverWebController;
use App\Http\Controllers\DriverPrimeController;
use App\Http\Controllers\DriverFuelInvoiceController;
use App\Http\Controllers\DriverFuelInvoicePlanningController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\GenerateBonCommandeController;
use App\Http\Controllers\MailboxController;
use App\Http\Controllers\Mobile\MobileAuthController;
use App\Http\Controllers\PlanningClientController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\PlanningQuickReferenceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationDraftController;
use App\Http\Controllers\ReservateurController;
use App\Http\Controllers\ReservateurPortalController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\RoadSheetController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SupplierClientController;
use App\Http\Controllers\SupplierVehiculeController;
use App\Http\Controllers\SupplierVehiculeInvoiceController;
use App\Http\Controllers\SupplierVehiculeInvoicePlanningController;
use App\Http\Controllers\SupplierVehiculeTarifController;
use App\Http\Controllers\TypeServiceController;
use App\Http\Controllers\VehicleMaintenanceController;
use App\Http\Controllers\VehiculeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Webklex\PHPIMAP\Facades\Client;
use Inertia\Inertia;
use Webklex\IMAP\Facades\Client as FacadesClient;

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

Route::get('/reservateur/login', [ReservateurPortalController::class, 'login'])
    ->name('reservateur.login');
Route::post('/reservateur/login', [ReservateurPortalController::class, 'authenticate'])
    ->name('reservateur.authenticate');
Route::get('/reservateur/portal', [ReservateurPortalController::class, 'index'])
    ->name('reservateur.portal.index');
Route::post('/reservateur/reservations', [ReservateurPortalController::class, 'store'])
    ->name('reservateur.reservations.store');
Route::put('/reservateur/reservations/{reservation}', [ReservateurPortalController::class, 'update'])
    ->name('reservateur.reservations.update');
Route::post('/reservateur/reservations/{reservation}/cancel', [ReservateurPortalController::class, 'cancel'])
    ->name('reservateur.reservations.cancel');
Route::post('/reservateur/logout', [ReservateurPortalController::class, 'logout'])
    ->name('reservateur.logout');

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::patch('/dashboard/plannings/{planning}/service', [DashboardController::class, 'updatePlanningService'])
    ->middleware(['auth', 'verified', 'permission:plannings.edit'])
    ->name('dashboard.plannings.service.update');

Route::post('/dashboard/plannings/{planning}/supplier-invoice', [DashboardController::class, 'linkPlanningToSupplierInvoice'])
    ->middleware(['auth', 'verified', 'permission:supplier-vehicule-invoices.edit'])
    ->name('dashboard.plannings.supplier-invoice');

Route::prefix('/dashboard/missing-supplier-plannings')
    ->middleware(['auth', 'verified', 'permission:plannings.edit'])
    ->name('dashboard.missing-suppliers.')
    ->group(function () {
        Route::get('/', [DashboardMissingSupplierController::class, 'index'])->name('index');
        Route::post('/auto-assign-md-tours', [DashboardMissingSupplierController::class, 'autoAssign'])->name('auto-assign');
        Route::post('/assign', [DashboardMissingSupplierController::class, 'assign'])->name('assign');
    });

Route::prefix('/dashboard/missing-service-plannings')
    ->middleware(['auth', 'verified', 'permission:plannings.edit'])
    ->name('dashboard.missing-services.')
    ->group(function () {
        Route::get('/', [DashboardMissingServiceController::class, 'index'])->name('index');
        Route::post('/assign', [DashboardMissingServiceController::class, 'assign'])->name('assign');
    });

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

Route::prefix('driver')->name('driver.')->middleware(['auth', 'verified', 'role:driver'])->group(function () {
    Route::get('/dashboard', [DriverWebController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [DriverWebController::class, 'profile'])->name('profile');
    Route::get('/plannings', [DriverWebController::class, 'plannings'])->name('plannings.index');
    Route::get('/plannings/{planning}', [DriverWebController::class, 'planning'])->name('plannings.show');
    Route::get('/road-sheets', [DriverWebController::class, 'roadSheets'])->name('road-sheets.index');
    Route::get('/road-sheets/saved', [DriverWebController::class, 'savedRoadSheets'])->name('road-sheets.saved');
    Route::get('/road-sheets/{planning}/edit', [DriverWebController::class, 'editRoadSheet'])->name('road-sheets.edit');
    Route::put('/road-sheets/{planning}', [DriverWebController::class, 'updateRoadSheet'])->name('road-sheets.update');
});

/*
|--------------------------------------------------------------------------
| Protected Application Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Roles & Permissions
    |--------------------------------------------------------------------------
    */
    Route::get('/roles-permissions', [RolePermissionController::class, 'index'])
        ->name('roles-permissions.index');
    Route::post('/roles-permissions/roles', [RolePermissionController::class, 'storeRole'])
        ->name('roles-permissions.roles.store');
    Route::put('/roles-permissions/roles/{role}', [RolePermissionController::class, 'updateRole'])
        ->name('roles-permissions.roles.update');
    Route::delete('/roles-permissions/roles/{role}', [RolePermissionController::class, 'destroyRole'])
        ->name('roles-permissions.roles.destroy');
    Route::post('/roles-permissions/permissions', [RolePermissionController::class, 'storePermission'])
        ->name('roles-permissions.permissions.store');
    Route::put('/roles-permissions/permissions/{permission}', [RolePermissionController::class, 'updatePermission'])
        ->name('roles-permissions.permissions.update');
    Route::delete('/roles-permissions/permissions/{permission}', [RolePermissionController::class, 'destroyPermission'])
        ->name('roles-permissions.permissions.destroy');
    Route::patch('/roles-permissions/users/{user}/role', [RolePermissionController::class, 'assignUserRole'])
        ->name('roles-permissions.users.assign-role');

    /*
    |--------------------------------------------------------------------------
    | Generate BonCMD
    |--------------------------------------------------------------------------
    */
    Route::get('/generate-boncmd', [GenerateBonCommandeController::class, 'index'])
        ->name('generate-boncmd.index');
    Route::match(['get', 'post'], '/generate-boncmd/pdf', [GenerateBonCommandeController::class, 'pdf'])
        ->name('generate-boncmd.pdf');

    /*
    |--------------------------------------------------------------------------
    | Commandes
    |--------------------------------------------------------------------------
    */
    Route::get('/commandes/{commande}/pdf', [CommandeController::class, 'pdf'])
        ->name('commandes.pdf');
    Route::post('/commandes/{commande}/send-email', [CommandeController::class, 'sendEmail'])
        ->name('commandes.send-email');
    Route::get('/plannings/{planning}/commande', [CommandeController::class, 'openFromPlanning'])
        ->name('plannings.commande.open');
    Route::get('/plannings/{planning}/commande/pdf', [CommandeController::class, 'pdfFromPlanning'])
        ->name('plannings.commande.pdf');
    Route::post('/plannings/{planning}/commande/send-email', [CommandeController::class, 'sendEmailFromPlanning'])
        ->name('plannings.commande.send-email');
    Route::resource('commandes', CommandeController::class)
        ->except(['create', 'edit', 'show']);

    /*
    |--------------------------------------------------------------------------
    | Tarifs fournisseurs
    |--------------------------------------------------------------------------
    */
    Route::get('/supplier-vehicule-tarifs', [SupplierVehiculeTarifController::class, 'index'])
        ->name('supplier-vehicule-tarifs.index');
    Route::post('/supplier-vehicule-tarifs/matrix', [SupplierVehiculeTarifController::class, 'updateMatrix'])
        ->name('supplier-vehicule-tarifs.matrix.update');
    Route::post('/supplier-vehicule-tarifs/{supplierVehicule}/tarifs', [SupplierVehiculeTarifController::class, 'updateSupplierTarifs'])
        ->name('supplier-vehicule-tarifs.supplier.update');
    Route::post('/supplier-vehicule-tarifs/sync-from-plannings', [SupplierVehiculeTarifController::class, 'syncFromPlannings'])
        ->name('supplier-vehicule-tarifs.sync-from-plannings');
    Route::post('/supplier-vehicule-tarifs/quick-store', [SupplierVehiculeTarifController::class, 'quickStore'])
        ->name('supplier-vehicule-tarifs.quick-store');
    Route::post('/supplier-vehicule-tarifs/services', [SupplierVehiculeTarifController::class, 'storeService'])
        ->name('supplier-vehicule-tarifs.services.store');
    Route::put('/supplier-vehicule-tarifs/services/{service}', [SupplierVehiculeTarifController::class, 'updateService'])
        ->name('supplier-vehicule-tarifs.services.update');
    Route::delete('/supplier-vehicule-tarifs/services/{service}', [SupplierVehiculeTarifController::class, 'destroyService'])
        ->name('supplier-vehicule-tarifs.services.destroy');
    Route::post('/supplier-vehicule-tarifs/suppliers', [SupplierVehiculeTarifController::class, 'storeSupplier'])
        ->name('supplier-vehicule-tarifs.suppliers.store');

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
    Route::post('/plannings/reorder', [PlanningController::class, 'reorder'])
        ->name('plannings.reorder');

    Route::resource('plannings', PlanningController::class)
        ->except(['show', 'edit', 'create']);

    Route::get('/planning-quick/services', [PlanningQuickReferenceController::class, 'services'])
        ->name('services.quick.index');
    Route::post('/planning-quick/services', [PlanningQuickReferenceController::class, 'storeService'])
        ->name('services.quick.store');
    Route::get('/planning-quick/destinations', [PlanningQuickReferenceController::class, 'destinations'])
        ->name('destinations.quick.index');
    Route::post('/planning-quick/destinations', [PlanningQuickReferenceController::class, 'storeDestination'])
        ->name('destinations.quick.store');
    Route::get('/planning-quick/guides', [PlanningQuickReferenceController::class, 'guides'])
        ->name('guides.quick.index');
    Route::post('/planning-quick/guides', [PlanningQuickReferenceController::class, 'storeGuide'])
        ->name('guides.quick.store');

    Route::get('/road-sheets', [RoadSheetController::class, 'index'])
        ->name('road-sheets.index');
    Route::get('/plannings/{planning}/road-sheet', [RoadSheetController::class, 'show'])
        ->name('road-sheets.show');
    Route::get('/plannings/{planning}/road-sheet/pdf', [RoadSheetController::class, 'pdf'])
        ->name('road-sheets.pdf');
    Route::post('/plannings/{planning}/road-sheet/send-email', [RoadSheetController::class, 'sendEmail'])
        ->name('road-sheets.send-email');
    Route::put('/road-sheets/{roadSheet}', [RoadSheetController::class, 'update'])
        ->name('road-sheets.update');
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
    Route::post('/destinations/replace-selected', [DestinationController::class, 'replaceSelected'])
        ->name('destinations.replace-selected');
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

    Route::resource('reservateurs', ReservateurController::class)
        ->except(['show', 'create', 'edit', 'destroy']);
    Route::post('/reservateurs/{reservateur}/toggle', [ReservateurController::class, 'toggle'])
        ->name('reservateurs.toggle');

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
    Route::get('/driver-primes', [DriverPrimeController::class, 'index'])
        ->name('driver-primes.index');
    Route::get('/driver-primes/pdf', [DriverPrimeController::class, 'pdf'])
        ->name('driver-primes.pdf');

    Route::post('/drivers/replace-selected', [DriverController::class, 'replaceSelected'])
        ->name('drivers.replace-selected');

    Route::post('/drivers/{driver}/vehicle-assignments', [DriverController::class, 'assignVehicle'])
        ->name('drivers.vehicle-assignments.store');

    Route::patch('/drivers/{driver}/vehicle-assignments/release', [DriverController::class, 'releaseVehicle'])
        ->name('drivers.vehicle-assignments.release');

    Route::post('/drivers/{driver}/fuel-cards', [DriverController::class, 'storeFuelCard'])
        ->name('drivers.fuel-cards.store');

    Route::post('/drivers/{driver}/fuel-cards/{fuelCard}/transactions', [DriverController::class, 'storeFuelCardTransaction'])
        ->name('drivers.fuel-cards.transactions.store');

    Route::patch('/drivers/{driver}/fuel-cards/{fuelCard}/status', [DriverController::class, 'updateFuelCardStatus'])
        ->name('drivers.fuel-cards.status');

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
    Route::post('/services/bulk-replace', [ServiceController::class, 'bulkReplace'])
        ->name('services.bulk-replace');
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

    Route::middleware(['permission:all-users.view'])->group(function () {
        Route::resource('all-users', AllUsersController::class)
            ->parameters(['all-users' => 'user'])
            ->names('all-users');

        Route::patch('/all-users/{user}/toggle-status', [AllUsersController::class, 'toggleStatus'])
            ->middleware('permission:all-users.edit')
            ->name('all-users.toggle-status');
    });


    // CRUD
    Route::resource('supplier-vehicule-invoices', SupplierVehiculeInvoiceController::class);

    Route::post(
        '/supplier-vehicule-invoices/{invoice}/payments',
        [SupplierVehiculeInvoiceController::class, 'storePayment']
    )->name('supplier-vehicule-invoices.payments.store');

    Route::delete(
        '/supplier-vehicule-invoices/{invoice}/payments/{payment}',
        [SupplierVehiculeInvoiceController::class, 'destroyPayment']
    )->name('supplier-vehicule-invoices.payments.destroy');

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


    Route::get('/mailbox', [MailboxController::class, 'index'])->name('mailbox.index');
    Route::get('/mailbox/demo', [MailboxController::class, 'seedDemo'])->name('mailbox.demo');
    Route::get('/mailbox/messages/{message}', [MailboxController::class, 'show'])->name('mailbox.show');


    Route::post('/mailbox/sync', [MailboxController::class, 'sync'])
        ->name('mailbox.sync');

    Route::get('/reservation-drafts', [ReservationDraftController::class, 'index'])
        ->name('reservation-drafts.index');
    Route::post('/reservation-drafts/{reservationDraft}/validate', [ReservationDraftController::class, 'validateDraft'])
        ->name('reservation-drafts.validate');
    Route::post('/reservation-drafts/{reservationDraft}/reject', [ReservationDraftController::class, 'reject'])
        ->name('reservation-drafts.reject');

    Route::post('/supplier-vehicules/replace-selected', [SupplierVehiculeController::class, 'replaceSelected'])
        ->name('supplier-vehicules.replace-selected');


    Route::get('/vehicle-maintenances-report', [VehicleMaintenanceController::class, 'report'])
        ->name('vehicle-maintenances.report');

    Route::prefix('vehicle-maintenances')
        ->name('vehicle-maintenances.')
        ->group(function () {

            Route::get('/{vehiculeId}', [VehicleMaintenanceController::class, 'index'])
                ->name('index');

            Route::post('/', [VehicleMaintenanceController::class, 'store'])
                ->name('store');

            Route::put('/{id}', [VehicleMaintenanceController::class, 'update'])
                ->name('update');

            Route::delete('/{id}', [VehicleMaintenanceController::class, 'destroy'])
                ->name('destroy');
        });
});

Route::get('/mail-test', function () {
    $client = FacadesClient::make([
        'host' => env('IMAP_HOST'),
        'port' => env('IMAP_PORT'),
        'encryption' => env('IMAP_ENCRYPTION'),
        'validate_cert' => false,
        'username' => env('IMAP_USERNAME'),
        'password' => env('IMAP_PASSWORD'),
        'protocol' => 'imap',
    ]);

    $client->connect();

    $folder = $client->getFolder('INBOX');
    $messages = $folder->messages()->all()->limit(10)->get();

    dd([
        'count' => $messages->count(),
        'subjects' => $messages->map(fn($m) => $m->getSubject())->toArray(),
    ]);
});

Route::prefix('mobile')->group(function () {
    Route::post('/login', [MobileAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [MobileAuthController::class, 'me']);
        Route::post('/logout', [MobileAuthController::class, 'logout']);
    });
});

Route::get('/plannings/print/supplier-clients', [PlanningController::class, 'printSupplierClients'])
    ->name('plannings.print.supplier-clients');

Route::get('/plannings/print/supplier-vehicules', [PlanningController::class, 'printSupplierVehicules'])
    ->name('plannings.print.supplier-vehicules');

require __DIR__ . '/auth.php';
