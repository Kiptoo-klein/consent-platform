<x-app-layout>
            <x-slot name="header">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                            Production Readiness
                        </h2>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Deployment, backups, workers, scheduler and recovery status
                        </p>
                    </div>

                    <a
                        href="{{ route('platform.dashboard') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    >
                        Platform Dashboard
                    </a>
                </div>
            </x-slot>

            @php
                $statusClasses = [
                    'pass' =>
                        'border-emerald-200 bg-emerald-50 text-emerald-800',

                    'warning' =>
                        'border-amber-200 bg-amber-50 text-amber-800',

                    'fail' =>
                        'border-red-200 bg-red-50 text-red-800',

                    'deferred' =>
                        'border-blue-200 bg-blue-50 text-blue-800',

                    'info' =>
                        'border-gray-200 bg-gray-50 text-gray-700',
                ];

                $statusLabels = [
                    'pass' => 'Ready',
                    'warning' => 'Review',
                    'fail' => 'Action required',
                    'deferred' => 'Deferred',
                    'info' => 'Information',
                ];
            @endphp

            <div class="py-8">
                <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

                    @if (session('success'))
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-800">
                            {!! nl2br(e(session('success'))) !!}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-800">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm dark:bg-gray-900">
                            <p class="text-sm font-semibold text-gray-500">
                                Ready
                            </p>

                            <p class="mt-2 text-4xl font-bold text-emerald-600">
                                {{ number_format($summary['pass']) }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-amber-200 bg-white p-6 shadow-sm dark:bg-gray-900">
                            <p class="text-sm font-semibold text-gray-500">
                                Review
                            </p>

                            <p class="mt-2 text-4xl font-bold text-amber-600">
                                {{ number_format($summary['warning']) }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-red-200 bg-white p-6 shadow-sm dark:bg-gray-900">
                            <p class="text-sm font-semibold text-gray-500">
                                Required
                            </p>

                            <p class="mt-2 text-4xl font-bold text-red-600">
                                {{ number_format($summary['fail']) }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-blue-200 bg-white p-6 shadow-sm dark:bg-gray-900">
                            <p class="text-sm font-semibold text-gray-500">
                                Domain stage
                            </p>

                            <p class="mt-2 text-4xl font-bold text-blue-600">
                                {{ number_format($summary['deferred']) }}
                            </p>
                        </div>
                    </div>

                    <section class="rounded-2xl border border-blue-200 bg-blue-50 p-6 text-blue-900">
                        <h3 class="font-bold">
                            Domain-dependent work is intentionally deferred
                        </h3>

                        <p class="mt-2 text-sm leading-6">
                            The public domain, HTTPS certificate, forced HTTPS,
                            secure cookies, branded sender address, SPF, DKIM
                            and DMARC will be completed later. They do not prevent
                            local backup, queue, scheduler and deployment testing.
                        </p>
                    </section>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        @foreach ([
                            'Queued jobs' => $metrics['queued_jobs'],
                            'Failed jobs' => $metrics['failed_jobs'],
                            'Database size' => $metrics['database_size'] === null
                                ? null
                                : $formatter->formatBytes($metrics['database_size']),
                            'Application logs' => $formatter->formatBytes($metrics['log_size']),
                            'Free disk' => $metrics['free_disk'] === null
                                ? null
                                : $formatter->formatBytes($metrics['free_disk']),
                        ] as $label => $value)
                            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-500">
                                    {{ $label }}
                                </p>

                                <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">
                                    @if ($value === null)
                                        —
                                    @elseif (is_numeric($value))
                                        {{ number_format($value) }}
                                    @else
                                        {{ $value }}
                                    @endif
                                </p>
                            </div>
                        @endforeach
                    </div>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                Latest verified backup
                            </h3>

                            @if ($latestBackup)
                                <dl class="mt-5 space-y-4 text-sm">
                                    <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                                        <dt class="text-gray-500">
                                            Created
                                        </dt>

                                        <dd class="text-right font-semibold text-gray-900 dark:text-white">
                                            {{ $latestBackup['created_at'] }}
                                        </dd>
                                    </div>

                                    <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                                        <dt class="text-gray-500">
                                            Database
                                        </dt>

                                        <dd class="font-semibold text-gray-900 dark:text-white">
                                            {{ $latestBackup['driver'] }}
                                        </dd>
                                    </div>

                                    <div class="flex justify-between gap-4 border-b border-gray-100 pb-3">
                                        <dt class="text-gray-500">
                                            Total size
                                        </dt>

                                        <dd class="font-semibold text-gray-900 dark:text-white">
                                            {{ $formatter->formatBytes($latestBackup['total_size']) }}
                                        </dd>
                                    </div>

                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500">
                                            Integrity
                                        </dt>

                                        <dd class="font-semibold {{ $latestBackup['verified'] ? 'text-emerald-600' : 'text-red-600' }}">
                                            {{ $latestBackup['verified'] ? 'Verified' : 'Failed' }}
                                        </dd>
                                    </div>
                                </dl>
                            @else
                                <p class="mt-4 text-sm leading-6 text-gray-500">
                                    No backup has been created yet.
                                </p>
                            @endif

                            <form
                                method="POST"
                                action="{{ route('platform.production-readiness.backup') }}"
                                class="mt-6"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="inline-flex rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500"
                                >
                                    Create backup now
                                </button>
                            </form>
                        </section>

                        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                Operational actions
                            </h3>

                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <form
                                    method="POST"
                                    action="{{ route('platform.production-readiness.prune') }}"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                                    >
                                        Run retention cleanup
                                    </button>
                                </form>

                                <form
                                    method="POST"
                                    action="{{ route('platform.production-readiness.optimize') }}"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                                    >
                                        Build production caches
                                    </button>
                                </form>

                                <form
                                    method="POST"
                                    action="{{ route('platform.production-readiness.queue-restart') }}"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                                    >
                                        Restart queue workers
                                    </button>
                                </form>

                                @if ($maintenanceMode)
                                    <form
                                        method="POST"
                                        action="{{ route('platform.production-readiness.maintenance.disable') }}"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="w-full rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-500"
                                        >
                                            Disable maintenance
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </section>
                    </div>

                    @foreach ($checks->groupBy('category') as $category => $categoryChecks)
                        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                            <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
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

                                        <span class="inline-flex w-fit rounded-full border px-3 py-1 text-xs font-bold {{ $statusClasses[$check['status']] }}">
                                            {{ $statusLabels[$check['status']] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach

                    <section class="rounded-2xl border border-red-200 bg-red-50 p-6 text-red-900">
                        <h3 class="font-bold">
                            Maintenance mode
                        </h3>

                        <p class="mt-2 text-sm leading-6">
                            Enabling maintenance mode blocks normal visitors.
                            You will receive a temporary administrator bypass URL.
                        </p>

                        @unless ($maintenanceMode)
                            <form
                                method="POST"
                                action="{{ route('platform.production-readiness.maintenance.enable') }}"
                                class="mt-5 max-w-xl"
                            >
                                @csrf

                                <label
                                    for="confirmation"
                                    class="block text-sm font-semibold"
                                >
                                    Type MAINTENANCE to confirm
                                </label>

                                <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                                    <input
                                        id="confirmation"
                                        name="confirmation"
                                        type="text"
                                        autocomplete="off"
                                        required
                                        class="block w-full rounded-lg border-red-300"
                                    >

                                    <button
                                        type="submit"
                                        class="shrink-0 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-500"
                                    >
                                        Enable maintenance
                                    </button>
                                </div>
                            </form>
                        @endunless
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-gray-950 p-6 text-gray-100">
                        <h3 class="font-bold">
                            Prepared deployment resources
                        </h3>

                        <pre class="mt-4 overflow-x-auto rounded-xl bg-black/40 p-5 text-sm leading-7 text-emerald-300"><code>deploy/production-deploy.sh
deploy/supervisor-consent-platform.conf.example
deploy/nginx-consent-platform.conf.example
deploy/cron-consent-platform.txt
deploy/.env.production.example
deploy/RESTORE-DATABASE.md
deploy/ROLLBACK.md
deploy/PRODUCTION-LAUNCH-CHECKLIST.md</code></pre>
                    </section>
                </div>
            </div>
        </x-app-layout>
