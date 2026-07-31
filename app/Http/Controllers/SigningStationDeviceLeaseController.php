<?php

namespace App\Http\Controllers;

use App\Models\SigningStationDeviceLease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SigningStationDeviceLeaseController extends Controller
{
    public function heartbeat(
        Request $request,
        string $stationToken
    ): JsonResponse {
        $lease = $request
            ->attributes
            ->get(
                'kioskDeviceLease'
            );

        return response()->json([
            'active' =>
                $lease
                instanceof SigningStationDeviceLease,

            'expires_at' =>
                $lease
                    ?->expires_at
                    ?->toIso8601String(),
        ]);
    }
}
