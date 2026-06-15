<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_account_id')->nullable()->constrained('mail_accounts')->nullOnDelete();
            $table->foreignId('mail_message_id')->nullable()->unique()->constrained('mail_messages')->nullOnDelete();
            $table->foreignId('planning_id')->nullable()->constrained('plannings')->nullOnDelete();
            $table->string('status')->default('pending')->index();
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->string('source_from')->nullable();
            $table->string('source_subject')->nullable();
            $table->json('parsed_payload')->nullable();
            $table->text('validation_notes')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_drafts');
    }
};
