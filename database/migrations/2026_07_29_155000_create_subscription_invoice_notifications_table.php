<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'subscription_invoice_notifications',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('organization_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table
                    ->foreignId('subscription_invoice_id')
                    ->constrained('subscription_invoices')
                    ->cascadeOnDelete();

                $table->foreignId('recipient_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('reminder_key', 50);
                $table->string('status', 20);
                $table->string('recipient_email');
                $table->string('subject');
                $table->text('message')->nullable();
                $table->timestamp('scheduled_for')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index([
                    'subscription_invoice_id',
                    'status',
                ]);

                $table->index([
                    'subscription_invoice_id',
                    'reminder_key',
                    'status',
                ]);

                $table->index([
                    'organization_id',
                    'created_at',
                ]);

                $table->index([
                    'recipient_user_id',
                    'created_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'subscription_invoice_notifications'
        );
    }
};
