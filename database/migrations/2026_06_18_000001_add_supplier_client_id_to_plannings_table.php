<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plannings', function (Blueprint $table) {
            if (!Schema::hasColumn('plannings', 'supplier_client_id')) {
                $table->foreignId('supplier_client_id')
                    ->nullable()
                    ->after('service_id')
                    ->constrained('supplier_clients')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('plannings', function (Blueprint $table) {
            if (Schema::hasColumn('plannings', 'supplier_client_id')) {
                $table->dropConstrainedForeignId('supplier_client_id');
            }
        });
    }
};
