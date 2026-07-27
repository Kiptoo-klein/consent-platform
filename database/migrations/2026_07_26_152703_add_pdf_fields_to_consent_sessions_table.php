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
        Schema::table('consent_sessions', function (Blueprint $table) {
            $table->string('pdf_path')
                ->nullable()
                ->after('cancelled_at');

            $table->timestamp('pdf_generated_at')
                ->nullable()
                ->after('pdf_path');

            $table->timestamp('pdf_emailed_at')
                ->nullable()
                ->after('pdf_generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consent_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'pdf_path',
                'pdf_generated_at',
                'pdf_emailed_at',
            ]);
        });
    }
};
