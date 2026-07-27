<x-app-layout>
            <x-slot name="header">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                            Security Status
                        </h2>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Application protections and production-readiness checks
                        </p>
                    </div>

                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        Back to Dashboard
                    </a>
                </div>
            </x-slot>

            @php
                $statusClasses = [
                    'pass' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300',
                    'warning' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
                    'fail' => 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300',
                ];

                $statusLabels = [
                    'pass' => 'Protected',
                    'warning' => 'Review',
                    'fail' => 'Action required',
                ];
            @endphp

            <div class="py-8">
                <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm dark:border-emerald-900 dark:bg-gray-900">
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                                Protected
                            </p>

                            <p class="mt-2 text-4xl font-bold text-emerald-600 dark:text-emerald-400">
                                {{ number_format($summary['pass']) }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-amber-200 bg-white p-6 shadow-sm dark:border-amber-900 dark:bg-gray-900">
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                                Needs review
                            </p>

                            <p class="mt-2 text-4xl font-bold text-amber-600 dark:text-amber-400">
                                {{ number_format($summary['warning']) }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-red-200 bg-white p-6 shadow-sm dark:border-red-900 dark:bg-gray-900">
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                                Action required
                            </p>

                            <p class="mt-2 text-4xl font-bold text-red-600 dark:text-red-400">
                                {{ number_format($summary['fail']) }}
                            </p>
                        </div>
                    </div>

                    @if (! $isProduction)
                        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 text-blue-900 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-200">
                            <p class="font-semibold">
                                Development environment
                            </p>

                            <p class="mt-1 text-sm leading-6">
                                HTTPS, secure cookies, trusted hosts and an enforced Content Security Policy should be enabled when the live domain and TLS certificate are ready.
                            </p>
                        </div>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        @foreach ([
                            'Queued jobs' => $runtime['queued_jobs'],
                            'Failed jobs' => $runtime['failed_jobs'],
                            'Stale kiosk sessions' => $runtime['stale_kiosk_sessions'],
                            'Invalid station tokens' => $runtime['invalid_station_tokens'],
                            'Invalid consent tokens' => $runtime['invalid_consent_tokens'],
                        ] as $label => $value)
                            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ $label }}
                                </p>

                                <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">
                                    {{ $value === null ? '—' : number_format($value) }}
                                </p>
                            </div>
                        @endforeach
                    </div>

                    @foreach ($checks->groupBy('category') as $category => $categoryChecks)
                        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800/60">
                                <h3 class="font-bold text-gray-900 dark:text-white">
                                    {{ $category }}
                                </h3>
                            </div>

                            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($categoryChecks as $check)
                                    <div class="grid gap-3 px-6 py-5 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                {{ $check['label'] }}
                                            </p>

                                            <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">
                                                {{ $check['details'] }}
                                            </p>
                                        </div>

                                        <span
                                            class="inline-flex w-fit rounded-full border px-3 py-1 text-xs font-bold {{ $statusClasses[$check['status']] }}"
                                        >
                                            {{ $statusLabels[$check['status']] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach

                    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800/60">
                            <h3 class="font-bold text-gray-900 dark:text-white">
                                Latest failed background jobs
                            </h3>
                        </div>

                        @if ($latestFailedJobs->isEmpty())
                            <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No failed jobs were found, or failed-job storage is not configured.
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                                                Failed
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                                                Queue
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                                                Error
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($latestFailedJobs as $job)
                                            <tr>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                                    {{ $job->failed_at }}
                                                </td>

                                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                                    {{ $job->connection }}/{{ $job->queue }}
                                                </td>

                                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                                    {{ $job->exception ?: 'No error summary available.' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-gray-950 p-6 text-gray-100 shadow-sm">
                        <h3 class="font-bold">
                            Recommended production environment
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-gray-400">
                            Apply these settings only after the domain, HTTPS certificate, database queue and cache are configured.
                        </p>

                        <pre class="mt-5 overflow-x-auto rounded-xl bg-black/40 p-5 text-sm leading-7 text-emerald-300"><code>APP_ENV=production
APP_DEBUG=false
APP_URL=https://consent.example.com

SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

SECURITY_FORCE_HTTPS=true
SECURITY_ALLOWED_HOSTS=consent.example.com
SECURITY_CSP_MODE=enforce

QUEUE_CONNECTION=database
CACHE_STORE=database</code></pre>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <h3 class="font-bold text-gray-900 dark:text-white">
                            Cleanup test
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                            Preview cleanup without modifying records:
                        </p>

                        <pre class="mt-4 overflow-x-auto rounded-xl bg-gray-950 p-4 text-sm text-emerald-300"><code>php artisan security:cleanup --dry-run</code></pre>
                    </section>
                </div>
            </div>
        </x-app-layout>
