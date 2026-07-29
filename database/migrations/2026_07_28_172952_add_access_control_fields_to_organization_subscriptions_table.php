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
        Schema::table('organization_subscriptions', function (Blueprint $table) {
            /*
             * The Organization Admin responsible for this subscription.
             *
             * This remains nullable during the initial migration so
             * existing organizations can be backfilled safely.
             */
            $table->foreignId('billing_owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Examples: unpaid, paid, past_due, failed, or refunded.
             */
            $table->string('payment_status', 30)
                ->default('unpaid')
                ->index();

            /*
             * A Platform Admin may allow the organization to operate
             * without a paid subscription.
             */
            $table->timestamp('bypass_approved_at')
                ->nullable();

            $table->foreignId('bypass_approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('bypass_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId(
                'bypass_approved_by_user_id'
            );

            $table->dropConstrainedForeignId(
                'billing_owner_user_id'
            );

            $table->dropColumn([
                'payment_status',
                'bypass_approved_at',
                'bypass_reason',
            ]);
        });
    }
};
