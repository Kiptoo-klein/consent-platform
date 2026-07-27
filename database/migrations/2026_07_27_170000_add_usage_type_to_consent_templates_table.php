<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the operational workflow assigned to each consent template.
     *
     * Existing templates default to "both" so current individual-consent
     * and signing-station workflows remain available after the migration.
     */
    public function up(): void
    {
        Schema::table('consent_templates', function (Blueprint $table) {
            $table->string('usage_type', 32)
                ->default('both')
                ->after('category');

            $table->index([
                'organization_id',
                'usage_type',
            ]);
        });
    }

    /**
     * Remove the template workflow setting.
     */
    public function down(): void
    {
        Schema::table('consent_templates', function (Blueprint $table) {
            $table->dropIndex([
                'organization_id',
                'usage_type',
            ]);

            $table->dropColumn('usage_type');
        });
    }
};
