<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store the active monthly or annual billing cycle.
     */
    public function up(): void
    {
        Schema::table(
            'organization_subscriptions',
            function (Blueprint $table): void {
                $table
                    ->string(
                        'billing_cycle',
                        20
                    )
                    ->default('monthly')
                    ->after('payment_status');
            }
        );
    }

    /**
     * Remove the billing cycle.
     */
    public function down(): void
    {
        Schema::table(
            'organization_subscriptions',
            function (Blueprint $table): void {
                $table->dropColumn(
                    'billing_cycle'
                );
            }
        );
    }
};
