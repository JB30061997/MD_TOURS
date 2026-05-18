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
        Schema::create('vehicle_maintenances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vehicule_id')
                ->constrained('vehicules')
                ->cascadeOnDelete();

            $table->string('type_maintenance');
            // Vidange, Pneus, Freins, Assurance, Visite technique...

            $table->date('date_maintenance');

            $table->integer('kilometrage')->nullable();

            $table->decimal('montant', 10, 2)->default(0);

            $table->string('garage')->nullable();

            $table->date('prochaine_date')->nullable();

            $table->integer('prochain_kilometrage')->nullable();

            $table->string('status')->default('effectue');
            // planifie, effectue, annule

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenances');
    }
};
