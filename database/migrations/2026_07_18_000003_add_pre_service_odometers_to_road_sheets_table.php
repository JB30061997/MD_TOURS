<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('road_sheets', function (Blueprint $table) {
            $table->unsignedInteger('pre_service_odometer_start')->nullable()->after('pre_service_km');
            $table->unsignedInteger('pre_service_odometer_end')->nullable()->after('pre_service_odometer_start');
        });
    }

    public function down(): void
    {
        Schema::table('road_sheets', function (Blueprint $table) {
            $table->dropColumn(['pre_service_odometer_start', 'pre_service_odometer_end']);
        });
    }
};
