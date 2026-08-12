<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'evaluation_email_credit_usages',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('organization_id')
                    ->constrained()
                    ->cascadeOnDelete();

                /*
                 * These are nullable deliberately.
                 *
                 * Consumed Evaluation credits must survive deletion of
                 * the source consent record or notification so that the
                 * lifetime allowance can never be reset by deleting data.
                 */
                $table->foreignId('consent_session_id')
                    ->nullable()
                    ->constrained('consent_sessions')
                    ->nullOnDelete();

                $table->foreignId('consent_notification_id')
                    ->nullable()
                    ->unique()
                    ->constrained('consent_notifications')
                    ->nullOnDelete();

                $table->uuid('reservation_key')
                    ->unique();

                $table->string('notification_type', 64)
                    ->nullable();

                $table->string('status', 32);

                $table->timestamp('reserved_at');
                $table->timestamp('reservation_expires_at')
                    ->nullable();

                $table->timestamp('consumed_at')
                    ->nullable();

                $table->timestamp('released_at')
                    ->nullable();

                $table->string('release_reason', 255)
                    ->nullable();

                $table->json('metadata')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'organization_id',
                    'status',
                ]);

                $table->index([
                    'organization_id',
                    'reserved_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'evaluation_email_credit_usages'
        );
    }
};
