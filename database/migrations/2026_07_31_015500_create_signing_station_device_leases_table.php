<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('signing_station_device_leases')) {
            return;
        }

        Schema::create(
            'signing_station_device_leases',
            function (Blueprint $table): void {
                $table->id();

                $table->uuid('lease_token')
                    ->unique();

                $table->foreignId('organization_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('signing_station_id')
                    ->constrained()
                    ->cascadeOnDelete();

                /*
                 * A SHA-256 digest of the random device token stored in
                 * the browser's Laravel session. Raw session identifiers
                 * and raw device tokens are never stored in this table.
                 */
                $table->string('device_key', 64);

                /*
                 * Binding the lease to the current public station token
                 * makes regenerated station links invalidate old leases.
                 */
                $table->string('station_token_hash', 64);

                $table->timestamp('last_seen_at')
                    ->index();

                $table->timestamp('expires_at')
                    ->index();

                $table->timestamps();

                $table->unique(
                    [
                        'organization_id',
                        'device_key',
                    ],
                    'station_device_lease_org_device_unique'
                );

                $table->index(
                    [
                        'organization_id',
                        'expires_at',
                    ],
                    'station_device_lease_org_expiry_index'
                );

                $table->index(
                    [
                        'signing_station_id',
                        'expires_at',
                    ],
                    'station_device_lease_station_expiry_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'signing_station_device_leases'
        );
    }
};
