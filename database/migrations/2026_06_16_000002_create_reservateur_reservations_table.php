<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservateur_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservateur_id')->constrained('reservateurs')->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('service');
            $table->string('lieu_depart');
            $table->string('lieu_arrivee');
            $table->date('date_service');
            $table->time('heure_souhaitee')->nullable();
            $table->unsignedInteger('nombre_personnes')->nullable();
            $table->string('contact')->nullable();
            $table->text('informations_complementaires')->nullable();
            $table->string('statut')->default('en_attente')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservateur_reservations');
    }
};
