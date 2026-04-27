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
        Schema::create('plannings', function (Blueprint $table) {
            $table->id();

            $table->date('date_du')->nullable();
            $table->date('date_au')->nullable();
            $table->string('ref_dossier')->nullable();
            $table->integer('nbr_personnes')->nullable();
            $table->string('flight')->nullable();
            $table->time('heure')->nullable();
            $table->string('point_depart')->nullable();
            $table->string('site')->nullable();

            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('supplier_vehicule_id')->nullable()->constrained('supplier_vehicules')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('guide_id')->nullable()->constrained('guides')->nullOnDelete();
            $table->foreignId('destination_id')->nullable()->constrained('destinations')->nullOnDelete();
            $table->foreignId('vehicule_id')->nullable()->constrained('vehicules')->nullOnDelete();

            $table->decimal('budget', 12, 2)->nullable();
            $table->decimal('supplier_price', 12, 2)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plannings');
    }
};
