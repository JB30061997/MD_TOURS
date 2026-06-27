<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_vehicule_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_vehicule_invoice_id')
                ->constrained(
                    table: 'supplier_vehicule_invoices',
                    indexName: 'sv_invoice_payments_invoice_id_fk'
                )
                ->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method')->default('cash');
            $table->date('payment_date')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_vehicule_invoice_payments');
    }
};
