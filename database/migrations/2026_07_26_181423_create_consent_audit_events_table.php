<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('consent_audit_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();

            $table->foreignId('consent_session_id')
                ->constrained('consent_sessions')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('event_type', 100);

            $table->text('description');

            $table->json('metadata')->nullable();

            /*
             * Maximum IPv6 address length is 45 characters.
             */
            $table->string('ip_address', 45)->nullable();

            $table->text('user_agent')->nullable();

            /*
             * Audit events are append-only, so they only need
             * a creation timestamp and no updated_at column.
             */
            $table->timestamp('created_at')->useCurrent();

            /*
             * Improve organization and consent history queries.
             */
            $table->index([
                'organization_id',
                'created_at',
            ]);

            $table->index([
                'consent_session_id',
                'created_at',
            ]);

            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consent_audit_events');
    }
};
