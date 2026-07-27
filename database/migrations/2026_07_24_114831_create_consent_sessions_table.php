<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the consent sessions table.
     */
    public function up(): void
    {
        Schema::create('consent_sessions', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Multi-tenant ownership
            |--------------------------------------------------------------------------
            */

            $table
                ->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Template references
            |--------------------------------------------------------------------------
            |
            | The template ID helps us locate the parent template.
            |
            | The version ID permanently records the exact published version
            | that the signer will review.
            */

            $table
                ->foreignId('consent_template_id')
                ->constrained()
                ->restrictOnDelete();

            $table
                ->foreignId('consent_template_version_id')
                ->constrained()
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Staff member who created the session
            |--------------------------------------------------------------------------
            */

            $table
                ->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Signer details
            |--------------------------------------------------------------------------
            */

            $table->string('signer_name');

            $table
                ->string('signer_email')
                ->nullable();

            $table
                ->string('signer_reference')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Public session token
            |--------------------------------------------------------------------------
            |
            | We will later use this token in the signer-facing URL.
            | It prevents us from exposing sequential database IDs.
            */

            $table
                ->uuid('access_token')
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Session state
            |--------------------------------------------------------------------------
            */

            $table
                ->string('status')
                ->default('pending');

            /*
            |--------------------------------------------------------------------------
            | Responses
            |--------------------------------------------------------------------------
            |
            | Responses will eventually contain answers to the additional
            | fields defined inside the published template version.
            */

            $table
                ->json('responses')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Important timestamps
            |--------------------------------------------------------------------------
            */

            $table
                ->timestamp('started_at')
                ->nullable();

            $table
                ->timestamp('completed_at')
                ->nullable();

            $table
                ->timestamp('cancelled_at')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Helpful database indexes
            |--------------------------------------------------------------------------
            */

            $table->index([
                'organization_id',
                'status',
            ]);

            $table->index([
                'consent_template_id',
                'created_at',
            ]);
        });
    }

    /**
     * Remove the consent sessions table.
     */
    public function down(): void
    {
        Schema::dropIfExists('consent_sessions');
    }
};
