<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('mail_integrate')->default(false)->after('route_home');
            $table->string('mail_integration_login')->nullable()->after('mail_integrate');
            $table->text('mail_integration_password')->nullable()->after('mail_integration_login');
        });

        Schema::table('mail_accounts', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mail_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'mail_integrate',
                'mail_integration_login',
                'mail_integration_password',
            ]);
        });
    }
};
