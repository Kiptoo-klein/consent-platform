<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                    Signing Stations
                </h2>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage shared-device kiosks for collecting consent.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a
                    href="{{ route('signing-stations.create') }}"
                    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                >
                    Create Signing Station
                </a>

                @if (\Illuminate\Support\Facades\Route::has(
                    'signing-stations.analytics'
                ))
                    <a
                        href="{{ route('signing-stations.analytics') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-teal-700 bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-800"
                    >
                        Kiosk Analytics
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-800 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            @if ($signingStations->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300">
                        <svg
                            class="h-8 w-8"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M9.75 3.75h4.5m-6 16.5h7.5A2.25 2.25 0 0018 18V6a2.25 2.25 0 00-2.25-2.25h-7.5A2.25 2.25 0 006 6v12a2.25 2.25 0 002.25 2.25z"
                            />
                        </svg>
                    </div>

                    <h3 class="mt-5 text-lg font-semibold text-gray-900 dark:text-white">
                        No signing stations yet
                    </h3>

                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500 dark:text-gray-400">
                        Create a signing station to collect consent using a reception computer, tablet or another shared device.
                    </p>

                    <a
                        href="{{ route('signing-stations.create') }}"
                        class="mt-6 inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                    >
                        Create Your First Station
                    </a>
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($signingStations as $signingStation)
                        <article class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:bg-gray-900">
                            <div class="p-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <h3 class="truncate text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ $signingStation->name }}
                                        </h3>

                                        <p class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400">
                                            {{ $signingStation->consentTemplate?->title ?? 'Consent template unavailable' }}
                                        </p>
                                    </div>

                                    @if ($signingStation->active)
                                        <span class="inline-flex shrink-0 items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex shrink-0 items-center rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-semibold text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300">
                                            Paused
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-6 grid grid-cols-2 gap-3">
                                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Total records
                                        </p>

                                        <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                                            {{ $signingStation->consent_sessions_count ?? 0 }}
                                        </p>
                                    </div>

                                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Completed
                                        </p>

                                        <p class="mt-2 text-2xl font-bold text-green-600 dark:text-green-400">
                                            {{ $signingStation->completed_sessions_count ?? 0 }}
                                        </p>
                                    </div>
                                </div>

                                <dl class="mt-5 space-y-3 text-sm">
                                    <div class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">
                                            Email
                                        </dt>

                                        <dd class="font-medium text-gray-800 dark:text-gray-200">
                                            {{ $signingStation->require_email ? 'Required' : 'Optional' }}
                                        </dd>
                                    </div>

                                    <div class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">
                                            Reference
                                        </dt>

                                        <dd class="font-medium text-gray-800 dark:text-gray-200">
                                            {{ $signingStation->require_reference ? 'Required' : 'Optional' }}
                                        </dd>
                                    </div>

                                    <div class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500 dark:text-gray-400">
                                            Auto reset
                                        </dt>

                                        <dd class="font-medium text-gray-800 dark:text-gray-200">
                                            {{ $signingStation->auto_reset_seconds ?? 10 }} seconds
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="flex flex-wrap gap-2 border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800/50">
                                <a
                                    href="{{ route(
                                        'signing-stations.show',
                                        $signingStation
                                    ) }}"
                                    class="inline-flex flex-1 items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                                >
                                    Manage
                                </a>

                                @if ($signingStation->active)
                                    <a
                                        href="{{ route(
                                            'public-signing-stations.show',
                                            $signingStation->station_token
                                        ) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex flex-1 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                    >
                                        Launch
                                    </a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($signingStations->hasPages())
                    <div class="mt-8">
                        {{ $signingStations->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
