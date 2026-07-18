<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('road_sheets', function (Blueprint $table) {
            $table->unsignedInteger('pre_service_km')->default(0)->after('planning_id');
            $table->string('pre_service_origin')->nullable()->after('pre_service_km');
            $table->string('pre_service_note', 500)->nullable()->after('pre_service_origin');
        });
    }

    public function down(): void
    {
        Schema::table('road_sheets', function (Blueprint $table) {
            $table->dropColumn(['pre_service_km', 'pre_service_origin', 'pre_service_note']);
        });
    }
};
