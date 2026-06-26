<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('commandes') || Schema::hasColumn('commandes', 'supplier_vehicule_id')) {
            return;
        }

        Schema::table('commandes', function (Blueprint $table) {
            $table->foreignId('supplier_vehicule_id')
                ->nullable()
                ->after('id')
                ->constrained('supplier_vehicules')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('commandes') || !Schema::hasColumn('commandes', 'supplier_vehicule_id')) {
            return;
        }

        Schema::table('commandes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_vehicule_id');
        });
    }
};
