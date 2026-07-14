<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Planning;
use App\Models\SupplierVehicule;
use App\Models\User;
use App\Services\TemporaryMdToursSupplierAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardMissingSupplierAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_name_detection_is_case_insensitive_and_ignores_extra_spaces(): void
    {
        $this->assertTrue(TemporaryMdToursSupplierAssignmentService::isInternalMdToursDriver('Ahmed DRIVER MD TOURS'));
        $this->assertTrue(TemporaryMdToursSupplierAssignmentService::isInternalMdToursDriver('Mohamed Driver Md Tours'));
        $this->assertTrue(TemporaryMdToursSupplierAssignmentService::isInternalMdToursDriver('  Youssef   driver   md   tours  '));
        $this->assertFalse(TemporaryMdToursSupplierAssignmentService::isInternalMdToursDriver('DRIVER MD TOURS Ahmed'));
        $this->assertFalse(TemporaryMdToursSupplierAssignmentService::isInternalMdToursDriver('Ahmed chauffeur externe'));
        $this->assertFalse(TemporaryMdToursSupplierAssignmentService::isInternalMdToursDriver(null));
    }

    public function test_automatic_assignment_updates_only_eligible_missing_supplier_plannings_and_is_idempotent(): void
    {
        $admin = $this->admin();
        $mdTours = SupplierVehicule::create(['name' => '  md   tours  ']);
        $otherSupplier = SupplierVehicule::create(['name' => 'Fournisseur externe']);
        $internalDriver = Driver::create(['name' => '  Ahmed   Driver Md Tours  ']);
        $externalDriver = Driver::create(['name' => 'Ahmed Transport externe']);

        $eligible = $this->planning(['driver_id' => $internalDriver->id]);
        $external = $this->planning(['driver_id' => $externalDriver->id]);
        $withoutDriver = $this->planning();
        $alreadyAssigned = $this->planning([
            'driver_id' => $internalDriver->id,
            'supplier_vehicule_id' => $otherSupplier->id,
        ]);

        $response = $this->actingAs($admin)->postJson(route('dashboard.missing-suppliers.auto-assign'));

        $response->assertOk()->assertJson([
            'success' => true,
            'analyzed' => 3,
            'corrected' => 1,
            'ignored' => 2,
            'remaining' => 2,
        ]);
        $this->assertSame($mdTours->id, $eligible->fresh()->supplier_vehicule_id);
        $this->assertNull($external->fresh()->supplier_vehicule_id);
        $this->assertNull($withoutDriver->fresh()->supplier_vehicule_id);
        $this->assertSame($otherSupplier->id, $alreadyAssigned->fresh()->supplier_vehicule_id);

        $this->actingAs($admin)
            ->postJson(route('dashboard.missing-suppliers.auto-assign'))
            ->assertOk()
            ->assertJson(['analyzed' => 2, 'corrected' => 0, 'remaining' => 2]);
    }

    public function test_automatic_assignment_changes_nothing_when_md_tours_supplier_is_missing(): void
    {
        $admin = $this->admin();
        $driver = Driver::create(['name' => 'Hassan DRIVER MD TOURS']);
        $planning = $this->planning(['driver_id' => $driver->id]);

        $this->actingAs($admin)
            ->postJson(route('dashboard.missing-suppliers.auto-assign'))
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'analyzed' => 1,
                'corrected' => 0,
                'remaining' => 1,
            ]);

        $this->assertNull($planning->fresh()->supplier_vehicule_id);
    }

    public function test_admin_can_assign_one_or_many_plannings_without_overwriting_existing_supplier(): void
    {
        $admin = $this->admin();
        $supplier = SupplierVehicule::create(['name' => 'MD TOURS']);
        $existingSupplier = SupplierVehicule::create(['name' => 'Déjà affecté']);
        $first = $this->planning();
        $second = $this->planning();
        $alreadyAssigned = $this->planning(['supplier_vehicule_id' => $existingSupplier->id]);

        $this->actingAs($admin)
            ->postJson(route('dashboard.missing-suppliers.assign'), [
                'supplier_vehicule_id' => $supplier->id,
                'planning_ids' => [$first->id],
            ])
            ->assertOk()
            ->assertJson(['updated' => 1, 'skipped' => 0]);

        $this->actingAs($admin)
            ->postJson(route('dashboard.missing-suppliers.assign'), [
                'supplier_vehicule_id' => $supplier->id,
                'planning_ids' => [$second->id, $alreadyAssigned->id],
            ])
            ->assertOk()
            ->assertJson(['updated' => 1, 'skipped' => 1]);

        $this->assertSame($supplier->id, $first->fresh()->supplier_vehicule_id);
        $this->assertSame($supplier->id, $second->fresh()->supplier_vehicule_id);
        $this->assertSame($existingSupplier->id, $alreadyAssigned->fresh()->supplier_vehicule_id);
    }

    public function test_manual_assignment_requires_a_supplier_and_a_selection(): void
    {
        $admin = $this->admin();
        $planning = $this->planning();

        $this->actingAs($admin)
            ->postJson(route('dashboard.missing-suppliers.assign'), [
                'planning_ids' => [$planning->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('supplier_vehicule_id');

        $this->assertNull($planning->fresh()->supplier_vehicule_id);
    }

    public function test_non_administrator_cannot_list_or_modify_missing_supplier_plannings(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $supplier = SupplierVehicule::create(['name' => 'MD TOURS']);
        $planning = $this->planning();

        $this->actingAs($user)
            ->getJson(route('dashboard.missing-suppliers.index'))
            ->assertForbidden();
        $this->actingAs($user)
            ->postJson(route('dashboard.missing-suppliers.auto-assign'))
            ->assertForbidden();
        $this->actingAs($user)
            ->postJson(route('dashboard.missing-suppliers.assign'), [
                'supplier_vehicule_id' => $supplier->id,
                'planning_ids' => [$planning->id],
            ])
            ->assertForbidden();

        $this->assertNull($planning->fresh()->supplier_vehicule_id);
    }

    public function test_listing_contains_only_plannings_still_without_a_supplier(): void
    {
        $admin = $this->admin();
        $supplier = SupplierVehicule::create(['name' => 'MD TOURS']);
        $missing = $this->planning(['ref_dossier' => 'MISSING-001']);
        $this->planning(['ref_dossier' => 'ASSIGNED-001', 'supplier_vehicule_id' => $supplier->id]);

        $this->actingAs($admin)
            ->getJson(route('dashboard.missing-suppliers.index', ['search' => 'MISSING']))
            ->assertOk()
            ->assertJsonPath('plannings.total', 1)
            ->assertJsonPath('plannings.data.0.id', $missing->id);
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        return $user;
    }

    private function planning(array $attributes = []): Planning
    {
        return Planning::create([
            'date_du' => '2026-07-14',
            'ref_dossier' => 'TEST-'.fake()->unique()->numberBetween(1000, 9999),
            ...$attributes,
        ]);
    }
}
