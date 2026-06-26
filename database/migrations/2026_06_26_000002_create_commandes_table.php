<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('commandes')) {
            return;
        }

        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_vehicule_id')->constrained('supplier_vehicules')->cascadeOnDelete();
            $table->string('voucher_number')->unique();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->decimal('supplier_price', 12, 2)->nullable();
            $table->string('start_point')->nullable();
            $table->string('start_point_flight')->nullable();
            $table->string('start_point_city')->nullable();
            $table->time('start_point_time')->nullable();
            $table->string('end_point')->nullable();
            $table->string('end_point_flight')->nullable();
            $table->string('end_point_city')->nullable();
            $table->time('end_point_time')->nullable();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('vehicule_id')->nullable()->constrained('vehicules')->nullOnDelete();
            $table->foreignId('guide_id')->nullable()->constrained('guides')->nullOnDelete();
            $table->string('passenger')->nullable();
            $table->unsignedInteger('number_pax')->nullable();
            $table->string('reference')->nullable();
            $table->date('date')->nullable();
            $table->string('signature')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
