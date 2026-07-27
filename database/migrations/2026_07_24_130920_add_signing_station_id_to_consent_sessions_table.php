<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consent_sessions', function (Blueprint $table) {
            $table->foreignId('signing_station_id')
                ->nullable()
                ->after('consent_template_version_id')
                ->constrained()
                ->nullOnDelete();

            $table->index([
                'organization_id',
                'signing_station_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('consent_sessions', function (Blueprint $table) {
            $table->dropIndex([
                'organization_id',
                'signing_station_id',
            ]);

            $table->dropConstrainedForeignId(
                'signing_station_id'
            );
        });
    }
};
