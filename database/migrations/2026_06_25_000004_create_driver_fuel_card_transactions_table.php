<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_fuel_card_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_fuel_card_id')->constrained('driver_fuel_cards')->cascadeOnDelete();
            $table->string('type');
            $table->decimal('amount', 12, 2);
            $table->date('transaction_date')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_fuel_card_transactions');
    }
};
