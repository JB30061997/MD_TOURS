<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supplier_vehicule_invoice_plannings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('supplier_vehicule_invoice_id');
            $table->unsignedBigInteger('planning_id');

            $table->foreign('supplier_vehicule_invoice_id', 'svip_invoice_fk')
                ->references('id')
                ->on('supplier_vehicule_invoices')
                ->cascadeOnDelete();

            $table->foreign('planning_id', 'svip_planning_fk')
                ->references('id')
                ->on('plannings')
                ->cascadeOnDelete();

            $table->boolean('is_selected')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_vehicule_invoice_plannings');
    }
};
