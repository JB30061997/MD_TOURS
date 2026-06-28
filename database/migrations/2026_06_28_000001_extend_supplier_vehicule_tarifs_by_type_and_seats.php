<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('supplier_vehicule_service_tarifs')) {
            return;
        }

        Schema::table('supplier_vehicule_service_tarifs', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_vehicule_service_tarifs', 'type_service_id')) {
                $table->foreignId('type_service_id')
                    ->nullable()
                    ->after('service_id')
                    ->constrained('type_services')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('supplier_vehicule_service_tarifs', 'vehicle_seats')) {
                $table->unsignedInteger('vehicle_seats')
                    ->nullable()
                    ->after('type_service_id');
            }
        });

        $this->addIndexIfMissing('supplier_vehicule_service_tarifs', 'supplier_tarifs_supplier_idx', ['supplier_vehicule_id']);
        $this->addIndexIfMissing('supplier_vehicule_service_tarifs', 'supplier_tarifs_service_idx', ['service_id']);
        $this->dropIndexIfExists('supplier_vehicule_service_tarifs', 'supplier_service_tarifs_unique');

        Schema::table('supplier_vehicule_service_tarifs', function (Blueprint $table) {
            $table->unique(
                ['supplier_vehicule_id', 'service_id', 'type_service_id', 'vehicle_seats'],
                'supplier_service_type_seats_tarifs_unique'
            );
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('supplier_vehicule_service_tarifs')) {
            return;
        }

        $this->dropIndexIfExists('supplier_vehicule_service_tarifs', 'supplier_service_type_seats_tarifs_unique');

        Schema::table('supplier_vehicule_service_tarifs', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_vehicule_service_tarifs', 'type_service_id')) {
                $table->dropConstrainedForeignId('type_service_id');
            }

            if (Schema::hasColumn('supplier_vehicule_service_tarifs', 'vehicle_seats')) {
                $table->dropColumn('vehicle_seats');
            }
        });

        Schema::table('supplier_vehicule_service_tarifs', function (Blueprint $table) {
            $table->unique(
                ['supplier_vehicule_id', 'service_id'],
                'supplier_service_tarifs_unique'
            );
        });
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $exists = collect(DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]))->isNotEmpty();

        if ($exists) {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$index}");
        }
    }

    private function addIndexIfMissing(string $table, string $index, array $columns): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $exists = collect(DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]))->isNotEmpty();

        if (!$exists) {
            DB::statement("ALTER TABLE {$table} ADD INDEX {$index} (" . implode(', ', $columns) . ')');
        }
    }
};
