<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    Platform Activity
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Review platform and organization audit events.
                </p>
            </div>

            <a
                href="{{ route('platform.dashboard') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                Platform Dashboard
            </a>
        </div>
    </x-slot>

    @php
        $activityStatistics = $statistics ?? [];

        $totalActivity = (int) data_get(
            $activityStatistics,
            'total',
            isset($logs) && method_exists($logs, 'total')
                ? $logs->total()
                : 0
        );

        $todayActivity = (int) data_get(
            $activityStatistics,
            'today',
            0
        );

        $weekActivity = (int) data_get(
            $activityStatistics,
            'this_week',
            0
        );

        $organizationCount = (int) data_get(
            $activityStatistics,
            'organizations',
            0
        );

        $organizationOptions = $organizations ?? collect();
        $actionOptions = $actions ?? collect();
        $activityLogs = $logs ?? collect();
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Total Activity
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ number_format($totalActivity) }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500">
                        All recorded audit events
                    </p>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Today
                    </p>

                    <p class="mt-2 text-3xl font-bold text-teal-700">
                        {{ number_format($todayActivity) }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500">
                        Events recorded today
                    </p>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        This Week
                    </p>

                    <p class="mt-2 text-3xl font-bold text-green-700">
                        {{ number_format($weekActivity) }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500">
                        Events recorded this week
                    </p>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Organizations
                    </p>

                    <p class="mt-2 text-3xl font-bold text-amber-600">
                        {{ number_format($organizationCount) }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500">
                        Organizations represented
                    </p>
                </section>
            </div>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <form
                    method="GET"
                    action="{{ route('platform.activity-logs.index') }}"
                    class="grid gap-4 md:grid-cols-4"
                >
                    <div>
                        <label
                            for="search"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            Search
                        </label>

                        <input
                            id="search"
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Action or description"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                        >
                    </div>

                    <div>
                        <label
                            for="organization"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            Organization
                        </label>

                        <select
                            id="organization"
                            name="organization"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                        >
                            <option value="">
                                All organizations
                            </option>

                            @foreach ($organizationOptions as $organization)
                                <option
                                    value="{{ $organization->id }}"
                                    @selected(
                                        (string) request('organization')
                                        === (string) $organization->id
                                    )
                                >
                                    {{ $organization->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            for="action"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            Action
                        </label>

                        <select
                            id="action"
                            name="action"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                        >
                            <option value="">
                                All actions
                            </option>

                            @foreach ($actionOptions as $action)
                                <option
                                    value="{{ $action }}"
                                    @selected(
                                        request('action') === $action
                                    )
                                >
                                    {{ str($action)
                                        ->replace(['.', '_'], ' ')
                                        ->title() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg border border-teal-700 bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800"
                        >
                            Filter
                        </button>

                        <a
                            href="{{ route('platform.activity-logs.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                        >
                            Reset
                        </a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Date
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Actor
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Organization
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Action
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Description
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    IP Address
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Details
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($activityLogs as $log)
                                @php
                                    $action = (string) (
                                        $log->action
                                        ?? 'activity'
                                    );

                                    $badgeClasses = match ($action) {
                                        'user.created' =>
                                            'border-blue-200 bg-blue-100 text-blue-800',

                                        'user.updated' =>
                                            'border-amber-200 bg-amber-100 text-amber-800',

                                        'user.enabled' =>
                                            'border-green-200 bg-green-100 text-green-800',

                                        'user.disabled' =>
                                            'border-orange-200 bg-orange-100 text-orange-800',

                                        'user.archived' =>
                                            'border-red-200 bg-red-100 text-red-800',

                                        'user.restored' =>
                                            'border-purple-200 bg-purple-100 text-purple-800',

                                        default =>
                                            'border-gray-200 bg-gray-100 text-gray-800',
                                    };

                                    $actionLabel = str($action)
                                        ->replace(['.', '_'], ' ')
                                        ->title();
                                @endphp

                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                                        <div>
                                            {{ $log->created_at
                                                ? $log->created_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y')
                                                : '—' }}
                                        </div>

                                        <div class="mt-1 text-xs text-gray-400">
                                            {{ $log->created_at
                                                ? $log->created_at->copy()->timezone(config('app.display_timezone'))->format('H:i:s')
                                                : '' }}
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4">
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ $log->user?->name ?? 'System' }}
                                        </div>

                                        <div class="mt-1 text-xs text-gray-500">
                                            {{ $log->user?->email
                                                ?? 'Automated action' }}
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                                        {{ $log->organization?->name
                                            ?? 'Platform' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $badgeClasses }}">
                                            {{ $actionLabel }}
                                        </span>
                                    </td>

                                    <td class="max-w-md px-5 py-4 text-sm text-gray-700">
                                        {{ $log->description
                                            ?: 'No description recorded.' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                                        {{ $log->ip_address ?? '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <a
                                            href="{{ route(
                                                'platform.activity-logs.show',
                                                $log
                                            ) }}"
                                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:border-teal-700 hover:text-teal-800"
                                        >
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="7"
                                        class="px-6 py-14 text-center"
                                    >
                                        <p class="font-semibold text-gray-800">
                                            No activity logs found
                                        </p>

                                        <p class="mt-2 text-sm text-gray-500">
                                            Try changing or clearing the filters.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (
                    is_object($activityLogs)
                    && method_exists($activityLogs, 'hasPages')
                    && $activityLogs->hasPages()
                )
                    <div class="border-t border-gray-200 px-6 py-4">
                        {{ $activityLogs->withQueryString()->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
