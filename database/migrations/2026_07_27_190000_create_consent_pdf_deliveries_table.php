<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('consent_pdf_deliveries')) {
            return;
        }

        Schema::create('consent_pdf_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consent_session_id')
                ->constrained('consent_sessions')
                ->cascadeOnDelete();
            $table->string('recipient');
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                'consent_session_id',
                'consent_pdf_delivery_session_unique'
            );
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_pdf_deliveries');
    }
};
