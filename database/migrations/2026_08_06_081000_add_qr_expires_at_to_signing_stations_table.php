<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasColumn(
                'signing_stations',
                'qr_expires_at'
            )
        ) {
            Schema::table(
                'signing_stations',
                function (Blueprint $table): void {
                    $table
                        ->timestamp('qr_expires_at')
                        ->nullable()
                        ->index();
                }
            );
        }

        /*
         * Existing stations receive an initial 24-hour QR window
         * when this feature is deployed.
         */
        DB::table('signing_stations')
            ->whereNull('qr_expires_at')
            ->update([
                'qr_expires_at' =>
                    now()->addHours(24),
            ]);
    }

    public function down(): void
    {
        if (
            ! Schema::hasColumn(
                'signing_stations',
                'qr_expires_at'
            )
        ) {
            return;
        }

        Schema::table(
            'signing_stations',
            function (Blueprint $table): void {
                $table->dropIndex([
                    'qr_expires_at',
                ]);

                $table->dropColumn(
                    'qr_expires_at'
                );
            }
        );
    }
};
