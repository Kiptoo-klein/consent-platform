<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-950 dark:text-white">
                    Billing Management
                </h1>

                <p class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Review organization subscriptions, invoices, payments,
                    billing owners, and accounts requiring attention.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route(
                        'platform.organizations.index'
                    ) }}"
                    class="inline-flex items-center rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-teal-800"
                >
                    Organizations
                </a>

                <a
                    href="{{ route(
                        'platform.subscription-invoice-reminder-settings.index'
                    ) }}"
                    class="inline-flex items-center rounded-xl bg-indigo-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-800"
                >
                    Reminder Settings
                </a>

                <a
                    href="{{ route(
                        'platform.subscription-payment-settings.index'
                    ) }}"
                    class="inline-flex items-center rounded-xl border px-4 py-2.5 text-sm font-bold shadow-sm transition hover:opacity-90"
                    style="background-color:#b45309 !important;color:#ffffff !important;border-color:#92400e !important;"
                >
                    Payment Settings
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $formatStatus = static fn (?string $status): string =>
            $status
                ? ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $status
                    )
                )
                : 'Unavailable';

        $paymentBadge = static function (
            ?string $status
        ): string {
            return match ($status) {
                'paid' =>
                    'bg-emerald-100 text-emerald-900 '
                    .'dark:bg-emerald-950 dark:text-emerald-200',

                'past_due', 'failed' =>
                    'bg-red-100 text-red-900 '
                    .'dark:bg-red-950 dark:text-red-200',

                'unpaid' =>
                    'bg-amber-100 text-amber-900 '
                    .'dark:bg-amber-950 dark:text-amber-200',

                'refunded' =>
                    'bg-indigo-100 text-indigo-900 '
                    .'dark:bg-indigo-950 dark:text-indigo-200',

                default =>
                    'bg-gray-100 text-gray-800 '
                    .'dark:bg-gray-800 dark:text-gray-200',
            };
        };

        $subscriptionBadge = static function (
            ?string $status
        ): string {
            return match ($status) {
                'active' =>
                    'bg-emerald-100 text-emerald-900 '
                    .'dark:bg-emerald-950 dark:text-emerald-200',

                'trialing' =>
                    'bg-blue-100 text-blue-900 '
                    .'dark:bg-blue-950 dark:text-blue-200',

                'past_due', 'expired', 'cancelled' =>
                    'bg-red-100 text-red-900 '
                    .'dark:bg-red-950 dark:text-red-200',

                'suspended' =>
                    'bg-amber-100 text-amber-900 '
                    .'dark:bg-amber-950 dark:text-amber-200',

                default =>
                    'bg-gray-100 text-gray-800 '
                    .'dark:bg-gray-800 dark:text-gray-200',
            };
        };
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-3xl bg-gradient-to-r from-teal-900 via-teal-800 to-teal-700 p-6 text-white shadow-lg sm:p-8">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-100">
                    Manual payment workflow
                </p>

                <h2 class="mt-2 text-2xl font-bold">
                    Confirm the money before recording payment
                </h2>

                <div class="mt-6 grid gap-4 md:grid-cols-4">
                    @foreach ([
                        [
                            'number' => '1',
                            'title' => 'Select organization',
                            'body' => 'Open its invoices or payment history.',
                        ],
                        [
                            'number' => '2',
                            'title' => 'Issue invoice',
                            'body' => 'Create and issue the amount that is due.',
                        ],
                        [
                            'number' => '3',
                            'title' => 'Confirm payment',
                            'body' => 'Verify the bank, M-Pesa, or processor account.',
                        ],
                        [
                            'number' => '4',
                            'title' => 'Record payment',
                            'body' => 'Use Payments and save a successful transaction.',
                        ],
                    ] as $step)
                        <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-inset ring-white/20">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-sm font-bold text-teal-900">
                                {{ $step['number'] }}
                            </span>

                            <p class="mt-3 font-bold">
                                {{ $step['title'] }}
                            </p>

                            <p class="mt-1 text-sm leading-6 text-teal-50">
                                {{ $step['body'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-7">
                @foreach ([
                    [
                        'label' => 'Pending requests',
                        'value' => $statistics[
                            'pending_plan_requests'
                        ],
                        'class' => 'text-amber-700 dark:text-amber-300',
                    ],
                    [
                        'label' => 'Subscriptions',
                        'value' => $statistics['subscriptions'],
                        'class' => 'text-gray-950 dark:text-white',
                    ],
                    [
                        'label' => 'Paid',
                        'value' => $statistics['paid'],
                        'class' => 'text-emerald-700 dark:text-emerald-300',
                    ],
                    [
                        'label' => 'Payment attention',
                        'value' => $statistics['payment_attention'],
                        'class' => 'text-red-700 dark:text-red-300',
                    ],
                    [
                        'label' => 'Outstanding invoices',
                        'value' => $statistics['outstanding_invoices'],
                        'class' => 'text-amber-700 dark:text-amber-300',
                    ],
                    [
                        'label' => 'Overdue invoices',
                        'value' => $statistics['overdue_invoices'],
                        'class' => 'text-red-700 dark:text-red-300',
                    ],
                    [
                        'label' => 'No subscription',
                        'value' => $statistics['without_subscription'],
                        'class' => 'text-indigo-700 dark:text-indigo-300',
                    ],
                ] as $card)
                    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                            {{ $card['label'] }}
                        </p>

                        <p class="mt-3 text-3xl font-extrabold {{ $card['class'] }}">
                            {{ number_format($card['value']) }}
                        </p>
                    </section>
                @endforeach
            </div>

            <section
                id="pending-plan-requests"
                class="overflow-hidden rounded-3xl border-2 shadow-sm"
                style="background-color:#ffffff;border-color:#f59e0b;"
            >
                <div
                    class="flex flex-col gap-3 border-b px-6 py-5 sm:flex-row sm:items-center sm:justify-between"
                    style="background-color:#fffbeb;border-color:#fcd34d;"
                >
                    <div>
                        <h2
                            class="text-lg font-extrabold"
                            style="color:#111827;"
                        >
                            Pending Plan Requests
                        </h2>

                        <p
                            class="mt-1 text-sm font-semibold"
                            style="color:#78350f;"
                        >
                            Review organization requests and issue their
                            prepared draft invoices.
                        </p>
                    </div>

                    <span
                        class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-extrabold"
                        style="background-color:#fef3c7;color:#78350f;"
                    >
                        {{ number_format(
                            $pendingPlanRequests->count()
                        ) }}
                        awaiting review
                    </span>
                </div>

                @if ($pendingPlanRequests->isEmpty())
                    <div class="p-8 text-center">
                        <p
                            class="font-extrabold"
                            style="color:#111827;"
                        >
                            No pending plan requests
                        </p>

                        <p
                            class="mt-2 text-sm font-medium"
                            style="color:#4b5563;"
                        >
                            New organization requests will appear here
                            automatically.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead style="background-color:#f9fafb;">
                                <tr>
                                    @foreach ([
                                        'Organization',
                                        'Requested plan',
                                        'Amount',
                                        'Requested by',
                                        'Requested',
                                        'Draft invoice',
                                        'Action',
                                    ] as $heading)
                                        <th
                                            class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide"
                                            style="color:#374151;"
                                        >
                                            {{ $heading }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200">
                                @foreach (
                                    $pendingPlanRequests
                                    as $planRequest
                                )
                                    <tr class="align-top">
                                        <td class="px-5 py-4">
                                            <p
                                                class="font-extrabold"
                                                style="color:#111827;"
                                            >
                                                {{ $planRequest
                                                    ->organization
                                                    ?->name
                                                    ?? 'Unavailable organization' }}
                                            </p>

                                            <p
                                                class="mt-1 text-xs font-semibold"
                                                style="color:#6b7280;"
                                            >
                                                Account #{{ $planRequest
                                                    ->organization_id }}
                                            </p>
                                        </td>

                                        <td class="px-5 py-4">
                                            <p
                                                class="font-bold"
                                                style="color:#111827;"
                                            >
                                                {{ $planRequest
                                                    ->requestedPlan
                                                    ?->name
                                                    ?? 'Unavailable plan' }}
                                            </p>

                                            <p
                                                class="mt-1 text-sm font-semibold"
                                                style="color:#4b5563;"
                                            >
                                                {{ ucfirst(
                                                    $planRequest
                                                        ->billing_cycle
                                                ) }}
                                                billing
                                            </p>

                                            <p
                                                class="mt-1 text-xs"
                                                style="color:#6b7280;"
                                            >
                                                Current:
                                                {{ $planRequest
                                                    ->currentPlan
                                                    ?->name
                                                    ?? 'No current plan' }}
                                            </p>
                                        </td>

                                        <td class="px-5 py-4">
                                            <p
                                                class="font-extrabold"
                                                style="color:#111827;"
                                            >
                                                {{ $planRequest->currency }}
                                                {{ number_format(
                                                    (float) $planRequest
                                                        ->amount_snapshot,
                                                    2
                                                ) }}
                                            </p>
                                        </td>

                                        <td class="px-5 py-4">
                                            <p
                                                class="font-bold"
                                                style="color:#111827;"
                                            >
                                                {{ $planRequest
                                                    ->requestedBy
                                                    ?->name
                                                    ?? 'Unavailable user' }}
                                            </p>

                                            <p
                                                class="mt-1 text-xs"
                                                style="color:#6b7280;"
                                            >
                                                {{ $planRequest
                                                    ->requestedBy
                                                    ?->email
                                                    ?? '—' }}
                                            </p>
                                        </td>

                                        <td
                                            class="px-5 py-4 text-sm font-semibold"
                                            style="color:#374151;"
                                        >
                                            {{ $planRequest
                                                ->requested_at
                                                ?->format('M d, Y H:i')
                                                ?? 'Unavailable' }}
                                        </td>

                                        <td class="px-5 py-4">
                                            <p
                                                class="font-bold"
                                                style="color:#111827;"
                                            >
                                                {{ $planRequest
                                                    ->invoice
                                                    ?->invoice_number
                                                    ?? 'Not prepared' }}
                                            </p>

                                            <span
                                                class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-extrabold"
                                                style="background-color:#e5e7eb;color:#111827;"
                                            >
                                                {{ $planRequest
                                                    ->invoice
                                                    ?->status
                                                    ?->label()
                                                    ?? 'Unavailable' }}
                                            </span>
                                        </td>

                                        <td class="px-5 py-4">
                                            @if (
                                                $planRequest->organization
                                                && $planRequest->invoice
                                            )
                                                <a
                                                    href="{{ route(
                                                        'platform.organizations.subscription-invoices.show',
                                                        [
                                                            $planRequest
                                                                ->organization,
                                                            $planRequest
                                                                ->invoice,
                                                        ]
                                                    ) }}"
                                                    class="inline-flex items-center justify-center rounded-xl border px-4 py-2.5 text-xs font-extrabold shadow-sm transition hover:opacity-90"
                                                    style="background-color:#1d4ed8 !important;color:#ffffff !important;border-color:#1e40af !important;"
                                                >
                                                    Review Invoice
                                                </a>
                                            @elseif (
                                                $planRequest->organization
                                            )
                                                <a
                                                    href="{{ route(
                                                        'platform.organizations.subscription-invoices.index',
                                                        $planRequest
                                                            ->organization
                                                    ) }}"
                                                    class="inline-flex items-center justify-center rounded-xl border px-4 py-2.5 text-xs font-extrabold"
                                                    style="background-color:#374151 !important;color:#ffffff !important;border-color:#1f2937 !important;"
                                                >
                                                    View Account
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <form
                    method="GET"
                    action="{{ route(
                        'platform.billing.index'
                    ) }}"
                    class="grid gap-4 lg:grid-cols-4"
                >
                    <div>
                        <label
                            for="search"
                            class="block text-sm font-bold text-gray-900 dark:text-white"
                        >
                            Search
                        </label>

                        <input
                            id="search"
                            name="search"
                            type="search"
                            value="{{ $search }}"
                            placeholder="Organization, owner, or email"
                            class="mt-2 block w-full rounded-xl border-gray-300 text-gray-950 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        >
                    </div>

                    <div>
                        <label
                            for="payment_status"
                            class="block text-sm font-bold text-gray-900 dark:text-white"
                        >
                            Payment status
                        </label>

                        <select
                            id="payment_status"
                            name="payment_status"
                            class="mt-2 block w-full rounded-xl border-gray-300 text-gray-950 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        >
                            <option value="">
                                All payment statuses
                            </option>

                            @foreach ($paymentStatuses as $status)
                                <option
                                    value="{{ $status->value }}"
                                    @selected(
                                        $paymentStatus
                                            === $status->value
                                    )
                                >
                                    {{ $formatStatus(
                                        $status->value
                                    ) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            for="subscription_status"
                            class="block text-sm font-bold text-gray-900 dark:text-white"
                        >
                            Subscription status
                        </label>

                        <select
                            id="subscription_status"
                            name="subscription_status"
                            class="mt-2 block w-full rounded-xl border-gray-300 text-gray-950 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        >
                            <option value="">
                                All subscription statuses
                            </option>

                            @foreach ($subscriptionStatuses as $status)
                                <option
                                    value="{{ $status->value }}"
                                    @selected(
                                        $subscriptionStatus
                                            === $status->value
                                    )
                                >
                                    {{ $formatStatus(
                                        $status->value
                                    ) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-3">
                        <button
                            type="submit"
                            class="inline-flex rounded-xl bg-teal-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-teal-800"
                        >
                            Apply Filters
                        </button>

                        <a
                            href="{{ route(
                                'platform.billing.index'
                            ) }}"
                            class="inline-flex rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-bold text-gray-800 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200"
                        >
                            Reset
                        </a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800">
                    <h2 class="text-lg font-bold text-gray-950 dark:text-white">
                        Organization Billing Accounts
                    </h2>

                    <p class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Organizations needing payment attention are listed first.
                    </p>
                </div>

                @if ($subscriptions->isEmpty())
                    <div class="p-8 text-center">
                        <p class="font-bold text-gray-950 dark:text-white">
                            No billing accounts match these filters.
                        </p>

                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                            Reset the filters or create a subscription from
                            the organization page.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                            <thead class="bg-gray-50 dark:bg-gray-950">
                                <tr>
                                    @foreach ([
                                        'Organization',
                                        'Plan and owner',
                                        'Payment',
                                        'Subscription',
                                        'Period ends',
                                        'Invoices',
                                        'Quick Actions',
                                    ] as $heading)
                                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                            {{ $heading }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-900">
                                @foreach ($subscriptions as $subscription)
                                    @php
                                        $organization =
                                            $subscription->organization;

                                        $owner =
                                            $subscription->billingOwner;

                                        $paymentValue =
                                            $subscription
                                                ->payment_status
                                                ?->value;

                                        $statusValue =
                                            $subscription
                                                ->status
                                                ?->value;
                                    @endphp

                                    <tr class="align-top">
                                        <td class="px-5 py-4">
                                            <p class="font-bold text-gray-950 dark:text-white">
                                                {{ $organization?->name
                                                    ?? 'Unavailable organization' }}
                                            </p>

                                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                                Account #{{ $organization?->id }}
                                            </p>
                                        </td>

                                        <td class="px-5 py-4">
                                            <p class="font-semibold text-gray-950 dark:text-white">
                                                {{ $subscription->plan?->name
                                                    ?? 'No plan' }}
                                            </p>

                                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                                {{ $owner?->name
                                                    ?? 'No Billing Owner' }}
                                            </p>

                                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                                {{ $owner?->email ?? '—' }}
                                            </p>
                                        </td>

                                        <td class="px-5 py-4">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $paymentBadge($paymentValue) }}">
                                                {{ $formatStatus(
                                                    $paymentValue
                                                ) }}
                                            </span>
                                        </td>

                                        <td class="px-5 py-4">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $subscriptionBadge($statusValue) }}">
                                                {{ $formatStatus(
                                                    $statusValue
                                                ) }}
                                            </span>
                                        </td>

                                        <td class="px-5 py-4 text-sm font-semibold text-gray-800 dark:text-gray-200">
                                            {{ $subscription
                                                ->current_period_ends_at
                                                ?->format('M d, Y')
                                                ?? 'Not set' }}
                                        </td>

                                        <td class="px-5 py-4">
                                            <p class="font-bold text-gray-950 dark:text-white">
                                                {{ number_format(
                                                    $subscription
                                                        ->outstanding_invoices_count
                                                ) }}
                                                outstanding
                                            </p>

                                            @if (
                                                $subscription
                                                    ->overdue_invoices_count
                                                > 0
                                            )
                                                <p class="mt-1 text-sm font-bold text-red-700 dark:text-red-300">
                                                    {{ number_format(
                                                        $subscription
                                                            ->overdue_invoices_count
                                                    ) }}
                                                    overdue
                                                </p>
                                            @else
                                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                    No overdue invoices
                                                </p>
                                            @endif
                                        </td>

                                        <td class="px-5 py-4">
                                            <div class="grid min-w-[10.5rem] gap-2">
                                                <a
                                                    href="{{ route(
                                                        'platform.organizations.show',
                                                        $organization
                                                    ) }}"
                                                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-bold text-gray-800 shadow-sm transition hover:border-gray-400 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-800"
                                                >
                                                    Manage Account
                                                </a>

                                                <a
                                                    href="{{ route(
                                                        'platform.organizations.subscription-invoices.index',
                                                        $organization
                                                    ) }}"
                                                    class="inline-flex items-center justify-center rounded-lg bg-indigo-700 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                                >
                                                    View Invoices
                                                </a>

                                                <a
                                                    href="{{ route(
                                                        'platform.organizations.subscription-transactions.index',
                                                        $organization
                                                    ) }}"
                                                    class="inline-flex items-center justify-center rounded-lg bg-teal-700 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                                                >
                                                    Record Payment
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                        {{ $subscriptions->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
