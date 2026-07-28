<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Subscription Status
                </h1>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Review your organization's plan, payment status and access.
                </p>
            </div>

            <a
                href="{{ route('profile.edit') }}"
                class="text-sm font-semibold text-gray-600 transition hover:text-emerald-700 dark:text-gray-300"
            >
                Manage profile
            </a>
        </div>
    </x-slot>

    @php
        $plan = $subscription?->plan;

        $paymentStatus = $subscription?->payment_status?->value;
        $lifecycleStatus = $subscription?->status?->value;

        $hasAccess =
            $subscription?->allowsOrganizationAccess() ?? false;

        $hasBypass =
            $subscription?->hasPlatformBypass() ?? false;

        $formatStatus = static fn (?string $status): string =>
            $status
                ? ucwords(str_replace('_', ' ', $status))
                : 'Unavailable';
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            <section
                class="overflow-hidden rounded-3xl border bg-white shadow-sm dark:bg-gray-900
                    {{ $hasAccess
                        ? 'border-emerald-200 dark:border-emerald-900'
                        : 'border-amber-300 dark:border-amber-800' }}"
            >
                <div class="p-6 sm:p-8">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                Organization
                            </p>

                            <h2 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $organization->name }}
                            </h2>

                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                Current plan:
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    {{ $plan?->name ?? 'Unavailable' }}
                                </span>
                            </p>
                        </div>

                        <div
                            class="inline-flex w-fit items-center rounded-full px-4 py-2 text-sm font-bold
                                {{ $hasAccess
                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200'
                                    : 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200' }}"
                        >
                            @if ($hasAccess)
                                Access approved
                            @else
                                Payment required
                            @endif
                        </div>
                    </div>

                    @if (! $subscription)
                        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-5 text-red-900">
                            Subscription information is unavailable. Contact the Platform Administrator.
                        </div>
                    @else
                        <div class="mt-8 grid gap-4 sm:grid-cols-3">
                            <div class="rounded-2xl bg-gray-50 p-5 dark:bg-gray-800">
                                <p class="text-sm font-medium text-gray-500">
                                    Payment status
                                </p>

                                <p class="mt-2 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ $formatStatus($paymentStatus) }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-gray-50 p-5 dark:bg-gray-800">
                                <p class="text-sm font-medium text-gray-500">
                                    Subscription status
                                </p>

                                <p class="mt-2 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ $formatStatus($lifecycleStatus) }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-gray-50 p-5 dark:bg-gray-800">
                                <p class="text-sm font-medium text-gray-500">
                                    Platform approval
                                </p>

                                <p class="mt-2 text-lg font-bold text-gray-900 dark:text-white">
                                    {{ $hasBypass ? 'Approved' : 'Not approved' }}
                                </p>
                            </div>
                        </div>

                        @if (! $hasAccess)
                            <div class="mt-6 rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-100">
                                <h3 class="font-bold">
                                    Payment required
                                </h3>

                                <p class="mt-1 text-sm">
                                    Organization workflows remain unavailable until payment is confirmed or a Platform Admin approves access.
                                </p>
                            </div>
                        @endif
                    @endif
                </div>
            </section>

            @if ($plan)
                <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800 sm:px-8">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $plan->name }} plan capacity
                        </h2>

                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            The Organization Admin is included in the total user limit.
                        </p>
                    </div>

                    <div class="grid gap-px bg-gray-200 sm:grid-cols-2 lg:grid-cols-5 dark:bg-gray-800">
                        @foreach ([
                            'Total users' => $plan->max_users,
                            'Consent Managers' => $plan->max_consent_managers,
                            'Staff' => $plan->max_staff,
                            'Auditors' => $plan->max_auditors,
                            'Active kiosks' => $plan->max_active_kiosks,
                        ] as $label => $limit)
                            <div class="bg-white p-6 dark:bg-gray-900">
                                <p class="text-sm font-medium text-gray-500">
                                    {{ $label }}
                                </p>

                                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ number_format($limit) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

        </div>
    </div>
</x-app-layout>
