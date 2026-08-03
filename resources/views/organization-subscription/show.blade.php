<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Subscription Status
                </h1>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Review your organization's plan, usage, payment status and access.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                @if (
                    $subscription
                    && (int) $subscription->billing_owner_user_id
                        === (int) auth()->id()
                )
                    <a
                        href="{{ route('organization-billing.index') }}"
                        class="text-sm font-semibold text-indigo-700 transition hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        Billing & Receipts
                    </a>
                @endif

                <a
                    href="{{ route('profile.edit') }}"
                    class="text-sm font-semibold text-gray-600 transition hover:text-emerald-700 dark:text-gray-300"
                >
                    Manage profile
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $requiresPlanSelection =
            $subscription
                ?->requiresPlanSelection()
            ?? false;

        $plan = $subscription?->plan;

        $displayPlan =
            $requiresPlanSelection
                ? null
                : $plan;

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

        $usagePeriodStart =
            $usagePeriod['start'] ?? null;

        $usagePeriodEnd =
            $usagePeriod['end'] ?? null;

        $signedConsentPeriodLabel =
            $usagePeriodStart && $usagePeriodEnd
                ? $usagePeriodStart->copy()->timezone(config('app.display_timezone'))->format('M d, Y')
                    .' – '
                    .$usagePeriodEnd->copy()
                        ->subSecond()
                        ->copy()->timezone(config('app.display_timezone'))->format('M d, Y')
                : (
                    $usagePeriodStart
                        ? 'From '
                            .$usagePeriodStart
                                ->copy()->timezone(config('app.display_timezone'))->format('M d, Y')
                        : 'Current subscription period'
                );
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

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
                                    {{ $displayPlan?->name
                                        ?? 'No plan selected' }}
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
                            @elseif ($requiresPlanSelection)
                                Plan selection required
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
                                    {{ $requiresPlanSelection
                                        ? 'Plan not selected'
                                        : $formatStatus(
                                            $lifecycleStatus
                                        ) }}
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
                                @if ($requiresPlanSelection)
                                    <h3 class="font-bold">
                                        Choose a subscription plan
                                    </h3>

                                    <p class="mt-1 text-sm">
                                        The Organization Admin or Billing
                                        Owner must choose a plan before
                                        payment and activation.
                                    </p>
                                @else
                                    <h3 class="font-bold">
                                        Payment required
                                    </h3>

                                    <p class="mt-1 text-sm">
                                        Organization workflows remain
                                        unavailable until payment is confirmed
                                        or a Platform Admin approves access.
                                    </p>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            </section>

            @if ($plan)
                <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800 sm:px-8">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                            Subscription usage
                        </h2>

                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            @if ($requiresPlanSelection)
                                Current organization usage is shown for setup
                                reference. The
                            @else
                                Current use of the {{ $plan->name }} plan. The
                            @endif
                            Organization Admin is included in the total user limit.
                        </p>
                    </div>

                    <div class="grid gap-px bg-gray-200 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 dark:bg-gray-800">
                        @foreach ([
                            [
                                'label' => 'Total users',
                                'used' => $usage['users'],
                                'limit' => $plan->max_users,
                                'description' => null,
                            ],
                            [
                                'label' => 'Consent Managers',
                                'used' => $usage['consent_managers'],
                                'limit' => $plan->max_consent_managers,
                                'description' => null,
                            ],
                            [
                                'label' => 'Staff',
                                'used' => $usage['staff'],
                                'limit' => $plan->max_staff,
                                'description' => null,
                            ],
                            [
                                'label' => 'Auditors',
                                'used' => $usage['auditors'],
                                'limit' => $plan->max_auditors,
                                'description' => null,
                            ],
                            [
                                'label' => 'Active kiosks',
                                'used' => $usage['active_kiosks'],
                                'limit' => $plan->max_active_kiosks,
                                'description' => null,
                            ],
                            [
                                'label' => 'Consent templates',
                                'used' => $usage['consent_templates'],
                                'limit' => $plan->max_consent_templates,
                                'description' =>
                                    'Archived templates do not use capacity.',
                            ],
                            [
                                'label' => 'Signed consents this period',
                                'used' => $usage['signed_consents'],
                                'limit' =>
                                    $plan
                                        ->max_signed_consents_per_period,
                                'description' =>
                                    $signedConsentPeriodLabel,
                            ],
                        ] as $capacity)
                            @php
                                $used = $capacity['used'];
                                $limit = $capacity['limit'];

                                $unlimited =
                                    $limit === null;

                                $percentage =
                                    $unlimited
                                        ? 0
                                        : (
                                            $limit > 0
                                                ? min(
                                                    100,
                                                    (int) round(
                                                        ($used / $limit)
                                                        * 100
                                                    )
                                                )
                                                : 100
                                        );

                                $remaining =
                                    $unlimited
                                        ? null
                                        : max(
                                            0,
                                            $limit - $used
                                        );

                                $overage =
                                    $unlimited
                                        ? 0
                                        : max(
                                            0,
                                            $used - $limit
                                        );

                                $barClass =
                                    ! $unlimited
                                    && $used >= $limit
                                        ? 'bg-red-500'
                                        : (
                                            $percentage >= 80
                                                ? 'bg-amber-500'
                                                : 'bg-emerald-500'
                                        );
                            @endphp

                            <div class="bg-white p-6 dark:bg-gray-900">
                                <p class="text-sm font-medium text-gray-500">
                                    {{ $capacity['label'] }}
                                </p>

                                @if (filled($capacity['description']))
                                    <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                        {{ $capacity['description'] }}
                                    </p>
                                @endif

                                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ number_format($used) }}

                                    <span class="text-base font-semibold text-gray-500">
                                        @if ($unlimited)
                                            used · Unlimited
                                        @else
                                            of {{ number_format($limit) }}
                                        @endif
                                    </span>
                                </p>

                                @if ($unlimited)
                                    <div class="mt-4 rounded-full bg-indigo-100 px-3 py-1.5 text-center text-xs font-bold text-indigo-800 dark:bg-indigo-950 dark:text-indigo-200">
                                        Unlimited plan capacity
                                    </div>
                                @else
                                    <div
                                        class="mt-4 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700"
                                        role="progressbar"
                                        aria-label="{{ $capacity['label'] }} usage"
                                        aria-valuemin="0"
                                        aria-valuemax="{{ $limit }}"
                                        aria-valuenow="{{ min($used, $limit) }}"
                                    >
                                        <div
                                            class="h-full rounded-full {{ $barClass }}"
                                            style="width: {{ $percentage }}%"
                                        ></div>
                                    </div>
                                @endif

                                @if ($unlimited)
                                    <p class="mt-3 text-xs font-medium text-gray-500">
                                        No usage limit
                                    </p>
                                @elseif ($overage > 0)
                                    <p class="mt-3 text-xs font-semibold text-red-600 dark:text-red-400">
                                        {{ number_format($overage) }} over limit
                                    </p>
                                @else
                                    <p class="mt-3 text-xs font-medium text-gray-500">
                                        {{ number_format($remaining) }} remaining
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

        </div>
    </div>
</x-app-layout>
