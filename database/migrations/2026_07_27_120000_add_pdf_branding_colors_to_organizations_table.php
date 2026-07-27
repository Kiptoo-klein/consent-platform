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
        if (! Schema::hasColumn('organizations', 'pdf_primary_color')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->string('pdf_primary_color', 7)
                    ->default('#17324D')
                    ->after('accent_color');
            });
        }

        if (! Schema::hasColumn('organizations', 'pdf_accent_color')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->string('pdf_accent_color', 7)
                    ->default('#0F766E')
                    ->after('pdf_primary_color');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('organizations', 'pdf_accent_color')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->dropColumn('pdf_accent_color');
            });
        }

        if (Schema::hasColumn('organizations', 'pdf_primary_color')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->dropColumn('pdf_primary_color');
            });
        }
    }
};

