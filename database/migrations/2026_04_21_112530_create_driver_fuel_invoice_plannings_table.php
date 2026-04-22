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
        Schema::create('driver_fuel_invoice_plannings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('driver_fuel_invoice_id')
                ->constrained('driver_fuel_invoices')
                ->cascadeOnDelete();

            $table->foreignId('planning_id')
                ->constrained('plannings')
                ->cascadeOnDelete();

            $table->boolean('is_selected')->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['driver_fuel_invoice_id', 'planning_id'], 'dfi_planning_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_fuel_invoice_plannings');
    }
};
