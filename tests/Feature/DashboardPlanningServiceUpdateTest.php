<?php

namespace Tests\Feature;

use App\Models\Planning;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardPlanningServiceUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_user_can_update_only_the_planning_service(): void
    {
        $permission = Permission::create(['name' => 'plannings.edit']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->givePermissionTo($permission);
        $oldService = Service::create(['designation' => 'Arrival transfer']);
        $newService = Service::create(['designation' => 'Departure transfer']);
        $planning = Planning::create([
            'date_du' => '2026-06-10',
            'ref_dossier' => 'TEST-001',
            'service_id' => $oldService->id,
            'budget' => 1200,
            'supplier_price' => 900,
        ]);

        $response = $this->actingAs($user)->patch(
            route('dashboard.plannings.service.update', $planning),
            ['service_id' => $newService->id, 'replace_confirmed' => true]
        );

        $response->assertRedirect();
        $planning->refresh();
        $this->assertSame($newService->id, $planning->service_id);
        $this->assertSame('1200.00', $planning->budget);
        $this->assertSame('900.00', $planning->supplier_price);
    }

    public function test_replacing_a_service_requires_explicit_confirmation(): void
    {
        $permission = Permission::create(['name' => 'plannings.edit']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->givePermissionTo($permission);
        $oldService = Service::create(['designation' => 'Arrival transfer']);
        $newService = Service::create(['designation' => 'Departure transfer']);
        $planning = Planning::create([
            'date_du' => '2026-06-10',
            'ref_dossier' => 'TEST-002',
            'service_id' => $oldService->id,
        ]);

        $response = $this->actingAs($user)->patch(
            route('dashboard.plannings.service.update', $planning),
            ['service_id' => $newService->id, 'replace_confirmed' => false]
        );

        $response->assertSessionHasErrors('service_id');
        $this->assertSame($oldService->id, $planning->fresh()->service_id);
    }

    public function test_a_user_without_permission_cannot_update_the_service(): void
    {
        Permission::create(['name' => 'plannings.edit']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $service = Service::create(['designation' => 'Arrival transfer']);
        $planning = Planning::create(['date_du' => '2026-06-10', 'ref_dossier' => 'TEST-003']);

        $response = $this->actingAs($user)->patch(
            route('dashboard.plannings.service.update', $planning),
            ['service_id' => $service->id, 'replace_confirmed' => true]
        );

        $response->assertStatus(303);
        $response->assertSessionHas('authorization_error');
        $this->assertNull($planning->fresh()->service_id);
    }
}
