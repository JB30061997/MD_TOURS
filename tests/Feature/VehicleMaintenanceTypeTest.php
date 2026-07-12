<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicule;
use App\Models\VehicleMaintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VehicleMaintenanceTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_adblue_can_be_created_updated_and_displayed_in_history(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        foreach (['vehicle-maintenances.view', 'vehicle-maintenances.create', 'vehicle-maintenances.edit'] as $name) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }
        $vehicle = Vehicule::create(['matricule' => 'ADBLUE-001']);

        $this->actingAs($user)->post(route('vehicle-maintenances.store'), [
            'vehicule_id' => $vehicle->id,
            'type_maintenance' => 'AdBlue',
            'date_maintenance' => '2026-07-12',
            'montant' => 350,
        ])->assertRedirect(route('vehicle-maintenances.index', $vehicle));

        $maintenance = VehicleMaintenance::firstOrFail();
        $this->assertSame('AdBlue', $maintenance->type_maintenance);

        $this->actingAs($user)->put(route('vehicle-maintenances.update', $maintenance), [
            'type_maintenance' => 'AdBlue',
            'date_maintenance' => '2026-07-13',
            'montant' => 400,
        ])->assertRedirect(route('vehicle-maintenances.index', $vehicle));

        $this->assertDatabaseHas('vehicle_maintenances', [
            'id' => $maintenance->id,
            'type_maintenance' => 'AdBlue',
            'montant' => 400,
        ]);

        $this->actingAs($user)
            ->get(route('vehicle-maintenances.index', $vehicle))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('VehicleMaintenances/Index')
                ->where('maintenanceTypes', VehicleMaintenance::TYPES)
                ->where('vehicule.maintenances.0.type_maintenance', 'AdBlue'));
    }
}
