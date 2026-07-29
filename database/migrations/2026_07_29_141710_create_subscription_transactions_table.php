<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'subscription_transactions',
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

                $table->string('reference', 120)->unique();

                $table->string('type', 30)->index();
                $table->string('status', 30)->index();

                $table->decimal('amount', 12, 2);
                $table->char('currency', 3);

                $table
                    ->string('payment_method', 100)
                    ->nullable();

                $table->timestamp('paid_at')->nullable();

                $table
                    ->timestamp('period_starts_at')
                    ->nullable();

                $table
                    ->timestamp('period_ends_at')
                    ->nullable();

                $table->text('notes')->nullable();

                $table
                    ->foreignId('recorded_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->index([
                    'organization_id',
                    'paid_at',
                ]);

                $table->index([
                    'organization_subscription_id',
                    'type',
                ]);

                $table->index([
                    'organization_id',
                    'status',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_transactions');
    }
};
