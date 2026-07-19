<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Destination;
use App\Models\Driver;
use App\Models\Guide;
use App\Models\Planning;
use App\Models\Service;
use App\Models\SupplierClient;
use App\Models\SupplierVehicule;
use App\Models\TypeService;
use App\Models\User;
use App\Models\Vehicule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PlanningPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_persists_every_planning_field_and_clients_then_reloads_relations(): void
    {
        $references = $this->references();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('plannings.store'), $this->payload($references, [
            'ref_dossier' => 'ARRIVAL-CREATE-001',
        ]));

        $response->assertRedirect()->assertSessionHasNoErrors();

        $planning = Planning::query()->where('ref_dossier', 'ARRIVAL-CREATE-001')->firstOrFail();
        $this->assertPlanningValues($planning->fresh(), $references);
        $this->assertEqualsCanonicalizing(
            $references['clients']->pluck('id')->all(),
            $planning->clients()->pluck('clients.id')->all(),
        );

        $this->actingAs($user)
            ->get(route('plannings.index', ['date_du' => '2026-08-01', 'date_au' => '2026-08-31']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Plannings/Index')
                ->has('plannings.data', 1)
                ->where('plannings.data.0.service.id', $references['service']->id)
                ->where('plannings.data.0.service.designation', 'Arrival transfer')
                ->where('plannings.data.0.vehicule.id', $references['vehicle']->id)
                ->where('plannings.data.0.driver.id', $references['driver']->id)
                ->where('plannings.data.0.guide.id', $references['guide']->id)
                ->where('plannings.data.0.destination.id', $references['destination']->id)
                ->where('plannings.data.0.supplier_client.id', $references['supplierClient']->id)
                ->where('plannings.data.0.supplier_vehicule.id', $references['supplierVehicle']->id)
                ->has('plannings.data.0.planning_clients', 2));
    }

    public function test_update_persists_the_same_complete_payload_and_syncs_multiple_clients(): void
    {
        $references = $this->references();
        $oldService = Service::create(['designation' => 'Old service']);
        $planning = Planning::create([
            'date_du' => '2026-08-01',
            'ref_dossier' => 'BEFORE-UPDATE',
            'service_id' => $oldService->id,
        ]);
        $obsoleteClient = Client::create(['full_name' => 'Obsolete passenger']);
        $planning->clients()->attach($obsoleteClient);

        $response = $this->actingAs(User::factory()->create())->put(
            route('plannings.update', $planning),
            $this->payload($references, ['ref_dossier' => 'ARRIVAL-UPDATED-001']),
        );

        $response->assertRedirect()->assertSessionHasNoErrors();

        $planning->refresh();
        $this->assertPlanningValues($planning, $references);
        $this->assertSame('ARRIVAL-UPDATED-001', $planning->ref_dossier);
        $this->assertEqualsCanonicalizing(
            $references['clients']->pluck('id')->all(),
            $planning->clients()->pluck('clients.id')->all(),
        );
        $this->assertDatabaseMissing('planning_clients', [
            'planning_id' => $planning->id,
            'client_id' => $obsoleteClient->id,
        ]);
    }

    private function references(): array
    {
        $type = TypeService::create(['designation' => 'Transfer']);
        $service = Service::create([
            'designation' => 'Arrival transfer',
            'type_service' => $type->id,
        ]);
        $supplierClient = SupplierClient::create(['name' => 'Hotel Partner', 'is_active' => true]);

        return [
            'service' => $service,
            'vehicle' => Vehicule::create([
                'matricule' => '15327-B-33',
                'marque' => 'Mercedes',
                'modele' => 'Sprinter',
                'nombre_places' => 17,
                'status' => 'Disponible',
            ]),
            'driver' => Driver::create(['name' => 'MD Driver Test', 'status' => 'Disponible']),
            'guide' => Guide::create(['name' => 'Guide Test', 'status' => 'Disponible']),
            'destination' => Destination::create(['name' => 'Marrakech Airport']),
            'supplierClient' => $supplierClient,
            'supplierVehicle' => SupplierVehicule::create(['name' => 'Transport Partner', 'is_active' => true]),
            'clients' => collect([
                Client::create(['full_name' => 'Client One', 'supplier_client_id' => $supplierClient->id]),
                Client::create(['full_name' => 'Client Two', 'supplier_client_id' => $supplierClient->id]),
            ]),
        ];
    }

    private function payload(array $references, array $overrides = []): array
    {
        return array_merge([
            'date_du' => '2026-08-12',
            'date_au' => '2026-08-14',
            'ref_dossier' => 'ARRIVAL-DEFAULT',
            'nbr_personnes' => 2,
            'flight' => 'AT 402',
            'heure' => '08:45',
            'point_depart' => 'Terminal 1',
            'site' => 'Marrakech Airport arrivals',
            'service_id' => $references['service']->id,
            'supplier_client_id' => $references['supplierClient']->id,
            'supplier_vehicule_id' => $references['supplierVehicle']->id,
            'driver_id' => $references['driver']->id,
            'guide_id' => $references['guide']->id,
            'destination_id' => $references['destination']->id,
            'vehicule_id' => $references['vehicle']->id,
            'budget' => 1250.50,
            'supplier_price' => 875.25,
            'notes' => 'Full persistence test',
            'client_ids' => $references['clients']->pluck('id')->all(),
        ], $overrides);
    }

    private function assertPlanningValues(Planning $planning, array $references): void
    {
        $this->assertSame($references['service']->id, $planning->service_id);
        $this->assertSame($references['vehicle']->id, $planning->vehicule_id);
        $this->assertSame($references['driver']->id, $planning->driver_id);
        $this->assertSame($references['guide']->id, $planning->guide_id);
        $this->assertSame($references['destination']->id, $planning->destination_id);
        $this->assertSame($references['supplierClient']->id, $planning->supplier_client_id);
        $this->assertSame($references['supplierVehicle']->id, $planning->supplier_vehicule_id);
        $this->assertSame('2026-08-12', $planning->date_du->toDateString());
        $this->assertSame('2026-08-14', $planning->date_au->toDateString());
        $this->assertSame(2, $planning->nbr_personnes);
        $this->assertSame('AT 402', $planning->flight);
        $this->assertSame('08:45', $planning->heure->format('H:i'));
        $this->assertSame('Terminal 1', $planning->point_depart);
        $this->assertSame('Marrakech Airport arrivals', $planning->site);
        $this->assertSame('1250.50', $planning->budget);
        $this->assertSame('875.25', $planning->supplier_price);
        $this->assertSame('Full persistence test', $planning->notes);
    }
}
