<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_signatures', function (Blueprint $table) {
            $table->id();

            $table->foreignId('consent_session_id')
                ->constrained('consent_sessions')
                ->cascadeOnDelete();

            $table->string('signer_name');

            /*
            |--------------------------------------------------------------------------
            | Signature data
            |--------------------------------------------------------------------------
            |
            | This will later store the drawn signature as encoded image data.
            | It remains nullable until signature capture is implemented.
            */

            $table->longText('signature_data')->nullable();

            $table->string('signature_type')
                ->default('drawn');

            $table->timestamp('signed_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Audit information
            |--------------------------------------------------------------------------
            */

            $table->string('ip_address', 45)->nullable();

            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index('consent_session_id');
            $table->index('signed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_signatures');
    }
};
