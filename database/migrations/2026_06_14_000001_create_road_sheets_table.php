<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('road_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planning_id')->unique()->constrained('plannings')->cascadeOnDelete();
            $table->string('voucher_number')->nullable();
            $table->string('start_city')->nullable();
            $table->string('end_city')->nullable();
            $table->string('start_flight')->nullable();
            $table->string('end_flight')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->date('signature_date')->nullable();
            $table->string('signature_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('road_sheets');
    }
};
