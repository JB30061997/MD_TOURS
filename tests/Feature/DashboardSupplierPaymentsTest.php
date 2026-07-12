<?php

namespace Tests\Feature;

use App\Models\SupplierVehicule;
use App\Models\SupplierVehiculeInvoice;
use App\Models\SupplierVehiculeInvoicePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DashboardSupplierPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_payment_card_uses_payment_date_and_sums_partial_payments_once(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web']));
        $supplier = SupplierVehicule::create(['name' => 'Transport A']);
        $otherSupplier = SupplierVehicule::create(['name' => 'Transport B']);
        $invoice = SupplierVehiculeInvoice::create([
            'supplier_vehicule_id' => $supplier->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'total_amount' => 1000,
        ]);
        $otherInvoice = SupplierVehiculeInvoice::create([
            'supplier_vehicule_id' => $otherSupplier->id,
            'period_start' => '2026-07-01',
            'period_end' => '2026-07-31',
            'total_amount' => 900,
        ]);

        foreach ([
            ['invoice' => $invoice, 'amount' => 300, 'date' => '2026-07-05'],
            ['invoice' => $invoice, 'amount' => 200, 'date' => '2026-07-20'],
            ['invoice' => $invoice, 'amount' => 125, 'date' => '2026-06-15'],
            ['invoice' => $otherInvoice, 'amount' => 700, 'date' => '2026-07-10'],
        ] as $payment) {
            SupplierVehiculeInvoicePayment::create([
                'supplier_vehicule_invoice_id' => $payment['invoice']->id,
                'amount' => $payment['amount'],
                'payment_date' => $payment['date'],
            ]);
        }

        $this->actingAs($user)
            ->get(route('dashboard', [
                'date_from' => '2026-07-01',
                'date_to' => '2026-07-31',
                'supplier_vehicule_id' => $supplier->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('monthlyFinancialSummary.2.key', 'supplier_payments')
                ->where('monthlyFinancialSummary.2.label', 'Paiements fournisseurs')
                ->where('monthlyFinancialSummary.2.value', 500)
                ->where('monthlyFinancialSummary.2.previous_value', 125));
    }
}
