<?php

namespace Tests\Feature;

use App\Models\Planning;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardMissingServiceAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_contains_only_plannings_without_a_service(): void
    {
        $user = $this->authorizedUser();
        $service = Service::create(['designation' => 'Transfert aéroport']);
        $missing = $this->planning(['ref_dossier' => 'MISSING-SERVICE']);
        $this->planning(['ref_dossier' => 'ASSIGNED-SERVICE', 'service_id' => $service->id]);

        $this->actingAs($user)
            ->getJson(route('dashboard.missing-services.index', ['search' => 'MISSING']))
            ->assertOk()
            ->assertJsonPath('plannings.total', 1)
            ->assertJsonPath('plannings.data.0.id', $missing->id)
            ->assertJsonPath('plannings.data.0.service', 'Sans service');
    }

    public function test_one_or_many_plannings_can_be_assigned_without_overwriting_an_existing_service(): void
    {
        $user = $this->authorizedUser();
        $service = Service::create(['designation' => 'Transfert arrivée']);
        $existingService = Service::create(['designation' => 'Excursion']);
        $first = $this->planning();
        $second = $this->planning();
        $alreadyAssigned = $this->planning(['service_id' => $existingService->id]);

        $this->actingAs($user)->postJson(route('dashboard.missing-services.assign'), [
            'service_id' => $service->id,
            'planning_ids' => [$first->id, $second->id, $alreadyAssigned->id],
        ])->assertOk()->assertJson(['updated' => 2, 'skipped' => 1]);

        $this->assertSame($service->id, $first->fresh()->service_id);
        $this->assertSame($service->id, $second->fresh()->service_id);
        $this->assertSame($existingService->id, $alreadyAssigned->fresh()->service_id);
    }

    public function test_assignment_requires_a_valid_service_and_selection(): void
    {
        $user = $this->authorizedUser();
        $planning = $this->planning();

        $this->actingAs($user)->postJson(route('dashboard.missing-services.assign'), [
            'planning_ids' => [$planning->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('service_id');

        $this->assertNull($planning->fresh()->service_id);
    }

    private function authorizedUser(): User
    {
        $permission = Permission::firstOrCreate(['name' => 'plannings.edit', 'guard_name' => 'web']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->givePermissionTo($permission);

        return $user;
    }

    private function planning(array $attributes = []): Planning
    {
        return Planning::create([
            'date_du' => '2026-07-16',
            'ref_dossier' => 'TEST-'.fake()->unique()->numberBetween(1000, 9999),
            ...$attributes,
        ]);
    }
}
