<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'consent_sessions',
            function (Blueprint $table): void {
                $table->string('signing_channel', 32)
                    ->nullable()
                    ->index();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'consent_sessions',
            function (Blueprint $table): void {
                $table->dropIndex([
                    'signing_channel',
                ]);

                $table->dropColumn('signing_channel');
            }
        );
    }
};
