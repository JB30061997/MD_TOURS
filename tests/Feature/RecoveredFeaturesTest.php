<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Planning;
use App\Models\Service;
use App\Models\SupplierVehicule;
use App\Models\SupplierVehiculeInvoice;
use App\Models\SupplierVehiculeInvoicePlanning;
use App\Models\TypeService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RecoveredFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_boncmd_page_and_pdf_are_available_to_an_authorized_user(): void
    {
        $user = $this->authorizedUser('generate-boncmd.view');
        $supplier = SupplierVehicule::create(['name' => 'Transport Test']);
        $planning = Planning::create([
            'date_du' => '2026-07-05',
            'ref_dossier' => 'CMD-001',
            'supplier_vehicule_id' => $supplier->id,
            'supplier_price' => 750,
        ]);

        $this->actingAs($user)
            ->get(route('generate-boncmd.index', [
                'supplier_vehicule_id' => $supplier->id,
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('GenerateBonCMD/Index')
                ->has('rows', 1)
                ->where('rows.0.id', $planning->id));

        $this->actingAs($user)
            ->get(route('generate-boncmd.pdf', [
                'planning_ids' => [$planning->id],
                'supplier_vehicule_id' => $supplier->id,
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_driver_primes_filters_multiple_service_types_and_exports_pdf(): void
    {
        $user = $this->authorizedUser('driver-primes.view');
        $driver = Driver::create(['name' => 'Driver Test']);
        $circuitType = TypeService::create(['designation' => 'Circuit']);
        $transferType = TypeService::create(['designation' => 'Transfer']);
        $circuit = Service::create(['designation' => 'Circuit Sud', 'type_service' => $circuitType->id]);
        $transfer = Service::create(['designation' => 'Arrival transfer', 'type_service' => $transferType->id]);
        Planning::create(['date_du' => '2026-07-05', 'heure' => '10:00', 'ref_dossier' => 'CIR-1', 'driver_id' => $driver->id, 'service_id' => $circuit->id]);
        Planning::create(['date_du' => '2026-07-06', 'heure' => '09:00', 'ref_dossier' => 'TRF-1', 'service_id' => $transfer->id]);

        $this->actingAs($user)
            ->get(route('driver-primes.index', [
                'type_service_ids' => [$circuitType->id, $transferType->id],
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('DriverPrimes/Index')
                ->has('rows', 2)
                ->where('rows.0.reference', 'CIR-1')
                ->where('rows.0.driver', 'Driver Test')
                ->where('rows.1.reference', 'TRF-1')
                ->where('rows.1.driver', 'Sans chauffeur')
                ->where('summary.drivers_count', 1)
                ->where('summary.without_driver', 1));

        $this->actingAs($user)
            ->get(route('driver-primes.pdf', [
                'type_service_ids' => [$circuitType->id, $transferType->id],
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_driver_primes_rejects_invalid_service_type_filters(): void
    {
        $user = $this->authorizedUser('driver-primes.view');

        $this->actingAs($user)
            ->get(route('driver-primes.index', [
                'type_service_ids' => [999999],
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
            ]))
            ->assertSessionHasErrors('type_service_ids.0');
    }

    public function test_an_unbilled_planning_can_only_be_linked_to_an_invoice_of_the_same_supplier(): void
    {
        $user = $this->authorizedUser('supplier-vehicule-invoices.edit');
        $supplier = SupplierVehicule::create(['name' => 'Transport A']);
        $otherSupplier = SupplierVehicule::create(['name' => 'Transport B']);
        $planning = Planning::create([
            'date_du' => '2026-07-05',
            'ref_dossier' => 'INV-001',
            'supplier_vehicule_id' => $supplier->id,
        ]);
        $invoice = SupplierVehiculeInvoice::create([
            'supplier_vehicule_id' => $supplier->id,
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'invoice_number' => 'FA-001',
        ]);
        $wrongInvoice = SupplierVehiculeInvoice::create([
            'supplier_vehicule_id' => $otherSupplier->id,
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'invoice_number' => 'FA-002',
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.plannings.supplier-invoice', $planning), ['invoice_id' => $wrongInvoice->id])
            ->assertSessionHas('error');
        $this->assertDatabaseMissing('supplier_vehicule_invoice_plannings', ['planning_id' => $planning->id]);

        $this->actingAs($user)
            ->post(route('dashboard.plannings.supplier-invoice', $planning), ['invoice_id' => $invoice->id])
            ->assertSessionHas('success');

        $link = SupplierVehiculeInvoicePlanning::where('planning_id', $planning->id)->firstOrFail();
        $this->assertSame($invoice->id, $link->supplier_vehicule_invoice_id);
        $this->assertTrue((bool) $link->is_selected);
    }

    private function authorizedUser(string $permissionName): User
    {
        $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->givePermissionTo($permission);

        return $user;
    }
}
