<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track whether the customer has explicitly selected a plan.
     */
    public function up(): void
    {
        Schema::table(
            'organization_subscriptions',
            function (Blueprint $table): void {
                $table
                    ->boolean('requires_plan_selection')
                    ->default(false)
                    ->after('billing_cycle');

                $table
                    ->timestamp('plan_selected_at')
                    ->nullable()
                    ->after('requires_plan_selection');
            }
        );
    }

    /**
     * Remove the customer plan-selection state.
     */
    public function down(): void
    {
        Schema::table(
            'organization_subscriptions',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'requires_plan_selection',
                    'plan_selected_at',
                ]);
            }
        );
    }
};
