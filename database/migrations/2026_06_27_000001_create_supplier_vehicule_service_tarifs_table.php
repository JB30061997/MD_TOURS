<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_vehicule_service_tarifs')) {
            return;
        }

        Schema::create('supplier_vehicule_service_tarifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_vehicule_id')
                ->constrained('supplier_vehicules')
                ->cascadeOnDelete();
            $table->foreignId('service_id')
                ->constrained('services')
                ->cascadeOnDelete();
            $table->decimal('price', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(
                ['supplier_vehicule_id', 'service_id'],
                'supplier_service_tarifs_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_vehicule_service_tarifs');
    }
};
