<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PlatformActivityLogController extends Controller
{
    /**
     * Display platform activity logs.
     */
    public function index(Request $request): View
    {
        $query = ActivityLog::query()
            ->with([
                'user',
                'organization',
                'subject',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('organization')) {
            $query->where('organization_id', $request->organization);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        /*
        |--------------------------------------------------------------------------
        | Logs
        |--------------------------------------------------------------------------
        */

        $logs = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        $organizations = Organization::orderBy('name')->get();

        $actions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        /*
        |--------------------------------------------------------------------------
        | Dashboard Statistics
        |--------------------------------------------------------------------------
        */

        $statistics = [
            'total' => ActivityLog::count(),

            'today' => ActivityLog::whereDate(
                'created_at',
                today()
            )->count(),

            'this_week' => ActivityLog::whereBetween(
                'created_at',
                [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ]
            )->count(),

            'organizations' => Organization::count(),
        ];

        return view('platform.activity-logs.index', compact(
            'logs',
            'organizations',
            'actions',
            'statistics'
        ));
    }

    /**
     * Display a single activity log.
     */
    public function show(ActivityLog $activityLog): View
    {
        $activityLog->load([
            'user',
            'organization',
            'subject',
        ]);

        return view('platform.activity-logs.show', [
            'activityLog' => $activityLog,
        ]);
    }
}
