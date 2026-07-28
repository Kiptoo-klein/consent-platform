<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                    Kiosk Analytics
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Measure completions, inactivity timeouts, manual cancellations, and performance by station.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('signing-stations.index') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                >
                    Signing Stations
                </a>

                <a
                    href="{{ route('signing-stations.analytics.export', [
                        'from' => $filters['from']->toDateString(),
                        'to' => $filters['to']->toDateString(),
                        'station_id' => $filters['station_id'],
                    ]) }}"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500"
                >
                    Export CSV
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $formatDuration = function (?int $seconds): string {
            if ($seconds === null) {
                return 'Not available';
            }

            $minutes = intdiv($seconds, 60);
            $remainingSeconds = $seconds % 60;

            if ($minutes < 1) {
                return $remainingSeconds.' sec';
            }

            return $minutes.' min '.$remainingSeconds.' sec';
        };

        $stageLabel = fn (?string $stage): string => match ($stage) {
            'review' => 'Consent review',
            'details' => 'Signer details',
            'signing' => 'Signature',
            'completed' => 'Completed',
            default => 'Unknown stage',
        };
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <form method="GET" action="{{ route('signing-stations.analytics') }}" class="grid gap-4 lg:grid-cols-4">
                    <div>
                        <label for="from" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            From date
                        </label>
                        <input
                            id="from"
                            name="from"
                            type="date"
                            value="{{ $filters['from']->toDateString() }}"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        >
                    </div>

                    <div>
                        <label for="to" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            To date
                        </label>
                        <input
                            id="to"
                            name="to"
                            type="date"
                            value="{{ $filters['to']->toDateString() }}"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        >
                    </div>

                    <div>
                        <label for="station_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Signing station
                        </label>
                        <select
                            id="station_id"
                            name="station_id"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                        >
                            <option value="">All signing stations</option>
                            @foreach ($stations as $station)
                                <option
                                    value="{{ $station->id }}"
                                    @selected((string) $filters['station_id'] === (string) $station->id)
                                >
                                    {{ $station->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button
                            type="submit"
                            class="inline-flex flex-1 justify-center rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white"
                        >
                            Apply filters
                        </button>

                        <a
                            href="{{ route('signing-stations.analytics') }}"
                            class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                        >
                            Reset
                        </a>
                    </div>
                </form>

                @if ($errors->any())
                    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500">Total kiosk flows</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($metrics['total']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Review attempts in the selected period</p>
                </div>

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm dark:border-emerald-900 dark:bg-emerald-950/30">
                    <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Completed</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-900 dark:text-emerald-100">{{ number_format($metrics['completed']) }}</p>
                    <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">{{ number_format($metrics['completion_rate'], 1) }}% completion rate</p>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm dark:border-amber-900 dark:bg-amber-950/30">
                    <p class="text-sm font-medium text-amber-700 dark:text-amber-300">Abandoned by timeout</p>
                    <p class="mt-2 text-3xl font-bold text-amber-900 dark:text-amber-100">{{ number_format($metrics['abandoned']) }}</p>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">{{ number_format($metrics['abandonment_rate'], 1) }}% abandonment rate</p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500">Manually cancelled</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($metrics['cancelled']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Explicit cancellation rather than inactivity</p>
                </div>

                <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-sm dark:border-blue-900 dark:bg-blue-950/30">
                    <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Currently active</p>
                    <p class="mt-2 text-3xl font-bold text-blue-900 dark:text-blue-100">{{ number_format($metrics['active']) }}</p>
                    <p class="mt-1 text-xs text-blue-700 dark:text-blue-300">Reviewing, entering details, or signing</p>
                </div>

                <div class="rounded-xl border border-rose-200 bg-rose-50 p-5 shadow-sm dark:border-rose-900 dark:bg-rose-950/30">
                    <p class="text-sm font-medium text-rose-700 dark:text-rose-300">Potential stale flows</p>
                    <p class="mt-2 text-3xl font-bold text-rose-900 dark:text-rose-100">{{ number_format($metrics['stale_active']) }}</p>
                    <p class="mt-1 text-xs text-rose-700 dark:text-rose-300">No server activity for over 10 minutes</p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:col-span-2">
                    <p class="text-sm font-medium text-gray-500">Average completion time</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $formatDuration($metrics['average_completion_seconds']) }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">From opening the review page to successful submission</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 lg:col-span-2">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daily activity</h3>
                            <p class="text-sm text-gray-500">Completed, abandoned, and manually cancelled flows by start date.</p>
                        </div>
                        <div class="flex flex-wrap gap-3 text-xs font-medium text-gray-600 dark:text-gray-300">
                            <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Completed</span>
                            <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-amber-500"></span>Abandoned</span>
                            <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-gray-400"></span>Cancelled</span>
                        </div>
                    </div>

                    <div class="mt-6 max-h-[34rem] space-y-3 overflow-y-auto pr-2">
                        @foreach (array_reverse($dailyTrends) as $day)
                            <div class="grid grid-cols-[4.5rem_1fr_3rem] items-center gap-3 text-sm">
                                <span class="text-gray-500">{{ $day['label'] }}</span>
                                <div class="space-y-1">
                                    <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $day['completed_width'] }}%"></div>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                        <div class="h-full rounded-full bg-amber-500" style="width: {{ $day['abandoned_width'] }}%"></div>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                        <div class="h-full rounded-full bg-gray-400" style="width: {{ $day['cancelled_width'] }}%"></div>
                                    </div>
                                </div>
                                <span class="text-right font-semibold text-gray-700 dark:text-gray-200">{{ $day['total'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Abandonment stage</h3>
                    <p class="mt-1 text-sm text-gray-500">Where inactivity timeouts happened.</p>

                    <div class="mt-6 space-y-4">
                        @foreach ([
                            ['key' => 'review', 'label' => 'Consent review'],
                            ['key' => 'details', 'label' => 'Signer details'],
                            ['key' => 'signing', 'label' => 'Signature'],
                        ] as $stage)
                            @php
                                $stageCount = $abandonmentStages[$stage['key']] ?? 0;
                                $stagePercent = $metrics['abandoned'] > 0
                                    ? round(($stageCount / $metrics['abandoned']) * 100, 1)
                                    : 0;
                            @endphp
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ $stage['label'] }}</span>
                                    <span class="text-gray-500">{{ $stageCount }} · {{ $stagePercent }}%</span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div class="h-full rounded-full bg-amber-500" style="width: {{ $stagePercent }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-200">
                        A timeout is counted as abandoned only when the kiosk sends its two-minute inactivity cancellation. Older cancellations remain classified as historical/manual cancellations.
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Station performance</h3>
                    <p class="mt-1 text-sm text-gray-500">Compare completion and abandonment across kiosks.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-950/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Station</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Template</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Total</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Completed</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Abandoned</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Rate</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Avg. time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($stationPerformance as $row)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                        <a
                                            href="{{ route('signing-stations.show', $row['id']) }}"
                                            class="hover:text-indigo-600"
                                        >
                                            {{ $row['name'] }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $row['template'] }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-700 dark:text-gray-200">{{ $row['total'] }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-emerald-700 dark:text-emerald-300">{{ $row['completed'] }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-amber-700 dark:text-amber-300">{{ $row['abandoned'] }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($row['completion_rate'], 1) }}%</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-600 dark:text-gray-300">{{ $formatDuration($row['average_completion_seconds']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">
                                        No kiosk activity exists for this date range.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Most-used templates</h3>
                        <p class="mt-1 text-sm text-gray-500">Kiosk activity grouped by consent template.</p>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($templatePerformance as $row)
                            <div class="flex items-center justify-between gap-4 px-6 py-4">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $row['title'] }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $row['completed'] }} completed · {{ $row['abandoned'] }} abandoned</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $row['total'] }}</p>
                                    <p class="text-xs text-gray-500">{{ number_format($row['completion_rate'], 1) }}%</p>
                                </div>
                            </div>
                        @empty
                            <p class="px-6 py-10 text-center text-sm text-gray-500">No template activity exists for this period.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent abandoned flows</h3>
                        <p class="mt-1 text-sm text-gray-500">Latest inactivity timeouts within the selected period.</p>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($recentAbandoned as $flow)
                            <div class="px-6 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $flow->signingStation?->name ?? 'Unknown station' }}
                                        </p>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                            {{ $stageLabel($flow->current_stage) }}
                                            @if ($flow->consentSession?->signer_name)
                                                · {{ $flow->consentSession->signer_name }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="whitespace-nowrap text-xs text-gray-500">
                                        {{ $flow->abandoned_at?->diffForHumans() ?? 'Unknown time' }}
                                    </span>
                                </div>

                                @if ($flow->consentSession)
                                    <a
                                        href="{{ route('consent-sessions.show', $flow->consentSession) }}"
                                        class="mt-2 inline-flex text-xs font-semibold text-indigo-600 hover:text-indigo-500"
                                    >
                                        Open consent record
                                    </a>
                                @endif
                            </div>
                        @empty
                            <p class="px-6 py-10 text-center text-sm text-gray-500">No timed-out kiosk flows were recorded for this period.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
