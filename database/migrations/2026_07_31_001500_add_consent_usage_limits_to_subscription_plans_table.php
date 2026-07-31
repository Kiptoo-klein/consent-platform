<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add configurable consent usage limits to subscription plans.
     *
     * Null means unlimited. Zero means the feature is disabled.
     */
    public function up(): void
    {
        Schema::table(
            'subscription_plans',
            function (Blueprint $table): void {
                $table
                    ->unsignedInteger('max_consent_templates')
                    ->nullable()
                    ->after('max_active_kiosks');

                $table
                    ->unsignedInteger(
                        'max_signed_consents_per_period'
                    )
                    ->nullable()
                    ->after('max_consent_templates');
            }
        );
    }

    /**
     * Remove consent usage limits.
     */
    public function down(): void
    {
        Schema::table(
            'subscription_plans',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'max_consent_templates',
                    'max_signed_consents_per_period',
                ]);
            }
        );
    }
};
