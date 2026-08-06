<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-950 dark:text-white">
                    Subscription Plans
                </h1>

                <p class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Compare plans, limits, monthly pricing, and discounted
                    annual billing for {{ $organization->name }}.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route(
                        'organization-subscription.show'
                    ) }}"
                    class="inline-flex items-center justify-center rounded-xl border px-4 py-2.5 text-sm font-extrabold shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                    style="background-color:#111827 !important;color:#ffffff !important;border-color:#111827 !important;"
                >
                    Subscription Status
                </a>

                <a
                    href="{{ route(
                        'organization-billing.index'
                    ) }}"
                    class="inline-flex rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-teal-800"
                >
                    Billing &amp; Receipts
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $requiresPlanSelection =
            $subscription
                ?->requiresPlanSelection()
            ?? false;

        $currentPlan =
            $requiresPlanSelection
                ? null
                : $subscription?->plan;

        $currentPlanId =
            $requiresPlanSelection
                ? null
                : $subscription
                    ?->subscription_plan_id;

        $currentBillingCycle =
            $subscription?->billing_cycle
                ?: 'monthly';

        $formatCycle =
            static fn (string $cycle): string =>
                ucfirst($cycle);

        $usageCards = [
            [
                'label' => 'Total users',
                'usage' => $usage['users'],
                'limit' => $currentPlan?->max_users,
            ],
            [
                'label' => 'Consent Managers',
                'usage' => $usage['consent_managers'],
                'limit' => $currentPlan?->max_consent_managers,
            ],
            [
                'label' => 'Staff',
                'usage' => $usage['staff'],
                'limit' => $currentPlan?->max_staff,
            ],
            [
                'label' => 'Auditors',
                'usage' => $usage['auditors'],
                'limit' => $currentPlan?->max_auditors,
            ],
            [
                'label' => 'Active kiosks',
                'usage' => $usage['active_kiosks'],
                'limit' => $currentPlan?->max_active_kiosks,
            ],
            [
                'label' => 'Consent templates',
                'usage' => $usage['consent_templates'],
                'limit' => $currentPlan?->max_consent_templates,
            ],
            [
                'label' => 'Signed consents this period',
                'usage' => $usage['signed_consents'],
                'limit' =>
                    $currentPlan
                        ?->max_signed_consents_per_period,
            ],
        ];
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-2xl border border-emerald-300 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border-2 border-red-300 bg-red-50 px-5 py-4 text-red-900 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200">
                    <p class="font-bold">
                        The subscription request could not be submitted.
                    </p>

                    <ul class="mt-2 list-inside list-disc space-y-1 text-sm font-medium">
                        @foreach ($errors->all() as $message)
                            <li>
                                {{ $message }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="overflow-hidden rounded-3xl bg-gradient-to-r from-teal-950 via-teal-900 to-indigo-900 text-white shadow-lg">
                <div class="grid gap-8 p-6 sm:p-8 lg:grid-cols-[1.1fr_1fr]">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-100">
                            {{ $currentPlan
                                ? 'Current subscription'
                                : 'Choose your subscription plan' }}
                        </p>

                        <h2 class="mt-3 text-3xl font-extrabold">
                            {{ $currentPlan?->name
                                ?? 'No plan selected' }}
                        </h2>

                        @if ($currentPlan)
                            <div class="mt-5 flex flex-wrap gap-2">
                                <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold ring-1 ring-inset ring-white/25">
                                    {{ $formatCycle(
                                        $currentBillingCycle
                                    ) }}
                                    billing
                                </span>

                                <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold ring-1 ring-inset ring-white/25">
                                    {{ ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $subscription
                                                ->status
                                                ?->value
                                                ?? 'unknown'
                                        )
                                    ) }}
                                </span>

                                <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold ring-1 ring-inset ring-white/25">
                                    Payment:
                                    {{ ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $subscription
                                                ->payment_status
                                                ?->value
                                                ?? 'unknown'
                                        )
                                    ) }}
                                </span>
                            </div>
                        @endif

                        <p class="mt-5 max-w-2xl text-sm font-medium leading-7 text-teal-50">
                            @if ($currentPlan)
                                Selecting another plan creates a pending request and issues its invoice immediately. Your current plan remains unchanged until payment is confirmed.
                            @else
                                No plan has been selected for this organization.
                                Compare every active plan below, then choose a
                                monthly or annual billing cycle.
                            @endif
                        </p>
                    </div>

                    @if ($currentPlan)
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                            @foreach ($usageCards as $item)
                                <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-inset ring-white/20">
                                    <p class="text-xs font-bold uppercase tracking-wide text-teal-100">
                                        {{ $item['label'] }}
                                    </p>

                                    <p class="mt-2 text-xl font-extrabold">
                                        {{ number_format(
                                            $item['usage']
                                        ) }}

                                        <span class="text-sm font-semibold text-teal-100">
                                            @if ($item['limit'] === null)
                                                / Unlimited
                                            @else
                                                / {{ number_format(
                                                    $item['limit']
                                                ) }}
                                            @endif
                                        </span>
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex items-center">
                            <div class="w-full rounded-2xl bg-white/10 p-6 ring-1 ring-inset ring-white/20">
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-teal-100">
                                    First-time setup
                                </p>

                                <h3 class="mt-3 text-xl font-extrabold text-white">
                                    Every active plan is available
                                </h3>

                                <p class="mt-3 text-sm font-medium leading-7 text-teal-50">
                                    Nothing has been preselected. Review the
                                    pricing and limits below, then choose the
                                    plan that fits your organization.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            @if ($pendingRequest)
                @php
                    $pendingInvoice =
                        $pendingRequest->invoice;

                    $pendingInvoiceStatus =
                        $pendingInvoice?->status;

                    $isDraftInvoice =
                        $pendingInvoiceStatus
                        === \App\Enums\SubscriptionInvoiceStatus::
                            DRAFT;

                    $isPayableInvoice = in_array(
                        $pendingInvoiceStatus,
                        [
                            \App\Enums\SubscriptionInvoiceStatus::
                                ISSUED,

                            \App\Enums\SubscriptionInvoiceStatus::
                                OVERDUE,
                        ],
                        true
                    );

                    $paymentDetails =
                        $pendingInvoice
                            ?->payment_details_snapshot
                        ?? [];

                    $mpesaEnabled =
                        (bool) data_get(
                            $paymentDetails,
                            'mpesa_enabled',
                            false
                        );

                    $bankEnabled =
                        (bool) data_get(
                            $paymentDetails,
                            'bank_enabled',
                            false
                        );

                    $billingEmail =
                        data_get(
                            $paymentDetails,
                            'billing_contact_email'
                        );

                    $billingPhone =
                        data_get(
                            $paymentDetails,
                            'billing_contact_phone'
                        );

                    $additionalInstructions =
                        data_get(
                            $paymentDetails,
                            'additional_instructions'
                        );

                    $hasPaymentInformation =
                        $mpesaEnabled
                        || $bankEnabled
                        || filled($billingEmail)
                        || filled($billingPhone)
                        || filled(
                            $additionalInstructions
                        );
                @endphp

                @php
                    $pendingPlanName =
                        $pendingRequest
                            ->requestedPlan
                            ?->name
                        ?? 'Requested plan';

                    $pendingCycleLabel =
                        $formatCycle(
                            $pendingRequest
                                ->billing_cycle
                        );

                    $pendingInvoiceNumber =
                        $pendingInvoice
                            ?->invoice_number;

                    $pendingAmountLabel =
                        $pendingInvoice
                            ? (
                                $pendingInvoice->currency
                                .' '
                                .number_format(
                                    (float) $pendingInvoice
                                        ->total_amount,
                                    2
                                )
                            )
                            : (
                                $pendingRequest->currency
                                .' '
                                .number_format(
                                    (float) $pendingRequest
                                        ->amount_snapshot,
                                    2
                                )
                            );

                    $pendingStatusLabel =
                        $pendingInvoiceStatus
                            ?->label()
                        ?? 'Pending';

                    $canCancelDraftRequest =
                        $isDraftInvoice
                        && $pendingRequest
                            ->canBeCancelledByOrganization();

                    $canCancelIssuedRequest =
                        $isPayableInvoice
                        && ! $pendingRequest
                            ->hasPendingPaymentClaim()
                        && ! $pendingRequest
                            ->hasConfirmedPaymentClaim();

                    $canCancelPendingRequest =
                        $canCancelDraftRequest
                        || $canCancelIssuedRequest;
                @endphp

                <section
                    class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"
                    data-pending-subscription-summary
                >
                    <div
                        class="px-6 py-6 text-white sm:px-8"
                        style="background:linear-gradient(135deg,#0f172a 0%,#172554 58%,#312e81 100%);"
                    >
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-extrabold uppercase tracking-[0.14em] text-indigo-100">
                                        Pending plan change
                                    </span>

                                    <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-extrabold text-white">
                                        {{ $pendingStatusLabel }}
                                    </span>
                                </div>

                                <h2 class="mt-4 text-2xl font-black">
                                    {{ $pendingPlanName }}
                                    · {{ $pendingCycleLabel }}
                                </h2>

                                <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-indigo-100">
                                    The invoice is managed from Billing. Your
                                    current subscription remains active until
                                    payment is verified and this plan is activated.
                                </p>
                            </div>

                            <div class="rounded-2xl border border-white/15 bg-white/10 px-5 py-4 lg:min-w-[230px] lg:text-right">
                                <p class="text-xs font-bold uppercase tracking-[0.14em] text-indigo-200">
                                    Invoice amount
                                </p>

                                <p class="mt-2 text-2xl font-black text-white">
                                    {{ $pendingAmountLabel }}
                                </p>

                                @if ($pendingInvoice?->due_date)
                                    <p class="mt-2 text-xs font-semibold text-slate-300">
                                        Due
                                        {{ $pendingInvoice
                                            ->due_date
                                            ->copy()->timezone(config('app.display_timezone'))->format('M j, Y') }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-6 p-6 sm:p-8 lg:grid-cols-[1fr_auto] lg:items-center">
                        <dl class="grid gap-5 sm:grid-cols-3">
                            <div>
                                <dt class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">
                                    Requested plan
                                </dt>

                                <dd class="mt-1 font-extrabold text-slate-950">
                                    {{ $pendingPlanName }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">
                                    Billing cycle
                                </dt>

                                <dd class="mt-1 font-extrabold text-slate-950">
                                    {{ $pendingCycleLabel }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">
                                    Invoice
                                </dt>

                                <dd class="mt-1 break-words font-extrabold text-slate-950">
                                    {{ $pendingInvoiceNumber
                                        ?? 'Preparing invoice' }}
                                </dd>
                            </div>
                        </dl>

                        <div class="flex flex-wrap gap-3 lg:justify-end">
                            @if (
                                $pendingInvoice
                                && ! $isDraftInvoice
                            )
                                <a
                                    href="{{ route(
                                        'organization-billing.invoices.show',
                                        $pendingInvoice
                                    ) }}"
                                    class="inline-flex items-center justify-center rounded-xl bg-indigo-700 px-5 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-indigo-800"
                                >
                                    View Invoice
                                </a>

                                <a
                                    href="{{ route(
                                        'organization-billing.invoices.download',
                                        $pendingInvoice
                                    ) }}"
                                    class="inline-flex items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-3 text-sm font-extrabold text-indigo-800 shadow-sm transition hover:bg-indigo-100"
                                >
                                    Download PDF
                                </a>
                            @endif

                            @if ($canCancelPendingRequest)
                                <form
                                    method="POST"
                                    action="{{ $canCancelDraftRequest
                                        ? route(
                                            'organization-subscription-plans.cancel',
                                            $pendingRequest
                                        )
                                        : route(
                                            'organization-subscription-payment-claims.cancel',
                                            $pendingRequest
                                        ) }}"
                                    x-data
                                    data-subscription-summary-cancel-confirmation
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-white px-5 py-3 text-sm font-extrabold text-red-700 shadow-sm transition hover:bg-red-50"
                                        x-on:click="$dispatch(
                                            'open-modal',
                                            'cancel-subscription-summary-{{ $pendingRequest->id }}'
                                        )"
                                    >
                                        Cancel Request
                                    </button>

                                    <x-action-confirmation-modal
                                        name="cancel-subscription-summary-{{ $pendingRequest->id }}"
                                        title="Cancel this subscription request and invoice?"
                                        message="The pending plan request and associated invoice will be cancelled. Your current subscription will remain unchanged."
                                        confirm-text="Cancel request"
                                        variant="danger"
                                    />
                                </form>
                            @endif
                        </div>
                    </div>
                </section>
            @endif

            <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-4">
                @forelse ($plans as $plan)
                    @php
                        $isCurrentPlan =
                            (int) $currentPlanId
                            === (int) $plan->id;

                        $annualPrice =
                            $plan->annualPrice();

                        $annualSavings =
                            $plan->annualSavings();

                        $pricingConfigured =
                            $plan->monthly_price
                            !== null;
                    @endphp

                    <section
                        @class([
                            'flex h-full flex-col overflow-hidden rounded-3xl border bg-white shadow-sm dark:bg-gray-900',
                            'border-2 border-teal-600 ring-4 ring-teal-100 dark:ring-teal-950' =>
                                $isCurrentPlan,
                            'border-gray-200 dark:border-gray-800' =>
                                ! $isCurrentPlan,
                        ])
                    >
                        <div class="border-b border-gray-200 p-6 dark:border-gray-800">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-xl font-extrabold text-gray-950 dark:text-white">
                                        {{ $plan->name }}
                                    </h2>

                                    <p class="mt-2 text-sm font-medium leading-6 text-gray-700 dark:text-gray-300">
                                        {{ $plan->description
                                            ?: 'Subscription plan for organization consent workflows.' }}
                                    </p>
                                </div>

                                @if ($isCurrentPlan)
                                    <span class="shrink-0 rounded-full bg-teal-100 px-3 py-1 text-xs font-bold text-teal-900 dark:bg-teal-950 dark:text-teal-200">
                                        Current
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="border-b border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-950">
                            @if ($pricingConfigured)
                                <p class="text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">
                                    Monthly
                                </p>

                                <p class="mt-2 text-2xl font-extrabold text-gray-950 dark:text-white">
                                    {{ $plan->currency ?: 'KES' }}
                                    {{ number_format(
                                        (float) $plan
                                            ->monthly_price,
                                        2
                                    ) }}
                                </p>

                                @if (
                                    $plan->annual_billing_enabled
                                    && $annualPrice !== null
                                )
                                    <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                                        <p class="text-xs font-bold uppercase tracking-wide text-emerald-800 dark:text-emerald-300">
                                            Annual
                                        </p>

                                        <p class="mt-2 text-lg font-extrabold text-gray-950 dark:text-white">
                                            {{ $plan->currency ?: 'KES' }}
                                            {{ number_format(
                                                (float) $annualPrice,
                                                2
                                            ) }}
                                        </p>

                                        <p class="mt-1 text-xs font-bold text-emerald-700 dark:text-emerald-300">
                                            {{ number_format(
                                                (float) $plan
                                                    ->annual_discount_percent,
                                                2
                                            ) }}%
                                            discount

                                            @if ($annualSavings !== null)
                                                · Save
                                                {{ $plan->currency ?: 'KES' }}
                                                {{ number_format(
                                                    (float) $annualSavings,
                                                    2
                                                ) }}
                                            @endif
                                        </p>
                                    </div>
                                @endif
                            @else
                                <p class="font-bold text-amber-800 dark:text-amber-300">
                                    Pricing not configured
                                </p>

                                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                                    This plan cannot be requested until
                                    Platform Billing adds a rate.
                                </p>
                            @endif
                        </div>

                        <div class="flex-1 p-6">
                            <h3 class="text-sm font-bold text-gray-950 dark:text-white">
                                Plan benefits and limits
                            </h3>

                            <dl class="mt-4 space-y-3 text-sm">
                                @foreach ([
                                    [
                                        'label' => 'Total users',
                                        'value' => $plan->max_users,
                                    ],
                                    [
                                        'label' => 'Consent Managers',
                                        'value' => $plan->max_consent_managers,
                                    ],
                                    [
                                        'label' => 'Staff',
                                        'value' => $plan->max_staff,
                                    ],
                                    [
                                        'label' => 'Auditors',
                                        'value' => $plan->max_auditors,
                                    ],
                                    [
                                        'label' => 'Active kiosks',
                                        'value' => $plan->max_active_kiosks,
                                    ],
                                    [
                                        'label' => 'Consent templates',
                                        'value' =>
                                            $plan
                                                ->max_consent_templates,
                                    ],
                                    [
                                        'label' =>
                                            'Signed consents / period',
                                        'value' =>
                                            $plan
                                                ->max_signed_consents_per_period,
                                    ],
                                ] as $limit)
                                    <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-2 dark:border-gray-800">
                                        <dt class="font-medium text-gray-700 dark:text-gray-300">
                                            {{ $limit['label'] }}
                                        </dt>

                                        <dd class="font-extrabold text-gray-950 dark:text-white">
                                            @if ($limit['value'] === null)
                                                Unlimited
                                            @else
                                                {{ number_format(
                                                    $limit['value']
                                                ) }}
                                            @endif
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>

                            <p class="mt-4 text-xs font-medium leading-5 text-gray-500 dark:text-gray-400">
                                Archived templates do not use template capacity.
                                Signed-consent usage resets each subscription
                                billing period.
                            </p>
                        </div>

                        <div class="border-t border-gray-200 p-6 dark:border-gray-800">
                            @if (
                                $canRequestPlan
                                && ! $pendingRequest
                                && $pricingConfigured
                            )
                                <div class="grid gap-3">
                                    @if (
                                        ! (
                                            $isCurrentPlan
                                            && $currentBillingCycle
                                                === 'monthly'
                                        )
                                    )
                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'organization-subscription-plans.select-and-issue',
                                                $plan
                                            ) }}"
                                        >
                                            @csrf

                                            <input
                                                type="hidden"
                                                name="billing_cycle"
                                                value="monthly"
                                            >

                                            <button
                                                type="submit"
                                                class="inline-flex w-full items-center justify-center rounded-xl bg-teal-700 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-teal-800"
                                            >
                                                Choose Monthly
                                            </button>
                                        </form>
                                    @else
                                        <div
                                            aria-current="true"
                                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border px-4 py-3 text-center text-sm font-extrabold text-white shadow-sm"
                                            style="background-color:#0f766e !important;color:#ffffff !important;border-color:#115e59 !important;opacity:1 !important;"
                                        >
                                            <svg
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2.25"
                                                class="h-4 w-4"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M5 12l4 4L19 6"
                                                />
                                            </svg>

                                            <span>
                                                Current monthly plan
                                            </span>
                                        </div>
                                    @endif

                                    @if (
                                        $plan
                                            ->annual_billing_enabled
                                        && $annualPrice !== null
                                    )
                                        @if (
                                            ! (
                                                $isCurrentPlan
                                                && $currentBillingCycle
                                                    === 'annual'
                                            )
                                        )
                                            <form
                                                method="POST"
                                                action="{{ route(
                                                    'organization-subscription-plans.select-and-issue',
                                                    $plan
                                                ) }}"
                                            >
                                                @csrf

                                                <input
                                                    type="hidden"
                                                    name="billing_cycle"
                                                    value="annual"
                                                >

                                                <button
                                                    type="submit"
                                                    class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-700 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-800"
                                                >
                                                    Choose Annual
                                                </button>
                                            </form>
                                        @else
                                            <div
                                                aria-current="true"
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border px-4 py-3 text-center text-sm font-extrabold text-white shadow-sm"
                                                style="background-color:#4338ca !important;color:#ffffff !important;border-color:#3730a3 !important;opacity:1 !important;"
                                            >
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2.25"
                                                    class="h-4 w-4"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M5 12l4 4L19 6"
                                                    />
                                                </svg>

                                                <span>
                                                    Current annual plan
                                                </span>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            @elseif ($pendingRequest)
                                @if (
                                    (int) $pendingRequest
                                        ->requested_subscription_plan_id
                                    === (int) $plan->id
                                )
                                    <p
                                        class="rounded-xl border px-4 py-3 text-center text-sm font-extrabold shadow-sm"
                                        style="background-color:#f59e0b !important;color:#ffffff !important;border-color:#d97706 !important;"
                                    >
                                        Pending request
                                    </p>
                                @else
                                    @if ($canCancelPendingRequest)
                                        @if ($isCurrentPlan)
                                            <form
                                                method="POST"
                                                action="{{ $canCancelDraftRequest
                                                    ? route(
                                                        'organization-subscription-plans.cancel',
                                                        $pendingRequest
                                                    )
                                                    : route(
                                                        'organization-subscription-payment-claims.cancel',
                                                        $pendingRequest
                                                    ) }}"
                                                x-data
                                                data-subscription-card-cancel-confirmation
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="button"
                                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border px-4 py-3 text-center text-xs font-extrabold leading-5 text-white shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                                    style="background-color:#dc2626 !important;color:#ffffff !important;border-color:#b91c1c !important;"
                                                    x-on:click="$dispatch(
                                                        'open-modal',
                                                        'cancel-subscription-card-{{ $pendingRequest->id }}-{{ $plan->id }}'
                                                    )"
                                                >
                                                    <svg
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2"
                                                        class="h-4 w-4"
                                                        aria-hidden="true"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M6 6l12 12M18 6 6 18"
                                                        />
                                                    </svg>

                                                    <span>
                                                        Cancel pending change
                                                    </span>
                                                </button>

                                                <x-action-confirmation-modal
                                                    name="cancel-subscription-card-{{ $pendingRequest->id }}-{{ $plan->id }}"
                                                    title="Cancel pending plan change?"
                                                    message="The pending request and associated invoice will be cancelled. Your current subscription will remain active, and you may then choose another plan."
                                                    confirm-text="Cancel pending change"
                                                    variant="danger"
                                                />
                                            </form>
                                        @else
                                            <div
                                                class="rounded-xl border px-4 py-3 text-center shadow-sm"
                                                style="background-color:#fffbeb !important;border-color:#f59e0b !important;"
                                            >
                                                <div class="flex items-center justify-center gap-2">
                                                    <span
                                                        class="h-2.5 w-2.5 rounded-full"
                                                        style="background-color:#f59e0b !important;"
                                                        aria-hidden="true"
                                                    ></span>

                                                    <p
                                                        class="text-xs font-extrabold"
                                                        style="color:#92400e !important;"
                                                    >
                                                        Pending request active
                                                    </p>
                                                </div>

                                                <p
                                                    class="mt-1 text-xs font-semibold leading-5"
                                                    style="color:#b45309 !important;"
                                                >
                                                    Cancel it above before choosing this plan.
                                                </p>
                                            </div>
                                        @endif
                                    @else
                                        <p class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-center text-xs font-bold leading-5 text-slate-600">
                                            Payment verification is pending
                                        </p>
                                    @endif
                                @endif
                            @elseif (! $pricingConfigured)
                                <p class="rounded-xl bg-gray-100 px-4 py-3 text-center text-sm font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    Unavailable
                                </p>
                            @endif
                        </div>
                    </section>
                @empty
                    <section class="rounded-3xl border border-dashed border-gray-300 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-900">
                        <p class="font-bold text-gray-950 dark:text-white">
                            No subscription plans are currently available.
                        </p>
                    </section>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
