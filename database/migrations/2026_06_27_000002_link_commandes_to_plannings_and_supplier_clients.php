<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('commandes')) {
            return;
        }

        Schema::table('commandes', function (Blueprint $table) {
            if (!Schema::hasColumn('commandes', 'planning_id')) {
                $table->foreignId('planning_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('plannings')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('commandes', 'supplier_client_id')) {
                $table->foreignId('supplier_client_id')
                    ->nullable()
                    ->after('supplier_vehicule_id')
                    ->constrained('supplier_clients')
                    ->nullOnDelete();
            }
        });

        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('commandes', 'supplier_vehicule_id')) {
            DB::statement('ALTER TABLE commandes MODIFY supplier_vehicule_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('commandes')) {
            return;
        }

        Schema::table('commandes', function (Blueprint $table) {
            if (Schema::hasColumn('commandes', 'planning_id')) {
                $table->dropConstrainedForeignId('planning_id');
            }

            if (Schema::hasColumn('commandes', 'supplier_client_id')) {
                $table->dropConstrainedForeignId('supplier_client_id');
            }
        });
    }
};
