<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('type_services')) {
            return;
        }

        foreach ([
            'Circuit',
            'Excursion',
            'Transfer',
            'Transfert aéroport - hôtel - aéroport',
            'Transfert inter-ville',
        ] as $designation) {
            DB::table('type_services')->updateOrInsert(
                ['designation' => $designation],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        //
    }
};
