<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'consent_notifications',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('organization_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('consent_session_id')
                    ->constrained('consent_sessions')
                    ->cascadeOnDelete();

                $table->foreignId('actor_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('type', 40);
                $table->string('trigger', 40);
                $table->string('status', 20);
                $table->string('recipient_email');
                $table->string('subject');
                $table->text('message')->nullable();
                $table->unsignedSmallInteger(
                    'days_before_deadline'
                )->nullable();
                $table->timestamp('scheduled_for')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index([
                    'consent_session_id',
                    'status',
                ]);

                $table->index([
                    'type',
                    'days_before_deadline',
                ]);

                $table->index([
                    'organization_id',
                    'created_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_notifications');
    }
};
