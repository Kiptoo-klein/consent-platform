<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add editable pricing and annual billing settings.
     */
    public function up(): void
    {
        Schema::table(
            'subscription_plans',
            function (Blueprint $table): void {
                $table
                    ->decimal(
                        'monthly_price',
                        12,
                        2
                    )
                    ->nullable()
                    ->after('description');

                $table
                    ->char('currency', 3)
                    ->default('KES')
                    ->after('monthly_price');

                $table
                    ->boolean(
                        'annual_billing_enabled'
                    )
                    ->default(false)
                    ->after('currency');

                $table
                    ->decimal(
                        'annual_discount_percent',
                        5,
                        2
                    )
                    ->default(10)
                    ->after(
                        'annual_billing_enabled'
                    );
            }
        );
    }

    /**
     * Remove editable pricing settings.
     */
    public function down(): void
    {
        Schema::table(
            'subscription_plans',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'monthly_price',
                    'currency',
                    'annual_billing_enabled',
                    'annual_discount_percent',
                ]);
            }
        );
    }
};
