<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    Platform Dashboard
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Platform administration and organization overview.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                @if (\Illuminate\Support\Facades\Route::has(
                    'platform.organizations.index'
                ))
                    <a
                        href="{{ route('platform.organizations.index') }}"
                        class="inline-flex items-center rounded-lg border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800"
                    >
                        Manage Organizations
                    </a>
                @endif

                @if (\Illuminate\Support\Facades\Route::has(
                    'platform.activity-logs.index'
                ))
                    <a
                        href="{{ route('platform.activity-logs.index') }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                    >
                        Activity Logs
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    @php
        /*
         * The original controller may pass statistics using different
         * variable names. These fallbacks keep the dashboard functional.
         */
        $dashboardStatistics = [];

        if (isset($statistics)) {
            if ($statistics instanceof \Illuminate\Support\Collection) {
                $dashboardStatistics = $statistics->all();
            } elseif (is_array($statistics)) {
                $dashboardStatistics = $statistics;
            }
        }

        $readStatistic = function (
            array $keys,
            int $fallback = 0
        ) use ($dashboardStatistics): int {
            foreach ($keys as $key) {
                $value = data_get($dashboardStatistics, $key);

                if (is_numeric($value)) {
                    return (int) $value;
                }
            }

            return $fallback;
        };

        $databaseOrganizations = 0;
        $databaseUsers = 0;
        $databaseActivity = 0;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable(
                'organizations'
            )) {
                $databaseOrganizations =
                    \Illuminate\Support\Facades\DB::table(
                        'organizations'
                    )->count();
            }

            if (\Illuminate\Support\Facades\Schema::hasTable(
                'users'
            )) {
                $databaseUsers =
                    \Illuminate\Support\Facades\DB::table(
                        'users'
                    )->count();
            }

            foreach ([
                'activity_logs',
                'platform_activity_logs',
            ] as $activityTable) {
                if (\Illuminate\Support\Facades\Schema::hasTable(
                    $activityTable
                )) {
                    $databaseActivity =
                        \Illuminate\Support\Facades\DB::table(
                            $activityTable
                        )->count();

                    break;
                }
            }
        } catch (\Throwable $exception) {
            /*
             * Keep the admin page available even while the database
             * is temporarily unavailable.
             */
        }

        $organizationCount = $readStatistic(
            [
                'organizations',
                'organization_count',
                'total_organizations',
                'organizations.total',
            ],
            $databaseOrganizations
        );

        $userCount = $readStatistic(
            [
                'users',
                'user_count',
                'total_users',
                'users.total',
            ],
            $databaseUsers
        );

        $activityCount = $readStatistic(
            [
                'activity',
                'activity_count',
                'total_activity',
                'activity.total',
            ],
            $databaseActivity
        );
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <p class="text-sm text-gray-500">
                    Signed in as
                </p>

                <p class="mt-1 text-lg font-semibold text-gray-900">
                    {{ auth()->user()?->name ?? 'Platform administrator' }}
                </p>

                <p class="mt-2 text-sm text-gray-600">
                    Use this dashboard to manage organizations, users,
                    and platform activity.
                </p>
            </section>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Organizations
                    </p>

                    <p class="mt-3 text-3xl font-bold text-gray-900">
                        {{ number_format($organizationCount) }}
                    </p>

                    <p class="mt-2 text-sm text-gray-500">
                        Organizations registered on the platform.
                    </p>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Users
                    </p>

                    <p class="mt-3 text-3xl font-bold text-teal-700">
                        {{ number_format($userCount) }}
                    </p>

                    <p class="mt-2 text-sm text-gray-500">
                        Platform and organization user accounts.
                    </p>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Activity Events
                    </p>

                    <p class="mt-3 text-3xl font-bold text-amber-600">
                        {{ number_format($activityCount) }}
                    </p>

                    <p class="mt-2 text-sm text-gray-500">
                        Recorded administrative and organization events.
                    </p>
                </section>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                @if (\Illuminate\Support\Facades\Route::has(
                    'platform.organizations.index'
                ))
                    <a
                        href="{{ route('platform.organizations.index') }}"
                        class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-teal-600 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">
                                    Organization Management
                                </h2>

                                <p class="mt-2 text-sm text-gray-600">
                                    Review organizations and manage their
                                    accounts and users.
                                </p>
                            </div>

                            <span class="text-xl text-teal-700">
                                →
                            </span>
                        </div>
                    </a>
                @endif

                @if (\Illuminate\Support\Facades\Route::has(
                    'platform.activity-logs.index'
                ))
                    <a
                        href="{{ route('platform.activity-logs.index') }}"
                        class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-teal-600 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">
                                    Platform Activity
                                </h2>

                                <p class="mt-2 text-sm text-gray-600">
                                    Review administrative actions and
                                    organization audit history.
                                </p>
                            </div>

                            <span class="text-xl text-teal-700">
                                →
                            </span>
                        </div>
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
