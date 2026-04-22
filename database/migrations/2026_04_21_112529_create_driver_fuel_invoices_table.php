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
        Schema::create('driver_fuel_invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('driver_id')
                ->constrained('drivers')
                ->cascadeOnDelete();

            $table->date('period_start');
            $table->date('period_end');

            $table->string('invoice_number')->nullable();
            $table->date('invoice_date')->nullable();

            $table->decimal('total_amount', 12, 2)->default(0);

            $table->string('pdf_path')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['driver_id', 'period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_fuel_invoices');
    }
};
