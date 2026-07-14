<?php

namespace Tests\Feature;

use App\Mail\CommandePdfMail;
use App\Models\Commande;
use App\Models\SupplierClient;
use App\Models\SupplierVehicule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CommandeSupplierIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_keeps_client_and_vehicle_suppliers_separate(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $clientSupplier = SupplierClient::create(['name' => 'Oriontrek Voyage', 'email' => 'client@example.com']);
        $vehicleSupplier = SupplierVehicule::create(['name' => 'MD TOURS', 'email' => 'vehicle@example.com']);

        Commande::create([
            'voucher_number' => 'BC-TEST-001',
            'supplier_client_id' => $clientSupplier->id,
            'supplier_vehicule_id' => $vehicleSupplier->id,
        ]);
        Commande::create([
            'voucher_number' => 'BC-TEST-002',
            'supplier_client_id' => $clientSupplier->id,
        ]);

        $this->actingAs($user)
            ->get(route('commandes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Commandes/Index')
                ->where('commandes.data.0.supplier_vehicule.name', 'MD TOURS')
                ->where('commandes.data.0.supplier_client.name', 'Oriontrek Voyage')
                ->where('commandes.data.1.supplier_vehicule', null)
                ->where('commandes.data.1.supplier_client.name', 'Oriontrek Voyage'));
    }

    public function test_client_supplier_id_is_rejected_as_vehicle_supplier(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $clientSupplier = SupplierClient::create(['name' => 'Client Supplier Only']);

        $this->actingAs($user)
            ->post(route('commandes.store'), [
                'voucher_number' => 'BC-INVALID-SUPPLIER',
                'supplier_vehicule_id' => $clientSupplier->id,
            ])
            ->assertSessionHasErrors([
                'supplier_vehicule_id' => 'Le fournisseur sélectionné doit être un fournisseur véhicule valide.',
            ]);

        $this->assertDatabaseMissing('commandes', ['voucher_number' => 'BC-INVALID-SUPPLIER']);
    }

    public function test_email_is_sent_only_to_vehicle_supplier(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $clientSupplier = SupplierClient::create(['name' => 'Oriontrek Voyage', 'email' => 'client@example.com']);
        $vehicleSupplier = SupplierVehicule::create(['name' => 'MD TOURS', 'email' => 'vehicle@example.com']);
        $commande = Commande::create([
            'voucher_number' => 'BC-MAIL-001',
            'supplier_client_id' => $clientSupplier->id,
            'supplier_vehicule_id' => $vehicleSupplier->id,
        ]);

        $this->actingAs($user)
            ->post(route('commandes.send-email', $commande))
            ->assertSessionHas('success');

        Mail::assertSent(CommandePdfMail::class, fn (CommandePdfMail $mail) =>
            $mail->hasTo('vehicle@example.com') && !$mail->hasTo('client@example.com')
        );
    }

    public function test_email_is_not_sent_to_client_supplier_when_vehicle_supplier_is_missing(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $clientSupplier = SupplierClient::create(['name' => 'Oriontrek Voyage', 'email' => 'client@example.com']);
        $commande = Commande::create([
            'voucher_number' => 'BC-MAIL-002',
            'supplier_client_id' => $clientSupplier->id,
        ]);

        $this->actingAs($user)
            ->post(route('commandes.send-email', $commande))
            ->assertSessionHas('error');

        Mail::assertNothingSent();
    }
}
