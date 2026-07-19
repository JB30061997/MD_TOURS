<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\TypeService;
use App\Models\User;
use App\Models\SupplierClient;
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

    public function test_destination_guide_and_client_quick_creation_return_selectable_records(): void
    {
        $user = User::factory()->create();
        $supplier = SupplierClient::create([
            'name' => 'Agence Test',
            'is_active' => true,
        ]);

        $this->actingAs($user)->postJson('/planning-quick/destinations', [
            'name' => 'Ouzoud',
            'city' => 'Azilal',
        ])->assertCreated()->assertJsonPath('data.name', 'Ouzoud');

        $this->actingAs($user)->postJson('/planning-quick/guides', [
            'name' => 'Guide Planning',
        ])->assertCreated()->assertJsonPath('data.name', 'Guide Planning');

        $this->actingAs($user)->postJson('/planning-quick/clients', [
            'supplier_client_id' => $supplier->id,
            'full_name' => 'Client Planning',
        ])->assertCreated()->assertJsonPath('data.full_name', 'Client Planning');
    }

    public function test_client_duplicate_is_scoped_to_its_supplier(): void
    {
        $user = User::factory()->create();
        $firstSupplier = SupplierClient::create(['name' => 'Agence A', 'is_active' => true]);
        $secondSupplier = SupplierClient::create(['name' => 'Agence B', 'is_active' => true]);

        $this->actingAs($user)->postJson('/planning-quick/clients', [
            'supplier_client_id' => $firstSupplier->id,
            'full_name' => 'Même Client',
        ])->assertCreated();

        $this->actingAs($user)->postJson('/planning-quick/clients', [
            'supplier_client_id' => $secondSupplier->id,
            'full_name' => 'meme client',
        ])->assertCreated();
    }

    public function test_vehicle_quick_creation_requires_complete_minimum_business_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/planning-quick/vehicles', [
            'matricule' => '12345-A-6',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->actingAs($user)->postJson('/planning-quick/vehicles', [
            'matricule' => '12345-A-6',
            'status' => 'Disponible',
            'nombre_places' => 17,
        ])->assertCreated()->assertJsonPath('data.matricule', '12345-A-6');
    }

    public function test_invalid_destination_labels_are_not_exposed_and_vehicle_search_uses_model_or_seats(): void
    {
        $user = User::factory()->create();
        \App\Models\Destination::create(['name' => '?']);
        \App\Models\Destination::create(['name' => 'Aéroport Marrakech']);
        \App\Models\Vehicule::create([
            'matricule' => '77777-B-7',
            'marque' => 'Mercedes',
            'modele' => 'Sprinter',
            'nombre_places' => 17,
            'status' => 'Disponible',
        ]);

        $this->actingAs($user)->getJson('/planning-quick/destinations?q=?')
            ->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($user)->getJson('/planning-quick/vehicles?q=Sprinter')
            ->assertOk()->assertJsonPath('data.0.matricule', '77777-B-7');
        $this->actingAs($user)->getJson('/planning-quick/vehicles?q=17')
            ->assertOk()->assertJsonPath('data.0.nombre_places', 17);
    }
}
