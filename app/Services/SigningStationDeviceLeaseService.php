<?php

namespace App\Services;

use App\Models\OrganizationSubscription;
use App\Models\SigningStation;
use App\Models\SigningStationDeviceLease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SigningStationDeviceLeaseService
{
    public const SESSION_KEY =
        'signing_station_device_token';

    public const LEASE_SECONDS = 180;

    public const HEARTBEAT_SECONDS = 45;

    public function acquireOrTouch(
        Request $request,
        SigningStation $station
    ): ?SigningStationDeviceLease {
        if (! $this->ready()) {
            return null;
        }

        $deviceKey = $this->deviceKey(
            $request
        );

        $stationTokenHash = hash(
            'sha256',
            (string) $station->station_token
        );

        return DB::transaction(
            function () use (
                $station,
                $deviceKey,
                $stationTokenHash
            ): SigningStationDeviceLease {
                $now = now();
                $expiresAt = $now
                    ->copy()
                    ->addSeconds(
                        self::LEASE_SECONDS
                    );

                /*
                 * Locking the subscription row serializes competing
                 * device acquisitions for the same organization.
                 */
                $subscription =
                    OrganizationSubscription::query()
                        ->where(
                            'organization_id',
                            $station->organization_id
                        )
                        ->with('plan')
                        ->lockForUpdate()
                        ->first();

                SigningStationDeviceLease::query()
                    ->where(
                        'organization_id',
                        $station->organization_id
                    )
                    ->where(
                        'expires_at',
                        '<=',
                        $now
                    )
                    ->delete();

                $existing =
                    SigningStationDeviceLease::query()
                        ->where(
                            'organization_id',
                            $station->organization_id
                        )
                        ->where(
                            'device_key',
                            $deviceKey
                        )
                        ->lockForUpdate()
                        ->first();

                if ($existing !== null) {
                    $existing->forceFill([
                        'signing_station_id' =>
                            $station->id,

                        'station_token_hash' =>
                            $stationTokenHash,

                        'last_seen_at' =>
                            $now,

                        'expires_at' =>
                            $expiresAt,
                    ])->save();

                    return $existing->refresh();
                }

                $limit =
                    $subscription
                        ?->effectiveActiveKioskLimit();

                if ($limit !== null) {
                    $activeLeaseCount =
                        SigningStationDeviceLease::query()
                            ->where(
                                'organization_id',
                                $station->organization_id
                            )
                            ->where(
                                'expires_at',
                                '>',
                                $now
                            )
                            ->count();

                    if (
                        $activeLeaseCount
                        >= (int) $limit
                    ) {
                        throw ValidationException::withMessages([
                            'kiosk' =>
                                'All kiosk device slots for this subscription plan are currently in use.',
                        ]);
                    }
                }

                return SigningStationDeviceLease::query()
                    ->create([
                        'lease_token' =>
                            (string) Str::uuid(),

                        'organization_id' =>
                            $station->organization_id,

                        'signing_station_id' =>
                            $station->id,

                        'device_key' =>
                            $deviceKey,

                        'station_token_hash' =>
                            $stationTokenHash,

                        'last_seen_at' =>
                            $now,

                        'expires_at' =>
                            $expiresAt,
                    ]);
            },
            3
        );
    }

    public function releaseForStation(
        SigningStation $station
    ): void {
        if (! $this->ready()) {
            return;
        }

        SigningStationDeviceLease::query()
            ->where(
                'signing_station_id',
                $station->id
            )
            ->delete();
    }

    public function activeCountForOrganization(
        int $organizationId
    ): int {
        if (! $this->ready()) {
            return 0;
        }

        return SigningStationDeviceLease::query()
            ->where(
                'organization_id',
                $organizationId
            )
            ->where(
                'expires_at',
                '>',
                now()
            )
            ->count();
    }

    private function deviceKey(
        Request $request
    ): string {
        $deviceToken = $request
            ->session()
            ->get(
                self::SESSION_KEY
            );

        if (
            ! is_string($deviceToken)
            || strlen($deviceToken) < 40
        ) {
            $deviceToken = Str::random(64);

            $request
                ->session()
                ->put(
                    self::SESSION_KEY,
                    $deviceToken
                );
        }

        return hash(
            'sha256',
            $deviceToken
        );
    }

    private function ready(): bool
    {
        return Schema::hasTable(
            'signing_station_device_leases'
        );
    }
}
