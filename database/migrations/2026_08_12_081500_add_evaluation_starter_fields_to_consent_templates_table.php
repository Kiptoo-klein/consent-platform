<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'consent_templates',
            function (Blueprint $table): void {
                /*
                 * Stable internal identity for Evaluation starter
                 * templates. Titles are editable, so they must never
                 * be used to identify a starter.
                 */
                $table->string(
                    'evaluation_starter_key',
                    64
                )->nullable();

                /*
                 * Starters are preserved for historical consent/version
                 * references after the organization becomes paid, but
                 * are permanently removed from future template use.
                 */
                $table->timestamp(
                    'evaluation_retired_at'
                )->nullable();

                $table->unique(
                    [
                        'organization_id',
                        'evaluation_starter_key',
                    ],
                    'consent_templates_org_eval_starter_unique'
                );

                $table->index(
                    [
                        'organization_id',
                        'evaluation_retired_at',
                    ],
                    'consent_templates_org_eval_retired_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'consent_templates',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'consent_templates_org_eval_starter_unique'
                );

                $table->dropIndex(
                    'consent_templates_org_eval_retired_index'
                );

                $table->dropColumn([
                    'evaluation_starter_key',
                    'evaluation_retired_at',
                ]);
            }
        );
    }
};
