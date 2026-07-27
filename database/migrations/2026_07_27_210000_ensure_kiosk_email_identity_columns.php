<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('signing_stations')) {
            return;
        }

        if (! Schema::hasColumn('signing_stations', 'sender_name')) {
            Schema::table('signing_stations', function (Blueprint $table): void {
                $table->string('sender_name', 120)->nullable();
            });
        }

        if (! Schema::hasColumn('signing_stations', 'reply_to_email')) {
            Schema::table('signing_stations', function (Blueprint $table): void {
                $table->string('reply_to_email')->nullable();
            });
        }

        if (! Schema::hasColumn('signing_stations', 'email_description')) {
            Schema::table('signing_stations', function (Blueprint $table): void {
                $table->text('email_description')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Intentionally retained. These columns may have been installed by
        // the earlier kiosk email-identity feature and contain organization data.
    }
};
