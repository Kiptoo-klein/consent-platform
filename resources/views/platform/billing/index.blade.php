<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-teal-700">
                    Platform administration
                </p>

                <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">
                    Billing Management
                </h1>

                <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-300">
                    Review subscription requests, verify payments, manage invoices,
                    and monitor organization billing health.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('platform.organizations.index') }}"
                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-extrabold text-slate-800 shadow-sm transition hover:bg-slate-50"
                >
                    Organizations
                </a>

                <a
                    href="{{ route(
                        'platform.subscription-invoice-reminder-settings.index'
                    ) }}"
                    class="inline-flex items-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-extrabold text-indigo-800 shadow-sm transition hover:bg-indigo-100"
                >
                    Reminder Settings
                </a>

                <a
                    href="{{ route(
                        'platform.subscription-payment-settings.index'
                    ) }}"
                    class="inline-flex items-center rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-extrabold text-white shadow-sm transition hover:bg-teal-800"
                >
                    Payment Settings
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $formatStatus = static fn (?string $status): string =>
            $status
                ? ucwords(str_replace('_', ' ', $status))
                : 'Unavailable';

        $paymentBadge = static function (?string $status): string {
            return match ($status) {
                'paid' =>
                    'background-color:#ecfdf5;color:#065f46;border-color:#a7f3d0;',

                'past_due', 'failed' =>
                    'background-color:#fef2f2;color:#991b1b;border-color:#fecaca;',

                'unpaid' =>
                    'background-color:#fffbeb;color:#92400e;border-color:#fde68a;',

                'refunded' =>
                    'background-color:#eef2ff;color:#3730a3;border-color:#c7d2fe;',

                default =>
                    'background-color:#f8fafc;color:#475569;border-color:#cbd5e1;',
            };
        };

        $subscriptionBadge = static function (?string $status): string {
            return match ($status) {
                'active' =>
                    'background-color:#ecfdf5;color:#065f46;border-color:#a7f3d0;',

                'trialing' =>
                    'background-color:#eff6ff;color:#1e40af;border-color:#bfdbfe;',

                'past_due', 'expired', 'cancelled' =>
                    'background-color:#fef2f2;color:#991b1b;border-color:#fecaca;',

                'suspended' =>
                    'background-color:#fffbeb;color:#92400e;border-color:#fde68a;',

                default =>
                    'background-color:#f8fafc;color:#475569;border-color:#cbd5e1;',
            };
        };

        $verificationPendingCount =
            $pendingPlanRequests
                ->filter(
                    static fn ($request): bool =>
                        $request->hasPendingPaymentClaim()
                )
                ->count();

        $awaitingCustomerPaymentCount =
            $pendingPlanRequests
                ->filter(
                    static fn ($request): bool =>
                        $request->invoice?->status?->isOutstanding()
                        && ! $request->hasPendingPaymentClaim()
                )
                ->count();

        $draftRequestCount =
            $pendingPlanRequests
                ->filter(
                    static fn ($request): bool =>
                        $request->invoice?->status?->value === 'draft'
                )
                ->count();
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-7 px-4 sm:px-6 lg:px-8">
            <section
                class="relative overflow-hidden rounded-3xl p-6 text-white shadow-xl sm:p-8"
                style="background:linear-gradient(135deg,#0f172a 0%,#134e4a 52%,#0f766e 100%);"
                data-platform-billing-overview
            >
                <div
                    aria-hidden="true"
                    class="absolute -right-20 -top-24 h-72 w-72 rounded-full border"
                    style="border-color:rgba(255,255,255,.08);"
                ></div>

                <div
                    aria-hidden="true"
                    class="absolute -bottom-28 right-24 h-64 w-64 rounded-full"
                    style="background-color:rgba(45,212,191,.12);"
                ></div>

                <div class="relative grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div class="max-w-2xl">
                        <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-teal-200">
                            Billing operations
                        </p>

                        <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">
                            Payment verification at a glance
                        </h2>

                        <p class="mt-3 max-w-xl text-sm font-medium leading-6 text-teal-50">
                            Prioritize reported payments first, then overdue
                            invoices and organizations requiring billing attention.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:min-w-[560px]">
                        @foreach ([
                            [
                                'label' => 'Verify now',
                                'value' => $verificationPendingCount,
                                'tone' => '#fef3c7',
                                'text' => '#78350f',
                            ],
                            [
                                'label' => 'Awaiting payment',
                                'value' => $awaitingCustomerPaymentCount,
                                'tone' => '#dbeafe',
                                'text' => '#1e3a8a',
                            ],
                            [
                                'label' => 'Outstanding',
                                'value' => $statistics['outstanding_invoices'],
                                'tone' => '#e0f2fe',
                                'text' => '#075985',
                            ],
                            [
                                'label' => 'Overdue',
                                'value' => $statistics['overdue_invoices'],
                                'tone' => '#fee2e2',
                                'text' => '#991b1b',
                            ],
                        ] as $metric)
                            <div
                                class="rounded-2xl border p-4 backdrop-blur"
                                style="background-color:rgba(255,255,255,.1);border-color:rgba(255,255,255,.16);"
                            >
                                <p class="text-xs font-bold uppercase tracking-[0.12em] text-teal-100">
                                    {{ $metric['label'] }}
                                </p>

                                <p class="mt-3 text-3xl font-black">
                                    {{ number_format($metric['value']) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section
                id="pending-plan-requests"
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"
                data-billing-action-queue
            >
                <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-amber-700">
                            Action queue
                        </p>

                        <h2 class="mt-1 text-xl font-black text-slate-950">
                            Pending Plan Requests
                        </h2>

                        <p class="mt-1 text-sm font-medium text-slate-600">
                            Customer payment reports appear first so Platform
                            Billing can verify and activate subscriptions quickly.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-extrabold text-amber-800">
                            {{ number_format($verificationPendingCount) }}
                            verification
                        </span>

                        <span class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-extrabold text-blue-800">
                            {{ number_format($awaitingCustomerPaymentCount) }}
                            awaiting payment
                        </span>

                        <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-extrabold text-slate-700">
                            {{ number_format($draftRequestCount) }}
                            draft
                        </span>
                    </div>
                </div>

                @if ($pendingPlanRequests->isEmpty())
                    <div class="p-10 text-center">
                        <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="h-6 w-6"
                                aria-hidden="true"
                            >
                                <path d="M5 12l4 4L19 6"></path>
                            </svg>
                        </span>

                        <p class="mt-4 font-extrabold text-slate-950">
                            No pending plan requests
                        </p>

                        <p class="mt-1 text-sm text-slate-600">
                            New requests and payment reports will appear here.
                        </p>
                    </div>
                @else
                    <div class="divide-y divide-slate-200">
                        @foreach (
                            $pendingPlanRequests
                                ->sortByDesc(
                                    static fn ($request): int =>
                                        $request->hasPendingPaymentClaim()
                                            ? 1
                                            : 0
                                )
                            as $planRequest
                        )
                            @php
                                $requestOrganization =
                                    $planRequest->organization;

                                $requestInvoice =
                                    $planRequest->invoice;

                                $claimPending =
                                    $planRequest->hasPendingPaymentClaim();

                                $invoiceStatus =
                                    $requestInvoice?->status?->value;

                                $queueTone =
                                    $claimPending
                                        ? [
                                            'background' => '#eff6ff',
                                            'border' => '#bfdbfe',
                                            'color' => '#1e40af',
                                            'label' => 'Payment reported',
                                        ]
                                        : (
                                            $invoiceStatus === 'draft'
                                                ? [
                                                    'background' => '#fffbeb',
                                                    'border' => '#fde68a',
                                                    'color' => '#92400e',
                                                    'label' => 'Draft invoice',
                                                ]
                                                : [
                                                    'background' => '#f0fdfa',
                                                    'border' => '#99f6e4',
                                                    'color' => '#115e59',
                                                    'label' => 'Awaiting payment',
                                                ]
                                        );
                            @endphp

                            <article class="grid gap-5 p-6 lg:grid-cols-[1fr_auto] lg:items-center">
                                <div class="grid gap-5 md:grid-cols-[1.1fr_1fr_1fr]">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span
                                                class="inline-flex rounded-full border px-3 py-1 text-xs font-extrabold"
                                                style="
                                                    background-color:{{ $queueTone['background'] }};
                                                    border-color:{{ $queueTone['border'] }};
                                                    color:{{ $queueTone['color'] }};
                                                "
                                            >
                                                {{ $queueTone['label'] }}
                                            </span>

                                            <span class="text-xs font-bold text-slate-500">
                                                Request #{{ $planRequest->id }}
                                            </span>
                                        </div>

                                        <h3 class="mt-3 text-lg font-black text-slate-950">
                                            {{ $requestOrganization?->name
                                                ?? 'Unavailable organization' }}
                                        </h3>

                                        <p class="mt-1 text-sm text-slate-500">
                                            Account #{{ $planRequest->organization_id }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">
                                            Requested subscription
                                        </p>

                                        <p class="mt-2 font-extrabold text-slate-950">
                                            {{ $planRequest->requestedPlan?->name
                                                ?? 'Unavailable plan' }}
                                        </p>

                                        <p class="mt-1 text-sm font-medium text-slate-600">
                                            {{ ucfirst($planRequest->billing_cycle) }}
                                            billing ·
                                            {{ $planRequest->currency }}
                                            {{ number_format(
                                                (float) $planRequest->amount_snapshot,
                                                2
                                            ) }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">
                                            Invoice and requester
                                        </p>

                                        <p class="mt-2 break-words font-extrabold text-slate-950">
                                            {{ $requestInvoice?->invoice_number
                                                ?? 'Not prepared' }}
                                        </p>

                                        <p class="mt-1 text-sm text-slate-600">
                                            {{ $planRequest->requestedBy?->name
                                                ?? 'Unavailable user' }}
                                        </p>

                                        @if ($claimPending)
                                            <p class="mt-2 text-xs font-bold text-blue-700">
                                                Ref:
                                                {{ $planRequest->payment_claim_reference }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex min-w-[180px] flex-col gap-2">
                                    @if ($requestOrganization && $requestInvoice)
                                        <a
                                            href="{{ route(
                                                'platform.organizations.subscription-invoices.show',
                                                [
                                                    $requestOrganization,
                                                    $requestInvoice,
                                                ]
                                            ) }}"
                                            class="inline-flex items-center justify-center rounded-xl px-4 py-3 text-sm font-extrabold text-white shadow-sm transition"
                                            style="background-color:{{ $claimPending ? '#2563eb' : '#0f766e' }};"
                                        >
                                            {{ $claimPending
                                                ? 'Verify Payment'
                                                : 'Review Invoice' }}
                                        </a>
                                    @elseif ($requestOrganization)
                                        <a
                                            href="{{ route(
                                                'platform.organizations.subscription-invoices.index',
                                                $requestOrganization
                                            ) }}"
                                            class="inline-flex items-center justify-center rounded-xl bg-slate-800 px-4 py-3 text-sm font-extrabold text-white shadow-sm"
                                        >
                                            View Account
                                        </a>
                                    @endif

                                    <p class="text-center text-xs font-medium text-slate-500">
                                        Requested
                                        {{ $planRequest->requested_at?->diffForHumans()
                                            ?? 'recently' }}
                                    </p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[0.15em] text-slate-500">
                            Billing accounts
                        </p>

                        <h2 class="mt-1 text-xl font-black text-slate-950">
                            Search and filter
                        </h2>
                    </div>

                    <p class="text-sm text-slate-500">
                        Accounts requiring attention are listed first.
                    </p>
                </div>

                <form
                    method="GET"
                    action="{{ route('platform.billing.index') }}"
                    class="mt-6 grid gap-4 lg:grid-cols-[1.5fr_1fr_1fr_auto]"
                >
                    <div>
                        <label for="search" class="block text-sm font-extrabold text-slate-800">
                            Search
                        </label>

                        <input
                            id="search"
                            name="search"
                            type="search"
                            value="{{ $search }}"
                            placeholder="Organization, billing owner, or email"
                            class="mt-2 block w-full rounded-xl border-slate-300 text-slate-950 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >
                    </div>

                    <div>
                        <label for="payment_status" class="block text-sm font-extrabold text-slate-800">
                            Payment status
                        </label>

                        <select
                            id="payment_status"
                            name="payment_status"
                            class="mt-2 block w-full rounded-xl border-slate-300 text-slate-950 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >
                            <option value="">All payment statuses</option>

                            @foreach ($paymentStatuses as $status)
                                <option
                                    value="{{ $status->value }}"
                                    @selected($paymentStatus === $status->value)
                                >
                                    {{ $formatStatus($status->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="subscription_status" class="block text-sm font-extrabold text-slate-800">
                            Subscription status
                        </label>

                        <select
                            id="subscription_status"
                            name="subscription_status"
                            class="mt-2 block w-full rounded-xl border-slate-300 text-slate-950 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >
                            <option value="">All subscription statuses</option>

                            @foreach ($subscriptionStatuses as $status)
                                <option
                                    value="{{ $status->value }}"
                                    @selected(
                                        $subscriptionStatus === $status->value
                                    )
                                >
                                    {{ $formatStatus($status->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button
                            type="submit"
                            class="inline-flex rounded-xl bg-teal-700 px-5 py-2.5 text-sm font-extrabold text-white shadow-sm transition hover:bg-teal-800"
                        >
                            Apply
                        </button>

                        <a
                            href="{{ route('platform.billing.index') }}"
                            class="inline-flex rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-extrabold text-slate-700 transition hover:bg-slate-50"
                        >
                            Reset
                        </a>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-xl font-black text-slate-950">
                                Organization Billing Accounts
                            </h2>

                            <p class="mt-1 text-sm font-medium text-slate-600">
                                Plan, owner, payment health, period, and invoice status.
                            </p>
                        </div>

                        <span class="inline-flex w-fit rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-extrabold text-slate-700">
                            {{ number_format($subscriptions->total()) }}
                            accounts
                        </span>
                    </div>
                </div>

                @if ($subscriptions->isEmpty())
                    <div class="p-10 text-center">
                        <p class="font-extrabold text-slate-950">
                            No billing accounts match these filters.
                        </p>

                        <p class="mt-1 text-sm text-slate-600">
                            Reset the filters or create a subscription from the
                            organization page.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    @foreach ([
                                        'Organization',
                                        'Plan and owner',
                                        'Payment',
                                        'Subscription',
                                        'Period ends',
                                        'Invoices',
                                        'Actions',
                                    ] as $heading)
                                        <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-[0.1em] text-slate-500">
                                            {{ $heading }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach ($subscriptions as $subscription)
                                    @php
                                        $accountOrganization =
                                            $subscription->organization;

                                        $owner =
                                            $subscription->billingOwner;

                                        $paymentValue =
                                            $subscription->payment_status?->value;

                                        $statusValue =
                                            $subscription->status?->value;
                                    @endphp

                                    <tr class="align-top transition hover:bg-slate-50/70">
                                        <td class="px-5 py-4">
                                            <p class="font-extrabold text-slate-950">
                                                {{ $accountOrganization?->name
                                                    ?? 'Unavailable organization' }}
                                            </p>

                                            <p class="mt-1 text-xs text-slate-500">
                                                Account #{{ $accountOrganization?->id }}
                                            </p>
                                        </td>

                                        <td class="px-5 py-4">
                                            <p class="font-bold text-slate-950">
                                                {{ $subscription->plan?->name
                                                    ?? 'No plan' }}
                                            </p>

                                            <p class="mt-1 text-sm font-medium text-slate-700">
                                                {{ $owner?->name
                                                    ?? 'No Billing Owner' }}
                                            </p>

                                            <p class="mt-1 text-xs text-slate-500">
                                                {{ $owner?->email ?? '—' }}
                                            </p>
                                        </td>

                                        <td class="px-5 py-4">
                                            <span
                                                class="inline-flex rounded-full border px-3 py-1 text-xs font-extrabold"
                                                style="{{ $paymentBadge($paymentValue) }}"
                                            >
                                                {{ $formatStatus($paymentValue) }}
                                            </span>
                                        </td>

                                        <td class="px-5 py-4">
                                            <span
                                                class="inline-flex rounded-full border px-3 py-1 text-xs font-extrabold"
                                                style="{{ $subscriptionBadge($statusValue) }}"
                                            >
                                                {{ $formatStatus($statusValue) }}
                                            </span>
                                        </td>

                                        <td class="px-5 py-4 text-sm font-bold text-slate-800">
                                            {{ $subscription->current_period_ends_at
                                                ?->copy()?->timezone(config('app.display_timezone'))?->format('M d, Y')
                                                ?? 'Not set' }}
                                        </td>

                                        <td class="px-5 py-4">
                                            <p class="font-extrabold text-slate-950">
                                                {{ number_format(
                                                    $subscription
                                                        ->outstanding_invoices_count
                                                ) }}
                                                outstanding
                                            </p>

                                            @if (
                                                $subscription
                                                    ->overdue_invoices_count > 0
                                            )
                                                <p class="mt-1 text-sm font-extrabold text-red-700">
                                                    {{ number_format(
                                                        $subscription
                                                            ->overdue_invoices_count
                                                    ) }}
                                                    overdue
                                                </p>
                                            @else
                                                <p class="mt-1 text-sm text-slate-500">
                                                    No overdue invoices
                                                </p>
                                            @endif
                                        </td>

                                        <td class="px-5 py-4">
                                            <div class="flex min-w-[220px] flex-wrap gap-2">
                                                <a
                                                    href="{{ route(
                                                        'platform.organizations.show',
                                                        $accountOrganization
                                                    ) }}"
                                                    class="inline-flex rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-extrabold text-slate-700 shadow-sm hover:bg-slate-50"
                                                >
                                                    Account
                                                </a>

                                                <a
                                                    href="{{ route(
                                                        'platform.organizations.subscription-invoices.index',
                                                        $accountOrganization
                                                    ) }}"
                                                    class="inline-flex rounded-lg bg-indigo-700 px-3 py-2 text-xs font-extrabold text-white shadow-sm hover:bg-indigo-800"
                                                >
                                                    Invoices
                                                </a>

                                                <a
                                                    href="{{ route(
                                                        'platform.organizations.subscription-transactions.index',
                                                        $accountOrganization
                                                    ) }}"
                                                    class="inline-flex rounded-lg bg-teal-700 px-3 py-2 text-xs font-extrabold text-white shadow-sm hover:bg-teal-800"
                                                >
                                                    Payments
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-slate-200 px-5 py-4">
                        {{ $subscriptions->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
