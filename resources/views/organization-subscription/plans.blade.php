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
        $currentPlan =
            $subscription?->plan;

        $currentPlanId =
            $subscription
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
                            Current subscription
                        </p>

                        <h2 class="mt-3 text-3xl font-extrabold">
                            {{ $currentPlan?->name
                                ?? 'No active plan' }}
                        </h2>

                        @if ($subscription)
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
                            Selecting another plan creates a pending request
                            and a draft invoice. Your current plan remains
                            unchanged until Platform Billing confirms payment.
                        </p>
                    </div>

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

                @if ($isDraftInvoice)
                    <section
                        class="rounded-3xl border-2 p-6 shadow-sm sm:p-7"
                        style="background-color:#fffbeb;border-color:#f59e0b;"
                    >
                        <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                            <div class="max-w-2xl">
                                <div
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold uppercase tracking-wide"
                                    style="background-color:#fef3c7;color:#78350f;"
                                >
                                    Awaiting Platform Billing review
                                </div>

                                <h2
                                    class="mt-4 text-2xl font-extrabold"
                                    style="color:#111827;"
                                >
                                    {{ $pendingRequest
                                        ->requestedPlan
                                        ?->name }}
                                    —
                                    {{ $formatCycle(
                                        $pendingRequest
                                            ->billing_cycle
                                    ) }}
                                </h2>

                                <p
                                    class="mt-3 text-sm font-semibold leading-6"
                                    style="color:#374151;"
                                >
                                    Requested by
                                    {{ $pendingRequest
                                        ->requestedBy
                                        ?->name
                                        ?? 'an organization user' }}
                                    on
                                    {{ $pendingRequest
                                        ->requested_at
                                        ?->format('M d, Y H:i') }}.
                                </p>

                                <p
                                    class="mt-4 text-sm font-bold leading-6"
                                    style="color:#78350f;"
                                >
                                    Platform Billing must review this request
                                    and issue the invoice before payment can
                                    be made.
                                </p>
                            </div>

                            <div
                                class="w-full rounded-2xl border-2 p-5 shadow-sm xl:max-w-md"
                                style="background-color:#ffffff;border-color:#d1d5db;"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p
                                            class="text-xs font-extrabold uppercase tracking-wide"
                                            style="color:#4b5563;"
                                        >
                                            Draft invoice prepared
                                        </p>

                                        <p
                                            class="mt-2 text-lg font-extrabold"
                                            style="color:#111827;"
                                        >
                                            {{ $pendingInvoice
                                                ?->invoice_number
                                                ?? 'Invoice number pending' }}
                                        </p>
                                    </div>

                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-extrabold"
                                        style="background-color:#e5e7eb;color:#111827;"
                                    >
                                        Draft - not yet payable
                                    </span>
                                </div>

                                <div
                                    class="mt-5 border-t pt-5"
                                    style="border-color:#e5e7eb;"
                                >
                                    <p
                                        class="text-xs font-extrabold uppercase tracking-wide"
                                        style="color:#4b5563;"
                                    >
                                        Invoice amount
                                    </p>

                                    <p
                                        class="mt-2 text-2xl font-extrabold"
                                        style="color:#111827;"
                                    >
                                        {{ $pendingRequest->currency }}
                                        {{ number_format(
                                            (float) $pendingRequest
                                                ->amount_snapshot,
                                            2
                                        ) }}
                                    </p>
                                </div>

                                <div
                                    class="mt-5 rounded-xl p-4 text-sm font-semibold leading-6"
                                    style="background-color:#f3f4f6;color:#374151;"
                                >
                                    Continue to payment to issue this invoice,
                                    set its due date, and display the payment
                                    instructions and PDF download.
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-6 rounded-xl border p-4 text-sm font-bold leading-6"
                            style="background-color:#ecfdf5;color:#065f46;border-color:#6ee7b7;"
                        >
                            Your current subscription remains active while
                            this plan request is being reviewed.
                        </div>

                        <div
                            class="mt-6 flex flex-col gap-4 rounded-2xl border p-5 sm:flex-row sm:items-center sm:justify-between"
                            style="background-color:#eff6ff;border-color:#93c5fd;"
                        >
                            <div>
                                <p
                                    class="text-sm font-extrabold"
                                    style="color:#1e3a8a;"
                                >
                                    Ready to pay?
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold leading-6"
                                    style="color:#1e40af;"
                                >
                                    Issue the invoice to see its due date,
                                    payment instructions, and PDF download.
                                </p>
                            </div>

                            <form
                                method="POST"
                                action="{{ route(
                                    'organization-subscription-plans.payment',
                                    $pendingRequest
                                ) }}"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="inline-flex w-full items-center justify-center rounded-xl border px-5 py-3 text-sm font-extrabold shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:w-auto"
                                    style="background-color:#1d4ed8 !important;color:#ffffff !important;border-color:#1e40af !important;"
                                >
                                    Continue to Payment
                                </button>
                            </form>
                        </div>

                        @if (
                            $pendingRequest
                                ->canBeCancelledByOrganization()
                        )
                            <div
                                class="mt-6 flex flex-col gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-between"
                                style="border-color:#fcd34d;"
                            >
                                <p
                                    class="text-sm font-semibold leading-6"
                                    style="color:#78350f;"
                                >
                                    Selected the wrong plan or billing
                                    cycle? You may cancel while the invoice
                                    is still a draft.
                                </p>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'organization-subscription-plans.cancel',
                                        $pendingRequest
                                    ) }}"
                                    onsubmit="return confirm('Cancel this plan request? The draft invoice will also be cancelled, and your current subscription will remain unchanged.');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-xl border px-5 py-2.5 text-sm font-extrabold shadow-sm transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                        style="background-color:#b91c1c !important;color:#ffffff !important;border-color:#991b1b !important;"
                                    >
                                        Cancel Request
                                    </button>
                                </form>
                            </div>
                        @endif
                    </section>
                @elseif ($isPayableInvoice)
                    <section
                        class="overflow-hidden rounded-3xl border-2 shadow-sm"
                        style="background-color:#ffffff;border-color:#dc2626;"
                    >
                        <div
                            class="px-6 py-6 sm:px-8"
                            style="background-color:#7f1d1d;color:#ffffff;"
                        >
                            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <div
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold uppercase tracking-wide"
                                        style="background-color:#fee2e2;color:#7f1d1d;"
                                    >
                                        @if (
                                            $pendingInvoiceStatus
                                            === \App\Enums\SubscriptionInvoiceStatus::
                                                OVERDUE
                                        )
                                            Invoice overdue — payment required
                                        @else
                                            Invoice issued — payment required
                                        @endif
                                    </div>

                                    <h2 class="mt-4 text-2xl font-extrabold">
                                        {{ $pendingRequest
                                            ->requestedPlan
                                            ?->name }}
                                        —
                                        {{ $formatCycle(
                                            $pendingRequest
                                                ->billing_cycle
                                        ) }}
                                    </h2>

                                    <p class="mt-3 max-w-2xl text-sm font-semibold leading-6 text-red-50">
                                        Platform Billing has issued the
                                        subscription invoice. Complete
                                        payment using one of the approved
                                        methods below.
                                    </p>
                                </div>

                                <span
                                    class="inline-flex w-fit rounded-full px-3 py-1 text-sm font-extrabold"
                                    style="background-color:#ffffff;color:#7f1d1d;"
                                >
                                    {{ $pendingInvoiceStatus?->label() }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-6 p-6 sm:p-8">
                            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                @foreach ([
                                    [
                                        'label' => 'Invoice number',
                                        'value' => $pendingInvoice
                                            ?->invoice_number
                                            ?? 'Unavailable',
                                    ],
                                    [
                                        'label' => 'Amount payable',
                                        'value' => $pendingInvoice
                                            ?->currency
                                            .' '
                                            .number_format(
                                                (float) $pendingInvoice
                                                    ?->total_amount,
                                                2
                                            ),
                                    ],
                                    [
                                        'label' => 'Issue date',
                                        'value' => $pendingInvoice
                                            ?->issue_date
                                            ?->format('M d, Y')
                                            ?? 'Unavailable',
                                    ],
                                    [
                                        'label' => 'Due date',
                                        'value' => $pendingInvoice
                                            ?->due_date
                                            ?->format('M d, Y')
                                            ?? 'Unavailable',
                                    ],
                                ] as $item)
                                    <div
                                        class="rounded-2xl border p-5"
                                        style="background-color:#f9fafb;border-color:#d1d5db;"
                                    >
                                        <p
                                            class="text-xs font-extrabold uppercase tracking-wide"
                                            style="color:#4b5563;"
                                        >
                                            {{ $item['label'] }}
                                        </p>

                                        <p
                                            class="mt-2 font-extrabold"
                                            style="color:#111827;"
                                        >
                                            {{ $item['value'] }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <a
                                    href="{{ route(
                                        'organization-billing.invoices.show',
                                        $pendingInvoice
                                    ) }}"
                                    class="inline-flex items-center justify-center rounded-xl border px-5 py-2.5 text-sm font-extrabold shadow-sm transition hover:opacity-90"
                                    style="background-color:#1d4ed8 !important;color:#ffffff !important;border-color:#1e40af !important;"
                                >
                                    View Invoice
                                </a>

                                <a
                                    href="{{ route(
                                        'organization-billing.invoices.download',
                                        $pendingInvoice
                                    ) }}"
                                    class="inline-flex items-center justify-center rounded-xl border px-5 py-2.5 text-sm font-extrabold shadow-sm transition hover:opacity-90"
                                    style="background-color:#4338ca !important;color:#ffffff !important;border-color:#3730a3 !important;"
                                >
                                    Download PDF
                                </a>
                            </div>

                            <section
                                class="rounded-2xl border-2 p-5 sm:p-6"
                                style="background-color:#f0fdf4;border-color:#22c55e;"
                            >
                                <h3
                                    class="text-lg font-extrabold"
                                    style="color:#14532d;"
                                >
                                    Payment Instructions
                                </h3>

                                @if ($hasPaymentInformation)
                                    <div class="mt-5 grid gap-5 lg:grid-cols-2">
                                        @if ($mpesaEnabled)
                                            <div
                                                class="rounded-xl border p-5"
                                                style="background-color:#ffffff;border-color:#86efac;"
                                            >
                                                <h4
                                                    class="font-extrabold"
                                                    style="color:#14532d;"
                                                >
                                                    M-Pesa
                                                </h4>

                                                <dl class="mt-4 space-y-3 text-sm">
                                                    <div>
                                                        <dt
                                                            class="font-bold"
                                                            style="color:#4b5563;"
                                                        >
                                                            Payment type
                                                        </dt>

                                                        <dd
                                                            class="mt-1 font-extrabold"
                                                            style="color:#111827;"
                                                        >
                                                            {{ ucfirst(
                                                                (string) data_get(
                                                                    $paymentDetails,
                                                                    'mpesa_type',
                                                                    'M-Pesa'
                                                                )
                                                            ) }}
                                                        </dd>
                                                    </div>

                                                    <div>
                                                        <dt
                                                            class="font-bold"
                                                            style="color:#4b5563;"
                                                        >
                                                            Paybill or Till number
                                                        </dt>

                                                        <dd
                                                            class="mt-1 font-extrabold"
                                                            style="color:#111827;"
                                                        >
                                                            {{ data_get(
                                                                $paymentDetails,
                                                                'mpesa_business_number',
                                                                'Unavailable'
                                                            ) }}
                                                        </dd>
                                                    </div>
                                                </dl>

                                                @if (
                                                    filled(
                                                        data_get(
                                                            $paymentDetails,
                                                            'mpesa_account_reference_instructions'
                                                        )
                                                    )
                                                )
                                                    <p
                                                        class="mt-4 whitespace-pre-line text-sm font-semibold leading-6"
                                                        style="color:#374151;"
                                                    >
                                                        {{ data_get(
                                                            $paymentDetails,
                                                            'mpesa_account_reference_instructions'
                                                        ) }}
                                                    </p>
                                                @endif

                                                @if (
                                                    filled(
                                                        data_get(
                                                            $paymentDetails,
                                                            'mpesa_instructions'
                                                        )
                                                    )
                                                )
                                                    <p
                                                        class="mt-3 whitespace-pre-line text-sm leading-6"
                                                        style="color:#374151;"
                                                    >
                                                        {{ data_get(
                                                            $paymentDetails,
                                                            'mpesa_instructions'
                                                        ) }}
                                                    </p>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($bankEnabled)
                                            <div
                                                class="rounded-xl border p-5"
                                                style="background-color:#ffffff;border-color:#bfdbfe;"
                                            >
                                                <h4
                                                    class="font-extrabold"
                                                    style="color:#1e3a8a;"
                                                >
                                                    Bank Transfer
                                                </h4>

                                                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                                                    @foreach ([
                                                        'Bank' =>
                                                            data_get(
                                                                $paymentDetails,
                                                                'bank_name'
                                                            ),

                                                        'Account name' =>
                                                            data_get(
                                                                $paymentDetails,
                                                                'bank_account_name'
                                                            ),

                                                        'Account number' =>
                                                            data_get(
                                                                $paymentDetails,
                                                                'bank_account_number'
                                                            ),

                                                        'Branch' =>
                                                            data_get(
                                                                $paymentDetails,
                                                                'bank_branch'
                                                            ),

                                                        'SWIFT / BIC' =>
                                                            data_get(
                                                                $paymentDetails,
                                                                'bank_swift_code'
                                                            ),
                                                    ] as $label => $value)
                                                        @if (filled($value))
                                                            <div>
                                                                <dt
                                                                    class="font-bold"
                                                                    style="color:#4b5563;"
                                                                >
                                                                    {{ $label }}
                                                                </dt>

                                                                <dd
                                                                    class="mt-1 break-words font-extrabold"
                                                                    style="color:#111827;"
                                                                >
                                                                    {{ $value }}
                                                                </dd>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </dl>

                                                @if (
                                                    filled(
                                                        data_get(
                                                            $paymentDetails,
                                                            'bank_reference_instructions'
                                                        )
                                                    )
                                                )
                                                    <p
                                                        class="mt-4 whitespace-pre-line text-sm font-semibold leading-6"
                                                        style="color:#374151;"
                                                    >
                                                        {{ data_get(
                                                            $paymentDetails,
                                                            'bank_reference_instructions'
                                                        ) }}
                                                    </p>
                                                @endif

                                                @if (
                                                    filled(
                                                        data_get(
                                                            $paymentDetails,
                                                            'bank_instructions'
                                                        )
                                                    )
                                                )
                                                    <p
                                                        class="mt-3 whitespace-pre-line text-sm leading-6"
                                                        style="color:#374151;"
                                                    >
                                                        {{ data_get(
                                                            $paymentDetails,
                                                            'bank_instructions'
                                                        ) }}
                                                    </p>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    @if (
                                        filled($billingEmail)
                                        || filled($billingPhone)
                                        || filled(
                                            $additionalInstructions
                                        )
                                    )
                                        <div
                                            class="mt-5 rounded-xl border p-5"
                                            style="background-color:#ffffff;border-color:#d1d5db;"
                                        >
                                            <h4
                                                class="font-extrabold"
                                                style="color:#111827;"
                                            >
                                                Billing Contact
                                            </h4>

                                            @if (filled($billingEmail))
                                                <p
                                                    class="mt-3 text-sm font-semibold"
                                                    style="color:#374151;"
                                                >
                                                    Email:
                                                    {{ $billingEmail }}
                                                </p>
                                            @endif

                                            @if (filled($billingPhone))
                                                <p
                                                    class="mt-2 text-sm font-semibold"
                                                    style="color:#374151;"
                                                >
                                                    Phone:
                                                    {{ $billingPhone }}
                                                </p>
                                            @endif

                                            @if (
                                                filled(
                                                    $additionalInstructions
                                                )
                                            )
                                                <p
                                                    class="mt-3 whitespace-pre-line text-sm leading-6"
                                                    style="color:#374151;"
                                                >
                                                    {{ $additionalInstructions }}
                                                </p>
                                            @endif
                                        </div>
                                    @endif
                                @else
                                    <div
                                        class="mt-4 rounded-xl border p-4 text-sm font-bold leading-6"
                                        style="background-color:#fff7ed;color:#9a3412;border-color:#fdba74;"
                                    >
                                        Payment details were not attached
                                        to this invoice. Contact Platform
                                        Billing before sending payment.
                                    </div>
                                @endif
                            </section>

                            <div
                                class="rounded-xl border p-4 text-sm font-bold leading-6"
                                style="background-color:#ecfdf5;color:#065f46;border-color:#6ee7b7;"
                            >
                                Your current subscription remains active
                                until payment is confirmed and the requested
                                plan is activated.
                            </div>
                        </div>
                    </section>
                @else
                    <section
                        class="rounded-3xl border-2 p-6 shadow-sm"
                        style="background-color:#f9fafb;border-color:#9ca3af;"
                    >
                        <span
                            class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold uppercase tracking-wide"
                            style="background-color:#e5e7eb;color:#111827;"
                        >
                            {{ $pendingInvoiceStatus?->label()
                                ?? 'Processing' }}
                        </span>

                        <h2
                            class="mt-4 text-xl font-extrabold"
                            style="color:#111827;"
                        >
                            Platform Billing is processing this request
                        </h2>

                        <p
                            class="mt-2 text-sm font-semibold leading-6"
                            style="color:#4b5563;"
                        >
                            The current subscription remains unchanged
                            until the plan request is completed.
                        </p>
                    </section>
                @endif
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
                                                'organization-subscription-plans.request',
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
                                        <div class="rounded-xl bg-teal-100 px-4 py-3 text-center text-sm font-bold text-teal-900 dark:bg-teal-950 dark:text-teal-200">
                                            Current monthly plan
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
                                                    'organization-subscription-plans.request',
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
                                            <div class="rounded-xl bg-indigo-100 px-4 py-3 text-center text-sm font-bold text-indigo-900 dark:bg-indigo-950 dark:text-indigo-200">
                                                Current annual plan
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            @elseif ($pendingRequest)
                                <p class="rounded-xl bg-amber-100 px-4 py-3 text-center text-sm font-bold text-amber-900 dark:bg-amber-950 dark:text-amber-200">
                                    Request pending
                                </p>
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
