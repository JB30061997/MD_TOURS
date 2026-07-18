<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\TypeService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanningQuickReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_can_be_searched_and_created_without_leaving_planning(): void
    {
        $user = User::factory()->create();
        $type = TypeService::create(['designation' => 'Circuit']);

        $response = $this->actingAs($user)->postJson('/planning-quick/services', [
            'designation' => 'Circuit Essaouira',
            'type_service' => $type->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.designation', 'Circuit Essaouira');
        $this->assertDatabaseHas('services', ['designation' => 'Circuit Essaouira']);

        $this->actingAs($user)
            ->getJson('/planning-quick/services?q=Essaouira')
            ->assertOk()
            ->assertJsonPath('data.0.id', $response->json('data.id'));
    }

    public function test_normalized_duplicate_returns_existing_service(): void
    {
        $user = User::factory()->create();
        $type = TypeService::create(['designation' => 'Circuit']);
        $existing = Service::create([
            'designation' => 'Circuit Éssaouira',
            'type_service' => $type->id,
        ]);

        $this->actingAs($user)->postJson('/planning-quick/services', [
            'designation' => '  circuit essaouira  ',
            'type_service' => $type->id,
        ])->assertConflict()
            ->assertJsonPath('existing.id', $existing->id);

        $this->assertSame(1, Service::count());
    }

    public function test_service_creation_keeps_laravel_validation_authoritative(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/planning-quick/services', ['designation' => 'Incomplet'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type_service');
    }
}
