<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('road_sheet_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('road_sheet_id')->constrained('road_sheets')->cascadeOnDelete();
            $table->date('date')->nullable();
            $table->unsignedInteger('departure_kms')->nullable();
            $table->unsignedInteger('arrival_kms')->nullable();
            $table->unsignedInteger('distance')->nullable();
            $table->decimal('gasoline', 12, 2)->nullable();
            $table->decimal('jawaz', 12, 2)->nullable();
            $table->decimal('other_expenses', 12, 2)->nullable();
            $table->string('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('road_sheet_lines');
    }
};
