<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\DriverFuelCard;
use App\Models\User;
use App\Models\Vehicule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_operations_assign_vehicle_add_card_and_register_movements(): void
    {
        $user = User::factory()->create();
        $driver = Driver::create([
            'name' => 'Belqacem Brahim Driver MD Tours',
            'status' => 'Available',
        ]);
        $vehicule = Vehicule::create([
            'matricule' => 'TEST-001',
            'marque' => 'Mercedes',
            'modele' => 'Sprinter',
        ]);

        $this->actingAs($user)
            ->post(route('drivers.vehicle-assignments.store', $driver), [
                'vehicule_id' => $vehicule->id,
                'assigned_date' => '2026-06-25',
                'notes' => 'Test affectation',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('driver_vehicle_assignments', [
            'driver_id' => $driver->id,
            'vehicule_id' => $vehicule->id,
            'assigned_date' => '2026-06-25 00:00:00',
            'released_date' => null,
        ]);

        $this->actingAs($user)
            ->post(route('drivers.fuel-cards.store', $driver), [
                'card_number' => 'CT00001',
                'label' => 'Carte principale',
                'initial_balance' => 300,
                'status' => 'active',
                'notes' => 'Test carte',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $card = DriverFuelCard::where('card_number', 'CT00001')->firstOrFail();

        $this->assertSame($driver->id, $card->driver_id);
        $this->assertSame('300.00', $card->balance);
        $this->assertDatabaseHas('driver_fuel_card_transactions', [
            'driver_fuel_card_id' => $card->id,
            'type' => 'recharge',
            'amount' => 300,
            'reference' => 'Solde initial',
        ]);

        $this->actingAs($user)
            ->post(route('drivers.fuel-cards.transactions.store', [$driver, $card]), [
                'type' => 'expense',
                'amount' => 80,
                'transaction_date' => '2026-06-25',
                'reference' => 'Bon station test',
                'notes' => 'Mazot',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame('220.00', $card->fresh()->balance);
        $this->assertDatabaseHas('driver_fuel_card_transactions', [
            'driver_fuel_card_id' => $card->id,
            'type' => 'expense',
            'amount' => 80,
            'transaction_date' => '2026-06-25 00:00:00',
            'reference' => 'Bon station test',
        ]);
    }

    public function test_driver_fuel_card_expense_cannot_exceed_balance(): void
    {
        $user = User::factory()->create();
        $driver = Driver::create([
            'name' => 'Balance Guard Driver MD Tours',
            'status' => 'Available',
        ]);
        $card = $driver->fuelCards()->create([
            'card_number' => 'CT00002',
            'label' => 'Carte test',
            'balance' => 50,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->from('/drivers')
            ->post(route('drivers.fuel-cards.transactions.store', [$driver, $card]), [
                'type' => 'expense',
                'amount' => 90,
                'transaction_date' => '2026-06-25',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame('50.00', $card->fresh()->balance);
    }
}
