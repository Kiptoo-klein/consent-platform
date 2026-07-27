<?php

namespace App\Http\Controllers;

use App\Models\SigningStation;
use App\Models\SigningStationFlow;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SigningStationAnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureFeatureReady();

        $organizationId = $this->organizationId($request);
        $filters = $this->filters($request, $organizationId);

        $flows = $this->baseQuery($organizationId, $filters)
            ->with([
                'signingStation:id,name,consent_template_id',
                'signingStation.consentTemplate:id,title',
                'consentSession:id,signer_name,signer_email',
            ])
            ->orderBy('started_at')
            ->get();

        $stations = SigningStation::query()
            ->where('organization_id', $organizationId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $completed = $flows->where(
            'status',
            SigningStationFlow::STATUS_COMPLETED
        );
        $abandoned = $flows->where(
            'status',
            SigningStationFlow::STATUS_ABANDONED
        );
        $cancelled = $flows->where(
            'status',
            SigningStationFlow::STATUS_CANCELLED
        );
        $active = $flows->whereIn('status', [
            SigningStationFlow::STATUS_REVIEWING,
            SigningStationFlow::STATUS_DETAILS,
            SigningStationFlow::STATUS_SIGNING,
        ]);

        $terminalCount = $completed->count()
            + $abandoned->count()
            + $cancelled->count();

        $completionDurations = $completed
            ->filter(fn (SigningStationFlow $flow): bool =>
                $flow->started_at !== null
                && $flow->completed_at !== null
            )
            ->map(fn (SigningStationFlow $flow): int =>
                max(
                    0,
                    (int) $flow->started_at->diffInSeconds(
                        $flow->completed_at
                    )
                )
            );

        $staleCutoff = now()->subMinutes(10);
        $staleActive = $active->filter(
            fn (SigningStationFlow $flow): bool =>
                $flow->last_activity_at !== null
                && $flow->last_activity_at->lt($staleCutoff)
        );

        $metrics = [
            'total' => $flows->count(),
            'completed' => $completed->count(),
            'abandoned' => $abandoned->count(),
            'cancelled' => $cancelled->count(),
            'active' => $active->count(),
            'stale_active' => $staleActive->count(),
            'completion_rate' => $terminalCount > 0
                ? round(($completed->count() / $terminalCount) * 100, 1)
                : 0.0,
            'abandonment_rate' => $terminalCount > 0
                ? round(($abandoned->count() / $terminalCount) * 100, 1)
                : 0.0,
            'average_completion_seconds' => $completionDurations->isNotEmpty()
                ? (int) round((float) $completionDurations->average())
                : null,
        ];

        $abandonmentStages = [
            'review' => $abandoned
                ->where('current_stage', SigningStationFlow::STAGE_REVIEW)
                ->count(),
            'details' => $abandoned
                ->where('current_stage', SigningStationFlow::STAGE_DETAILS)
                ->count(),
            'signing' => $abandoned
                ->where('current_stage', SigningStationFlow::STAGE_SIGNING)
                ->count(),
        ];

        $dailyTrends = $this->dailyTrends(
            $flows,
            $filters['from'],
            $filters['to']
        );

        $stationPerformance = $this->stationPerformance($flows);
        $templatePerformance = $this->templatePerformance($flows);

        $recentAbandoned = $abandoned
            ->sortByDesc(fn (SigningStationFlow $flow) =>
                $flow->abandoned_at?->timestamp
                ?? $flow->updated_at?->timestamp
                ?? 0
            )
            ->take(20)
            ->values();

        return view('signing-stations.analytics', [
            'filters' => $filters,
            'stations' => $stations,
            'metrics' => $metrics,
            'abandonmentStages' => $abandonmentStages,
            'dailyTrends' => $dailyTrends,
            'stationPerformance' => $stationPerformance,
            'templatePerformance' => $templatePerformance,
            'recentAbandoned' => $recentAbandoned,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->ensureFeatureReady();

        $organizationId = $this->organizationId($request);
        $filters = $this->filters($request, $organizationId);

        $filename = sprintf(
            'kiosk-analytics-%s-to-%s.csv',
            $filters['from']->toDateString(),
            $filters['to']->toDateString()
        );

        return response()->streamDownload(
            function () use ($organizationId, $filters): void {
                $handle = fopen('php://output', 'wb');

                if ($handle === false) {
                    return;
                }

                fputcsv($handle, [
                    'Flow ID',
                    'Signing Station',
                    'Consent Template',
                    'Status',
                    'Last Stage',
                    'Signer Name',
                    'Signer Email',
                    'Started At',
                    'Completed At',
                    'Abandoned At',
                    'Cancelled At',
                    'Completion Seconds',
                    'Reason',
                ]);

                $this->baseQuery($organizationId, $filters)
                    ->with([
                        'signingStation:id,name,consent_template_id',
                        'signingStation.consentTemplate:id,title',
                        'consentSession:id,signer_name,signer_email',
                    ])
                    ->orderBy('id')
                    ->chunkById(200, function ($flows) use ($handle): void {
                        foreach ($flows as $flow) {
                            $duration = null;

                            if ($flow->started_at && $flow->completed_at) {
                                $duration = max(
                                    0,
                                    (int) $flow->started_at->diffInSeconds(
                                        $flow->completed_at
                                    )
                                );
                            }

                            fputcsv($handle, array_map(
                                fn ($value) => $this->csvValue($value),
                                [
                                    $flow->id,
                                    $flow->signingStation?->name,
                                    $flow->signingStation?->consentTemplate?->title,
                                    $flow->status,
                                    $flow->current_stage,
                                    $flow->consentSession?->signer_name,
                                    $flow->consentSession?->signer_email,
                                    $flow->started_at?->toDateTimeString(),
                                    $flow->completed_at?->toDateTimeString(),
                                    $flow->abandoned_at?->toDateTimeString(),
                                    $flow->cancelled_at?->toDateTimeString(),
                                    $duration,
                                    $flow->abandonment_reason,
                                ]
                            ));
                        }
                    });

                fclose($handle);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    private function filters(
        Request $request,
        int $organizationId
    ): array {
        $validated = Validator::make($request->query(), [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'station_id' => ['nullable', 'integer'],
        ])->validate();

        $to = filled($validated['to'] ?? null)
            ? CarbonImmutable::parse($validated['to'])->endOfDay()
            : CarbonImmutable::today()->endOfDay();

        $from = filled($validated['from'] ?? null)
            ? CarbonImmutable::parse($validated['from'])->startOfDay()
            : $to->subDays(29)->startOfDay();

        if ($from->gt($to)) {
            throw ValidationException::withMessages([
                'to' => 'The end date must be on or after the start date.',
            ]);
        }

        if ($from->diffInDays($to) > 180) {
            throw ValidationException::withMessages([
                'from' => 'Choose a date range of 180 days or less.',
            ]);
        }

        $stationId = filled($validated['station_id'] ?? null)
            ? (int) $validated['station_id']
            : null;

        if ($stationId !== null) {
            SigningStation::query()
                ->where('organization_id', $organizationId)
                ->findOrFail($stationId);
        }

        return [
            'from' => $from,
            'to' => $to,
            'station_id' => $stationId,
        ];
    }

    private function baseQuery(
        int $organizationId,
        array $filters
    ): Builder {
        return SigningStationFlow::query()
            ->where('organization_id', $organizationId)
            ->whereBetween('started_at', [
                $filters['from'],
                $filters['to'],
            ])
            ->when(
                $filters['station_id'],
                fn (Builder $query, int $stationId): Builder =>
                    $query->where('signing_station_id', $stationId)
            );
    }

    private function dailyTrends(
        Collection $flows,
        CarbonImmutable $from,
        CarbonImmutable $to
    ): array {
        $grouped = $flows->groupBy(
            fn (SigningStationFlow $flow): string =>
                $flow->started_at?->toDateString() ?? 'unknown'
        );

        $days = [];
        $cursor = $from->startOfDay();
        $end = $to->startOfDay();

        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            $dayFlows = $grouped->get($date, collect());

            $days[] = [
                'date' => $date,
                'label' => $cursor->format('M j'),
                'total' => $dayFlows->count(),
                'completed' => $dayFlows
                    ->where('status', SigningStationFlow::STATUS_COMPLETED)
                    ->count(),
                'abandoned' => $dayFlows
                    ->where('status', SigningStationFlow::STATUS_ABANDONED)
                    ->count(),
                'cancelled' => $dayFlows
                    ->where('status', SigningStationFlow::STATUS_CANCELLED)
                    ->count(),
            ];

            $cursor = $cursor->addDay();
        }

        $max = max(
            1,
            ...array_map(
                fn (array $day): int => $day['total'],
                $days
            )
        );

        return array_map(function (array $day) use ($max): array {
            $day['completed_width'] = ($day['completed'] / $max) * 100;
            $day['abandoned_width'] = ($day['abandoned'] / $max) * 100;
            $day['cancelled_width'] = ($day['cancelled'] / $max) * 100;

            return $day;
        }, $days);
    }

    private function stationPerformance(Collection $flows): Collection
    {
        return $flows
            ->groupBy('signing_station_id')
            ->map(function (Collection $stationFlows): array {
                $first = $stationFlows->first();
                $completed = $stationFlows->where(
                    'status',
                    SigningStationFlow::STATUS_COMPLETED
                );
                $abandoned = $stationFlows->where(
                    'status',
                    SigningStationFlow::STATUS_ABANDONED
                )->count();
                $cancelled = $stationFlows->where(
                    'status',
                    SigningStationFlow::STATUS_CANCELLED
                )->count();
                $terminal = $completed->count() + $abandoned + $cancelled;

                $durations = $completed
                    ->filter(fn (SigningStationFlow $flow): bool =>
                        $flow->started_at !== null
                        && $flow->completed_at !== null
                    )
                    ->map(fn (SigningStationFlow $flow): int =>
                        (int) $flow->started_at->diffInSeconds(
                            $flow->completed_at
                        )
                    );

                return [
                    'id' => $first?->signing_station_id,
                    'name' => $first?->signingStation?->name ?? 'Unknown station',
                    'template' => $first?->signingStation?->consentTemplate?->title
                        ?? 'No template',
                    'total' => $stationFlows->count(),
                    'completed' => $completed->count(),
                    'abandoned' => $abandoned,
                    'cancelled' => $cancelled,
                    'completion_rate' => $terminal > 0
                        ? round(($completed->count() / $terminal) * 100, 1)
                        : 0.0,
                    'average_completion_seconds' => $durations->isNotEmpty()
                        ? (int) round((float) $durations->average())
                        : null,
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    private function templatePerformance(Collection $flows): Collection
    {
        return $flows
            ->groupBy(fn (SigningStationFlow $flow): string =>
                (string) (
                    $flow->signingStation?->consent_template_id
                    ?? 'unknown'
                )
            )
            ->map(function (Collection $templateFlows): array {
                $first = $templateFlows->first();
                $completed = $templateFlows->where(
                    'status',
                    SigningStationFlow::STATUS_COMPLETED
                )->count();
                $abandoned = $templateFlows->where(
                    'status',
                    SigningStationFlow::STATUS_ABANDONED
                )->count();
                $cancelled = $templateFlows->where(
                    'status',
                    SigningStationFlow::STATUS_CANCELLED
                )->count();
                $terminal = $completed + $abandoned + $cancelled;

                return [
                    'title' => $first?->signingStation?->consentTemplate?->title
                        ?? 'Unknown template',
                    'total' => $templateFlows->count(),
                    'completed' => $completed,
                    'abandoned' => $abandoned,
                    'completion_rate' => $terminal > 0
                        ? round(($completed / $terminal) * 100, 1)
                        : 0.0,
                ];
            })
            ->sortByDesc('total')
            ->take(10)
            ->values();
    }

    private function organizationId(Request $request): int
    {
        $organizationId = (int) ($request->user()?->organization_id ?? 0);

        abort_if(
            $organizationId < 1,
            403,
            'An organization account is required to view kiosk analytics.'
        );

        return $organizationId;
    }

    private function ensureFeatureReady(): void
    {
        abort_unless(
            Schema::hasTable('signing_station_flows'),
            503,
            'Kiosk analytics is not ready. Run php artisan migrate.'
        );
    }

    private function csvValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        if (preg_match('/^[=+\-@]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
