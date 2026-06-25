<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_fuel_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->string('card_number');
            $table->string('label')->nullable();
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('card_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_fuel_cards');
    }
};
