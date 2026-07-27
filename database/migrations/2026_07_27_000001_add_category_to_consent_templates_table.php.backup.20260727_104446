<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add an organization-managed category to each consent template.
     */
    public function up(): void
    {
        Schema::table('consent_templates', function (Blueprint $table) {
            $table->string('category', 100)
                ->nullable()
                ->after('description');

            $table->index(
                [
                    'organization_id',
                    'category',
                ],
                'consent_templates_org_category_index'
            );
        });
    }

    /**
     * Remove template category support.
     */
    public function down(): void
    {
        Schema::table('consent_templates', function (Blueprint $table) {
            $table->dropIndex(
                'consent_templates_org_category_index'
            );

            $table->dropColumn('category');
        });
    }
};
