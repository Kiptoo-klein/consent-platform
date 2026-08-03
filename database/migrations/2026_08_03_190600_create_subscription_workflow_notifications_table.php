<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'subscription_workflow_notifications',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('organization_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table
                    ->foreignId('subscription_invoice_id')
                    ->constrained('subscription_invoices')
                    ->cascadeOnDelete();

                $table
                    ->foreignId(
                        'organization_subscription_plan_request_id'
                    )
                    ->constrained(
                        'organization_subscription_plan_requests'
                    )
                    ->cascadeOnDelete();

                $table
                    ->foreignId('subscription_transaction_id')
                    ->nullable()
                    ->constrained('subscription_transactions')
                    ->nullOnDelete();

                $table
                    ->foreignId('recipient_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('event', 50)->index();
                $table->string('fingerprint', 64)->unique();
                $table->string('status', 20)->index();
                $table->string('recipient_email');
                $table->string('subject');
                $table->text('message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index(
                    [
                        'subscription_invoice_id',
                        'event',
                        'status',
                    ],
                    'subscription_workflow_invoice_event_idx'
                );

                $table->index(
                    [
                        'organization_id',
                        'created_at',
                    ],
                    'subscription_workflow_org_created_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'subscription_workflow_notifications'
        );
    }
};
