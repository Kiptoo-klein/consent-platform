<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add signing-deadline fields to consent records.
     */
    public function up(): void
    {
        Schema::table(
            'consent_sessions',
            function (Blueprint $table): void {
                $table
                    ->timestamp('expires_at')
                    ->nullable()
                    ->after('cancelled_at');

                $table
                    ->timestamp('expired_at')
                    ->nullable()
                    ->after('expires_at');

                $table->index([
                    'status',
                    'expires_at',
                ]);
            }
        );
    }

    /**
     * Remove signing-deadline fields.
     */
    public function down(): void
    {
        Schema::table(
            'consent_sessions',
            function (Blueprint $table): void {
                $table->dropIndex([
                    'status',
                    'expires_at',
                ]);

                $table->dropColumn([
                    'expires_at',
                    'expired_at',
                ]);
            }
        );
    }
};
