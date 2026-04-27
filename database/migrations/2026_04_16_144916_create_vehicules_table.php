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
        Schema::create('vehicules', function (Blueprint $table) {
            $table->id();

            $table->string('matricule')->unique();
            $table->string('marque')->nullable();
            $table->string('modele')->nullable();
            $table->string('type')->nullable();
            $table->string('couleur')->nullable();
            $table->year('annee')->nullable();

            $table->integer('nombre_places')->nullable();
            $table->string('carburant')->nullable();
            $table->string('boite_vitesse')->nullable();

            $table->string('numero_assurance')->nullable();
            $table->date('date_expiration_assurance')->nullable();

            $table->date('date_visite_technique')->nullable();
            $table->date('date_expiration_visite')->nullable();

            $table->string('status')->default('Disponible');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicules');
    }
};
