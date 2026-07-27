<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\ConsentSession;
use App\Models\ConsentSignature;
use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\SigningStation;
use App\Models\User;
use Illuminate\View\View;

class PlatformDashboardController extends Controller
{
    /**
     * Display the platform administration dashboard.
     */
    public function index(): View
    {
        $statistics = [
            'organizations' => Organization::count(),
            'users' => User::count(),
            'consent_templates' => ConsentTemplate::count(),
            'consent_sessions' => ConsentSession::count(),
            'consent_signatures' => ConsentSignature::count(),
            'signing_stations' => SigningStation::count(),
        ];

        return view('platform.dashboard', [
            'statistics' => $statistics,
        ]);
    }
}
