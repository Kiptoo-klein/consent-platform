<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store organization-initiated subscription plan requests.
     */
    public function up(): void
    {
        Schema::create(
            'organization_subscription_plan_requests',
            function (Blueprint $table): void {
                $table->id();

                $table
                    ->foreignId('organization_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table
                    ->foreignId(
                        'organization_subscription_id'
                    )
                    ->constrained(
                        'organization_subscriptions'
                    )
                    ->cascadeOnDelete();

                $table
                    ->foreignId(
                        'current_subscription_plan_id'
                    )
                    ->nullable()
                    ->constrained(
                        'subscription_plans'
                    )
                    ->nullOnDelete();

                $table
                    ->foreignId(
                        'requested_subscription_plan_id'
                    )
                    ->constrained(
                        'subscription_plans'
                    )
                    ->restrictOnDelete();

                $table
                    ->foreignId(
                        'requested_by_user_id'
                    )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->foreignId(
                        'subscription_invoice_id'
                    )
                    ->nullable();

                /*
                 * Use short explicit PostgreSQL names. The default foreign
                 * key and unique names exceed PostgreSQL's identifier limit
                 * and would otherwise be truncated to the same value.
                 */
                $table->unique(
                    'subscription_invoice_id',
                    'org_plan_request_invoice_unique'
                );

                $table
                    ->foreign(
                        'subscription_invoice_id',
                        'org_plan_request_invoice_fk'
                    )
                    ->references('id')
                    ->on('subscription_invoices')
                    ->nullOnDelete();

                $table->string(
                    'billing_cycle',
                    20
                );

                $table
                    ->string('status', 30)
                    ->default('pending');

                $table
                    ->decimal(
                        'monthly_price_snapshot',
                        12,
                        2
                    )
                    ->nullable();

                $table
                    ->decimal(
                        'annual_discount_percent_snapshot',
                        5,
                        2
                    )
                    ->default(0);

                $table->decimal(
                    'amount_snapshot',
                    12,
                    2
                );

                $table
                    ->char('currency', 3)
                    ->default('KES');

                $table->timestamp(
                    'requested_at'
                );

                $table
                    ->timestamp('resolved_at')
                    ->nullable();

                $table
                    ->foreignId(
                        'resolved_by_user_id'
                    )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->index(
                    [
                        'organization_id',
                        'status',
                        'created_at',
                    ],
                    'org_plan_request_status_created_idx'
                );

                $table->index(
                    [
                        'requested_subscription_plan_id',
                        'billing_cycle',
                    ],
                    'org_plan_request_plan_cycle_idx'
                );
            }
        );
    }

    /**
     * Remove organization plan requests.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'organization_subscription_plan_requests'
        );
    }
};
