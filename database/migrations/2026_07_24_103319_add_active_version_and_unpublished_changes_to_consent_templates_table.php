<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('consent_templates', function (Blueprint $table) {
            $table->foreignId('active_version_id')
                ->nullable()
                ->after('template_schema')
                ->constrained('consent_template_versions')
                ->restrictOnDelete();

            $table->boolean('has_unpublished_changes')
                ->default(true)
                ->after('active_version_id');
        });

        /*
        |--------------------------------------------------------------------------
        | Backfill existing templates
        |--------------------------------------------------------------------------
        |
        | Existing published templates should point to their newest published
        | version. Existing drafts should remain unpublished working copies.
        |
        */

        $templates = DB::table('consent_templates')
            ->select('id', 'status')
            ->orderBy('id')
            ->get();

        foreach ($templates as $template) {
            $latestVersionId = DB::table('consent_template_versions')
                ->where('consent_template_id', $template->id)
                ->orderByDesc('version_number')
                ->value('id');

            if ($latestVersionId !== null) {
                DB::table('consent_templates')
                    ->where('id', $template->id)
                    ->update([
                        'active_version_id' => $latestVersionId,
                        'has_unpublished_changes' => false,
                    ]);
            } else {
                DB::table('consent_templates')
                    ->where('id', $template->id)
                    ->update([
                        'has_unpublished_changes' => true,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consent_templates', function (Blueprint $table) {
            $table->dropForeign([
                'active_version_id',
            ]);

            $table->dropColumn([
                'active_version_id',
                'has_unpublished_changes',
            ]);
        });
    }
};
