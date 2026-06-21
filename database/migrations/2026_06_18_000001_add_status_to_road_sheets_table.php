<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('road_sheets', function (Blueprint $table) {
            if (! Schema::hasColumn('road_sheets', 'status')) {
                $table->string('status')->default('a_completer')->index()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('road_sheets', function (Blueprint $table) {
            if (Schema::hasColumn('road_sheets', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
