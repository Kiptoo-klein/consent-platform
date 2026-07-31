<x-app-layout>
    @php
        $platformUser =
            auth()->user();

        $platformUser?->loadMissing(
            'platformRole'
        );

        $platformRoleSlug =
            $platformUser
                ?->platformRole
                ?->slug;

        $isSuperAdmin =
            $platformRoleSlug
            === 'super-admin';

        $canViewBilling =
            in_array(
                $platformRoleSlug,
                [
                    'super-admin',
                    'billing',
                    'platform-auditor',
                ],
                true
            );

        $canViewOrganizationUsers =
            in_array(
                $platformRoleSlug,
                [
                    'super-admin',
                    'support',
                    'platform-auditor',
                ],
                true
            );

        $canViewActivity =
            in_array(
                $platformRoleSlug,
                [
                    'super-admin',
                    'platform-auditor',
                ],
                true
            );

        $canViewSecurity =
            in_array(
                $platformRoleSlug,
                [
                    'super-admin',
                    'platform-auditor',
                ],
                true
            );

        $organizationActionLabel =
            $isSuperAdmin
                ? 'Manage Organizations'
                : 'View Organizations';

        $organizationActionDescription =
            match ($platformRoleSlug) {
                'super-admin' =>
                    'Review organizations and manage their '
                    .'accounts and users.',

                'support' =>
                    'Review organizations and organization-user '
                    .'records for support.',

                'platform-auditor' =>
                    'Review organization and user records in '
                    .'read-only mode.',

                default =>
                    'Review organization accounts and subscription '
                    .'information.',
            };

        $dashboardDescription =
            match ($platformRoleSlug) {
                'super-admin' =>
                    'Manage organizations, billing, staff, security, '
                    .'and platform activity.',

                'billing' =>
                    'Manage subscription plans, invoices, payments, '
                    .'and billing records.',

                'support' =>
                    'Review organizations and organization-user '
                    .'records required for support.',

                'platform-auditor' =>
                    'Review organizations, billing records, activity '
                    .'logs, and security status.',

                default =>
                    'Review the Platform information available to '
                    .'your assigned role.',
            };
    @endphp

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

            <div
                class="flex flex-wrap gap-3"
                data-dashboard-actions
            >
                <a
                    href="{{ route(
                        'platform.organizations.index'
                    ) }}"
                    data-dashboard-action="organizations"
                    class="inline-flex items-center rounded-lg border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800"
                >
                    {{ $organizationActionLabel }}
                </a>

                @if ($canViewBilling)
                    <a
                        href="{{ route(
                            'platform.billing.index'
                        ) }}"
                        data-dashboard-action="billing"
                        class="inline-flex items-center rounded-lg border border-blue-700 bg-blue-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800"
                    >
                        Billing Management
                    </a>
                @endif

                @if ($canViewActivity)
                    <a
                        href="{{ route(
                            'platform.activity-logs.index'
                        ) }}"
                        data-dashboard-action="activity"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                    >
                        Activity Logs
                    </a>
                @endif

                @if ($canViewSecurity)
                    <a
                        href="{{ route(
                            'platform.security.status'
                        ) }}"
                        data-dashboard-action="security"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                    >
                        Security Status
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

            if (
                $canViewOrganizationUsers
                && \Illuminate\Support\Facades\Schema::hasTable(
                    'users'
                )
            ) {
                $databaseUsers =
                    \Illuminate\Support\Facades\DB::table(
                        'users'
                    )->count();
            }

            if ($canViewActivity) {
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
                    {{ $dashboardDescription }}
                </p>
            </section>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <section
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                    data-dashboard-section="organizations"
                >
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

                @if ($canViewOrganizationUsers)
                    <section
                        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                        data-dashboard-section="users"
                    >
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
                @endif

                @if ($canViewActivity)
                    <section
                        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                        data-dashboard-section="activity"
                    >
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
                @endif
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <a
                    href="{{ route(
                        'platform.organizations.index'
                    ) }}"
                    data-dashboard-action="organizations"
                    class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-teal-600 hover:shadow-md"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                {{ $organizationActionLabel }}
                            </h2>

                            <p class="mt-2 text-sm text-gray-600">
                                {{ $organizationActionDescription }}
                            </p>
                        </div>

                        <span class="text-xl text-teal-700">
                            →
                        </span>
                    </div>
                </a>

                @if ($canViewBilling)
                    <a
                        href="{{ route(
                            'platform.billing.index'
                        ) }}"
                        data-dashboard-action="billing"
                        class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-blue-600 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">
                                    Billing Management
                                </h2>

                                <p class="mt-2 text-sm text-gray-600">
                                    Review subscriptions, invoices, payment
                                    records, and plan requests.
                                </p>
                            </div>

                            <span class="text-xl text-blue-700">
                                →
                            </span>
                        </div>
                    </a>
                @endif

                @if ($canViewActivity)
                    <a
                        href="{{ route(
                            'platform.activity-logs.index'
                        ) }}"
                        data-dashboard-action="activity"
                        class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-amber-600 hover:shadow-md"
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

                            <span class="text-xl text-amber-700">
                                →
                            </span>
                        </div>
                    </a>
                @endif

                @if ($canViewSecurity)
                    <a
                        href="{{ route(
                            'platform.security.status'
                        ) }}"
                        data-dashboard-action="security"
                        class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:border-red-600 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">
                                    Security Status
                                </h2>

                                <p class="mt-2 text-sm text-gray-600">
                                    Review security controls and current
                                    Platform protection status.
                                </p>
                            </div>

                            <span class="text-xl text-red-700">
                                →
                            </span>
                        </div>
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
