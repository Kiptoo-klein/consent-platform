<?php

namespace App\Http\Controllers;

use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Services\EvaluationOnboardingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the organization dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $organizationId = $user->organization_id;

        abort_if(
            ! $organizationId,
            403,
            'Your account is not connected to an organization.'
        );

        /*
        |--------------------------------------------------------------------------
        | Consent Record Statistics
        |--------------------------------------------------------------------------
        */

        $recordStatistics = ConsentSession::query()
            ->where('organization_id', $organizationId)
            ->selectRaw('COUNT(*) as total_records')
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_records',
                [ConsentSession::STATUS_PENDING]
            )
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress_records',
                [ConsentSession::STATUS_IN_PROGRESS]
            )
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_records',
                [ConsentSession::STATUS_COMPLETED]
            )
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled_records',
                [ConsentSession::STATUS_CANCELLED]
            )
            ->first();

        $completedToday = ConsentSession::query()
            ->where('organization_id', $organizationId)
            ->where('status', ConsentSession::STATUS_COMPLETED)
            ->whereDate('completed_at', today())
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Template Statistics
        |--------------------------------------------------------------------------
        */

        $totalTemplates = ConsentTemplate::query()
            ->where('organization_id', $organizationId)
            ->whereNull('evaluation_retired_at')
            ->count();

        $publishedTemplates = ConsentTemplate::query()
            ->where('organization_id', $organizationId)
            ->whereNull('evaluation_retired_at')
            ->whereHas('versions')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Recent Consent Records
        |--------------------------------------------------------------------------
        */

        $recentConsentRecords = ConsentSession::query()
            ->where('organization_id', $organizationId)
            ->with([
                'consentTemplate',
                'consentTemplateVersion',
            ])
            ->latest()
            ->limit(8)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Recent Consent Templates
        |--------------------------------------------------------------------------
        */

        $recentTemplates = ConsentTemplate::query()
            ->where('organization_id', $organizationId)
            ->whereNull('evaluation_retired_at')
            ->withCount([
                'versions',
                'consentSessions',
            ])
            ->latest()
            ->limit(5)
            ->get();

        $evaluationOnboarding =
            app(
                EvaluationOnboardingService::class
            )->dashboardState(
                (int) $organizationId
            );

        return view('dashboard', [
            'totalRecords' =>
                (int) ($recordStatistics->total_records ?? 0),

            'pendingRecords' =>
                (int) ($recordStatistics->pending_records ?? 0),

            'inProgressRecords' =>
                (int) ($recordStatistics->in_progress_records ?? 0),

            'completedRecords' =>
                (int) ($recordStatistics->completed_records ?? 0),

            'cancelledRecords' =>
                (int) ($recordStatistics->cancelled_records ?? 0),

            'completedToday' =>
                $completedToday,

            'totalTemplates' =>
                $totalTemplates,

            'publishedTemplates' =>
                $publishedTemplates,

            'recentConsentRecords' =>
                $recentConsentRecords,

            'recentTemplates' =>
                $recentTemplates,

            'evaluationOnboarding' =>
                $evaluationOnboarding,
        ]);
    }
}
