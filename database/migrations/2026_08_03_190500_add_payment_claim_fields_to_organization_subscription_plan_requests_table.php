<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'organization_subscription_plan_requests',
            function (Blueprint $table): void {
                $table
                    ->string('payment_claim_status', 30)
                    ->nullable()
                    ->index();

                $table
                    ->decimal('payment_claim_amount', 12, 2)
                    ->nullable();

                $table
                    ->char('payment_claim_currency', 3)
                    ->nullable();

                $table
                    ->string('payment_claim_method', 100)
                    ->nullable();

                $table
                    ->string('payment_claim_reference', 120)
                    ->nullable()
                    ->index();

                $table
                    ->timestamp('payment_claim_paid_at')
                    ->nullable();

                $table
                    ->text('payment_claim_notes')
                    ->nullable();

                $table
                    ->timestamp('payment_claim_submitted_at')
                    ->nullable();

                $table
                    ->foreignId(
                        'payment_claim_submitted_by_user_id'
                    )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->timestamp('payment_claim_reviewed_at')
                    ->nullable();

                $table
                    ->foreignId(
                        'payment_claim_reviewed_by_user_id'
                    )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->text('payment_claim_rejection_reason')
                    ->nullable();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'organization_subscription_plan_requests',
            function (Blueprint $table): void {
                $table->dropConstrainedForeignId(
                    'payment_claim_reviewed_by_user_id'
                );

                $table->dropConstrainedForeignId(
                    'payment_claim_submitted_by_user_id'
                );

                $table->dropIndex([
                    'payment_claim_reference',
                ]);

                $table->dropIndex([
                    'payment_claim_status',
                ]);

                $table->dropColumn([
                    'payment_claim_status',
                    'payment_claim_amount',
                    'payment_claim_currency',
                    'payment_claim_method',
                    'payment_claim_reference',
                    'payment_claim_paid_at',
                    'payment_claim_notes',
                    'payment_claim_submitted_at',
                    'payment_claim_reviewed_at',
                    'payment_claim_rejection_reason',
                ]);
            }
        );
    }
};
