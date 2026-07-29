<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'subscription_invoices',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('organization_subscription_id')
                    ->constrained('organization_subscriptions')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('organization_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table
                    ->foreignId('subscription_plan_id')
                    ->constrained('subscription_plans')
                    ->restrictOnDelete();

                $table
                    ->string('invoice_number', 120)
                    ->unique();

                $table
                    ->string('status', 30)
                    ->index();

                $table->date('issue_date')->nullable();
                $table->date('due_date')->nullable();

                $table->decimal('subtotal', 12, 2);

                $table
                    ->decimal('tax_amount', 12, 2)
                    ->default(0);

                $table->decimal('total_amount', 12, 2);
                $table->char('currency', 3);

                $table->text('notes')->nullable();

                $table
                    ->foreignId('issued_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('paid_at')->nullable();
                $table->timestamp('voided_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();

                $table->timestamps();

                $table->index([
                    'organization_id',
                    'status',
                ]);

                $table->index([
                    'organization_id',
                    'due_date',
                ]);

                $table->index([
                    'organization_subscription_id',
                    'status',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'subscription_invoices'
        );
    }
};
