<?php

namespace App\Http\Middleware;

use App\Models\ConsentSession;
use App\Models\SigningStation;
use App\Services\SigningStationDeviceLeaseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EnsureSigningStationDeviceLease
{
    public function __construct(
        private readonly SigningStationDeviceLeaseService $leaseService
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $station = $this->stationFor(
            $request
        );

        /*
         * Manually created public consents do not use kiosk capacity.
         * Missing, paused, or invalid stations remain the responsibility
         * of their existing token and controller checks.
         */
        if ($station === null) {
            return $next($request);
        }

        if ($this->isQrScanRequest($request, $station)) {
            return $next($request);
        }

        try {
            $lease =
                $this
                    ->leaseService
                    ->acquireOrTouch(
                        $request,
                        $station
                    );
        } catch (ValidationException $exception) {
            $message =
                $exception
                    ->errors()['kiosk'][0]
                ?? 'The kiosk device limit has been reached.';

            if (
                $request->expectsJson()
                || $request->isXmlHttpRequest()
            ) {
                return response()->json(
                    [
                        'message' =>
                            $message,
                    ],
                    429
                );
            }

            return response()->view(
                'public-signing-stations.device-limit-reached',
                [
                    'station' =>
                        $station,

                    'message' =>
                        $message,

                    'leaseSeconds' =>
                        SigningStationDeviceLeaseService::
                            LEASE_SECONDS,
                ],
                429
            );
        }

        $request->attributes->set(
            'kioskDeviceLease',
            $lease
        );

        $request->attributes->set(
            'kioskHeartbeatUrl',
            route(
                'public-signing-stations.heartbeat',
                [
                    'stationToken' =>
                        $station->station_token,
                ]
            )
        );

        $request->attributes->set(
            'kioskHeartbeatSeconds',
            SigningStationDeviceLeaseService::
                HEARTBEAT_SECONDS
        );

        return $next($request);
    }

    /**
     * QR scans run on personal signer devices and must not consume
     * an organization's shared-kiosk device allowance.
     */
    private function isQrScanRequest(
        Request $request,
        SigningStation $station
    ): bool {
        $stationSessionKey =
            'signing_station_channel_'.$station->id;

        if (
            $request->session()->get($stationSessionKey) ===
                ConsentSession::SIGNING_CHANNEL_QR_SCAN
        ) {
            return true;
        }

        $accessToken = $request->route('accessToken');

        if (
            ! is_string($accessToken)
            || $accessToken === ''
        ) {
            return false;
        }

        return ConsentSession::query()
            ->where('access_token', $accessToken)
            ->where(
                'signing_channel',
                ConsentSession::SIGNING_CHANNEL_QR_SCAN
            )
            ->exists();
    }

    private function stationFor(
        Request $request
    ): ?SigningStation {
        $stationToken = $request->route(
            'stationToken'
        );

        if (
            is_string($stationToken)
            && $stationToken !== ''
        ) {
            $station =
                SigningStation::query()
                    ->with([
                        'organization',
                        'consentTemplate',
                    ])
                    ->where(
                        'station_token',
                        $stationToken
                    )
                    ->where(
                        'active',
                        true
                    )
                    ->first();

            /*
             * A stale active flag must not consume a kiosk device
             * slot when the assigned template is no longer live.
             * The public controller remains responsible for the
             * user-facing unavailable response.
             */
            if (
                $station === null
                || ! $station->isAvailable()
            ) {
                return null;
            }

            return $station;
        }

        $accessToken = $request->route(
            'accessToken'
        );

        if (
            ! is_string($accessToken)
            || $accessToken === ''
        ) {
            return null;
        }

        $consentSession =
            ConsentSession::query()
                ->with([
                    'signingStation.organization',
                    'signingStation.consentTemplate',
                ])
                ->where(
                    'access_token',
                    $accessToken
                )
                ->first();

        $station =
            $consentSession
                ?->signingStation;

        if (
            $station === null
            || ! $station->isAvailable()
        ) {
            return null;
        }

        return $station;
    }
}
