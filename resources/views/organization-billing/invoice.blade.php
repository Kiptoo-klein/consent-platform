<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Subscription Invoice
                </h1>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ $invoice->invoice_number }}
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route(
                        'organization-billing.invoices.download',
                        $invoice
                    ) }}"
                    class="inline-flex rounded-lg border border-indigo-700 bg-indigo-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-800"
                >
                    Download PDF
                </a>

                <a
                    href="{{ route('organization-billing.index') }}"
                    class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                >
                    Back to Billing
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            @php
                $paymentDetails =
                    $invoice->payment_details_snapshot
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

                $paymentMethodOptions =
                    array_keys(
                        array_filter([
                            'M-Pesa' =>
                                $mpesaEnabled,

                            'Bank Transfer' =>
                                $bankEnabled,
                        ])
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

                $workflowInvoice =
                    $invoice;

                $workflowPlanRequest =
                    $invoice->planRequest;

                $workflowInvoicePayable =
                    in_array(
                        $invoice->status,
                        [
                            \App\Enums\SubscriptionInvoiceStatus::
                                ISSUED,

                            \App\Enums\SubscriptionInvoiceStatus::
                                OVERDUE,
                        ],
                        true
                    )
                    && $invoice->paid_at === null;

                $successfulPaidTotal =
                    (float) $invoice
                        ->transactions
                        ->filter(
                            static function ($transaction): bool {
                                $status =
                                    $transaction->status;

                                $type =
                                    $transaction->type;

                                $statusValue =
                                    $status instanceof \BackedEnum
                                        ? $status->value
                                        : (string) $status;

                                $typeValue =
                                    $type instanceof \BackedEnum
                                        ? $type->value
                                        : (string) $type;

                                return $statusValue === 'successful'
                                    && in_array(
                                        $typeValue,
                                        [
                                            'payment',
                                            'renewal',
                                        ],
                                        true
                                    );
                            }
                        )
                        ->sum(
                            static fn ($transaction): float =>
                                (float) $transaction->amount
                        );

                $balanceDue =
                    max(
                        0,
                        round(
                            (float) $invoice->total_amount
                            - $successfulPaidTotal,
                            2
                        )
                    );

                $billingCycle =
                    $workflowPlanRequest?->billing_cycle
                    ?? $invoice->subscription?->billing_cycle;

                $billingCycleLabel =
                    match ($billingCycle) {
                        'monthly' => 'Monthly',
                        'annual' => 'Annual',
                        default => 'Subscription',
                    };

                $invoiceStatusValue =
                    $invoice->status instanceof \BackedEnum
                        ? $invoice->status->value
                        : (string) $invoice->status;

                [
                    $statusBackground,
                    $statusColor,
                    $statusBorder,
                    $statusDot,
                ] = match ($invoiceStatusValue) {
                    'paid' => [
                        '#ecfdf5',
                        '#065f46',
                        '#a7f3d0',
                        '#10b981',
                    ],

                    'overdue' => [
                        '#fef2f2',
                        '#991b1b',
                        '#fecaca',
                        '#ef4444',
                    ],

                    'issued' => [
                        '#eff6ff',
                        '#1e40af',
                        '#bfdbfe',
                        '#3b82f6',
                    ],

                    'void',
                    'voided',
                    'cancelled' => [
                        '#f8fafc',
                        '#475569',
                        '#cbd5e1',
                        '#94a3b8',
                    ],

                    default => [
                        '#fffbeb',
                        '#92400e',
                        '#fde68a',
                        '#f59e0b',
                    ],
                };

                $issuerLabel =
                    $invoice->issuedBy?->name
                    ?? 'System generated';

                $paymentStateLabel =
                    match (true) {
                        $invoice->paid_at !== null =>
                            'Payment confirmed',

                        $workflowPlanRequest
                            ?->hasPendingPaymentClaim() =>
                            'Verification pending',

                        $workflowPlanRequest
                            ?->payment_claim_status
                            === \App\Models\OrganizationSubscriptionPlanRequest::
                                PAYMENT_CLAIM_REJECTED =>
                            'Payment needs attention',

                        $workflowInvoicePayable =>
                            'Awaiting payment',

                        default =>
                            $invoice->status?->label()
                            ?? 'Invoice update',
                    };
            @endphp

            <section
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
                data-professional-invoice-overview
            >
                <div
                    class="relative overflow-hidden px-6 py-7 sm:px-8 sm:py-9"
                    style="background:linear-gradient(135deg,#0f172a 0%,#172554 62%,#312e81 100%);"
                >
                    <div
                        aria-hidden="true"
                        class="absolute -right-16 -top-20 h-56 w-56 rounded-full border"
                        style="border-color:rgba(255,255,255,.08);"
                    ></div>

                    <div
                        aria-hidden="true"
                        class="absolute -bottom-24 right-20 h-48 w-48 rounded-full"
                        style="background-color:rgba(99,102,241,.18);"
                    ></div>

                    <div class="relative grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end">
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <span
                                    class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-extrabold uppercase tracking-[0.16em]"
                                    style="
                                        background-color:{{ $statusBackground }};
                                        border-color:{{ $statusBorder }};
                                        color:{{ $statusColor }};
                                    "
                                >
                                    <span
                                        class="h-2 w-2 rounded-full"
                                        style="background-color:{{ $statusDot }};"
                                    ></span>

                                    {{ $invoice->status?->label() }}
                                </span>

                                <span
                                    class="text-xs font-bold uppercase tracking-[0.14em]"
                                    style="color:#cbd5e1;"
                                >
                                    {{ $paymentStateLabel }}
                                </span>
                            </div>

                            <p
                                class="mt-6 text-xs font-bold uppercase tracking-[0.2em]"
                                style="color:#a5b4fc;"
                            >
                                Subscription invoice
                            </p>

                            <h2
                                class="mt-2 break-all text-2xl font-black tracking-tight text-white sm:text-3xl"
                            >
                                {{ $invoice->invoice_number }}
                            </h2>

                            <div class="mt-6 flex flex-wrap gap-x-8 gap-y-3 text-sm">
                                <div>
                                    <span style="color:#94a3b8;">
                                        Organization
                                    </span>

                                    <span class="ml-2 font-bold text-white">
                                        {{ $invoice->organization->name }}
                                    </span>
                                </div>

                                <div>
                                    <span style="color:#94a3b8;">
                                        Plan
                                    </span>

                                    <span class="ml-2 font-bold text-white">
                                        {{ $invoice->plan->name }}
                                        · {{ $billingCycleLabel }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            class="min-w-[250px] rounded-2xl border p-5 text-left lg:text-right"
                            style="
                                background-color:rgba(255,255,255,.1);
                                border-color:rgba(255,255,255,.16);
                                backdrop-filter:blur(10px);
                            "
                        >
                            <p
                                class="text-xs font-bold uppercase tracking-[0.16em]"
                                style="color:#c7d2fe;"
                            >
                                {{ $invoice->paid_at ? 'Invoice total' : 'Amount due' }}
                            </p>

                            <p class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">
                                <span class="text-lg font-extrabold text-indigo-200">
                                    {{ $invoice->currency }}
                                </span>

                                {{ number_format(
                                    $invoice->paid_at
                                        ? (float) $invoice->total_amount
                                        : $balanceDue,
                                    2
                                ) }}
                            </p>

                            <p
                                class="mt-3 text-sm font-semibold"
                                style="color:#cbd5e1;"
                            >
                                @if ($invoice->paid_at)
                                    Paid
                                    {{ $invoice->paid_at->copy()->timezone(config('app.display_timezone'))->format(
                                        'M j, Y · g:i A'
                                    ) }}
                                @elseif ($invoice->due_date)
                                    Due
                                    {{ $invoice->due_date->copy()->timezone(config('app.display_timezone'))->format(
                                        'M j, Y'
                                    ) }}
                                @else
                                    Payment date pending
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid lg:grid-cols-[1.15fr_.85fr]">
                    <div class="border-b border-slate-200 p-6 sm:p-8 lg:border-b-0 lg:border-r dark:border-slate-800">
                        <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">
                            Invoice details
                        </p>

                        <dl class="mt-6 grid gap-x-8 gap-y-6 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-slate-500">
                                    Billed to
                                </dt>

                                <dd class="mt-1 text-base font-extrabold text-slate-950">
                                    {{ $invoice->organization->name }}
                                </dd>

                                @if ($invoice->organization->email)
                                    <dd class="mt-1 text-sm text-slate-500">
                                        {{ $invoice->organization->email }}
                                    </dd>
                                @endif
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-slate-500">
                                    Subscription
                                </dt>

                                <dd class="mt-1 text-base font-extrabold text-slate-950">
                                    {{ $invoice->plan->name }} Plan
                                </dd>

                                <dd class="mt-1 text-sm text-slate-500">
                                    {{ $billingCycleLabel }} billing
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-slate-500">
                                    Issue date
                                </dt>

                                <dd class="mt-1 font-extrabold text-slate-950">
                                    {{ $invoice->issue_date?->copy()?->timezone(config('app.display_timezone'))?->format(
                                        'M j, Y'
                                    ) ?? '—' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-slate-500">
                                    Due date
                                </dt>

                                <dd class="mt-1 font-extrabold text-slate-950">
                                    {{ $invoice->due_date?->copy()?->timezone(config('app.display_timezone'))?->format(
                                        'M j, Y'
                                    ) ?? '—' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-slate-500">
                                    Created by
                                </dt>

                                <dd class="mt-1 font-extrabold text-slate-950">
                                    {{ $issuerLabel }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-slate-500">
                                    Payment status
                                </dt>

                                <dd class="mt-1 font-extrabold text-slate-950">
                                    {{ $paymentStateLabel }}
                                </dd>
                            </div>
                        </dl>

                        @if ($invoice->notes)
                            <div class="mt-7 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" style="border-left:4px solid #6366f1;">
                                <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-slate-500">
                                    Notes
                                </p>

                                <p class="mt-2 whitespace-pre-line text-sm font-medium leading-6 text-slate-700">
                                    {{ $invoice->notes }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="p-6 sm:p-8" style="background-color:#f8fafc;">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">
                                Billing summary
                            </p>

                            <span class="text-xs font-bold text-slate-400">
                                {{ $invoice->currency }}
                            </span>
                        </div>

                        <dl class="mt-6 space-y-4">
                            <div class="flex items-center justify-between gap-6">
                                <dt class="text-sm font-medium text-slate-700">
                                    {{ $invoice->plan->name }}
                                    subscription
                                </dt>

                                <dd class="font-extrabold text-slate-950">
                                    {{ number_format(
                                        (float) $invoice->subtotal,
                                        2
                                    ) }}
                                </dd>
                            </div>

                            <div class="flex items-center justify-between gap-6">
                                <dt class="text-sm font-medium text-slate-700">
                                    Tax
                                </dt>

                                <dd class="font-extrabold text-slate-950">
                                    {{ number_format(
                                        (float) $invoice->tax_amount,
                                        2
                                    ) }}
                                </dd>
                            </div>

                            @if ($successfulPaidTotal > 0)
                                <div class="flex items-center justify-between gap-6">
                                    <dt class="text-sm font-semibold text-emerald-700">
                                        Payments received
                                    </dt>

                                    <dd class="font-extrabold text-emerald-700">
                                        -
                                        {{ number_format(
                                            $successfulPaidTotal,
                                            2
                                        ) }}
                                    </dd>
                                </div>
                            @endif
                        </dl>

                        <div class="my-6 border-t border-dashed border-slate-300"></div>

                        <div class="flex items-end justify-between gap-6">
                            <div>
                                <p class="text-sm font-extrabold text-slate-800">
                                    {{ $invoice->paid_at
                                        ? 'Total paid'
                                        : 'Balance due' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $billingCycleLabel }} subscription
                                </p>
                            </div>

                            <p class="text-right text-2xl font-black text-slate-950">
                                <span class="text-sm font-extrabold text-indigo-700">
                                    {{ $invoice->currency }}
                                </span>

                                {{ number_format(
                                    $invoice->paid_at
                                        ? (float) $invoice->total_amount
                                        : $balanceDue,
                                    2
                                ) }}
                            </p>
                        </div>

                        <div
                            class="mt-7 rounded-2xl border p-4"
                            style="
                                background-color:{{ $statusBackground }};
                                border-color:{{ $statusBorder }};
                                color:{{ $statusColor }};
                            "
                        >
                            <div class="flex items-start gap-3">
                                <span
                                    class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full"
                                    style="background-color:{{ $statusDot }};"
                                ></span>

                                <div>
                                    <p class="text-sm font-extrabold">
                                        {{ $paymentStateLabel }}
                                    </p>

                                    <p class="mt-1 text-xs font-semibold leading-5 opacity-80">
                                        @if ($invoice->paid_at)
                                            Payment has been confirmed and linked
                                            to this invoice.
                                        @elseif (
                                            $workflowPlanRequest
                                                ?->hasPendingPaymentClaim()
                                        )
                                            Your payment report is awaiting
                                            verification by Platform Billing.
                                        @elseif ($workflowInvoicePayable)
                                            Follow the payment instructions below,
                                            then report the completed payment.
                                        @else
                                            This invoice is no longer awaiting
                                            payment.
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            @if (
                $workflowInvoicePayable
                && $workflowPlanRequest
            )
                <section
                    class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
                    data-subscription-payment-claim
                >
                    @if (
                        $workflowPlanRequest
                            ->hasPendingPaymentClaim()
                    )
                        <div class="border-b border-blue-200 px-6 py-5 sm:px-8" style="background:linear-gradient(135deg,#eff6ff 0%,#eef2ff 100%);">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-start gap-4">
                                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-sm">
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            class="h-5 w-5"
                                            aria-hidden="true"
                                        >
                                            <path d="M12 8v4l3 2"></path>
                                            <circle cx="12" cy="12" r="9"></circle>
                                        </svg>
                                    </span>

                                    <div>
                                        <p class="text-xs font-extrabold uppercase tracking-[0.15em] text-blue-700">
                                            Payment submitted
                                        </p>

                                        <h2 class="mt-1 text-xl font-black text-blue-950">
                                            Verification in progress
                                        </h2>

                                        <p class="mt-1 text-sm font-semibold text-blue-800">
                                            Platform Billing has been notified.
                                            Your plan activates after verification.
                                        </p>
                                    </div>
                                </div>

                                <span class="inline-flex w-fit items-center gap-2 rounded-full border border-blue-300 bg-white px-3 py-1.5 text-xs font-extrabold text-blue-800 shadow-sm">
                                    <span class="h-2 w-2 animate-pulse rounded-full bg-blue-500"></span>
                                    Pending review
                                </span>
                            </div>
                        </div>

                        <div class="p-6 sm:p-8">
                            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach ([
                                    [
                                        'label' => 'Reference',
                                        'value' =>
                                            $workflowPlanRequest
                                                ->payment_claim_reference,
                                    ],
                                    [
                                        'label' => 'Method',
                                        'value' =>
                                            $workflowPlanRequest
                                                ->payment_claim_method,
                                    ],
                                    [
                                        'label' => 'Amount reported',
                                        'value' =>
                                            $workflowPlanRequest
                                                ->payment_claim_currency
                                            .' '
                                            .number_format(
                                                (float) $workflowPlanRequest
                                                    ->payment_claim_amount,
                                                2
                                            ),
                                    ],
                                    [
                                        'label' => 'Payment time',
                                        'value' =>
                                            $workflowPlanRequest
                                                ->payment_claim_paid_at
                                                ?->copy()?->timezone(config('app.display_timezone'))?->format(
                                                    'M j, Y · g:i A T'
                                                )
                                            ?? '—',
                                    ],
                                ] as $item)
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                        <dt class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">
                                            {{ $item['label'] }}
                                        </dt>

                                        <dd class="mt-2 break-words font-extrabold text-slate-950">
                                            {{ $item['value'] }}
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>

                            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">
                                    <p class="text-xs font-extrabold text-blue-900">
                                        1. Payment reported
                                    </p>
                                </div>

                                <div class="rounded-xl border border-blue-500 bg-blue-600 px-4 py-3 text-white shadow-sm">
                                    <p class="text-xs font-extrabold">
                                        2. Verification
                                    </p>
                                </div>

                                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-slate-600 shadow-sm">
                                    <p class="text-xs font-extrabold">
                                        3. Plan activation
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div>
                            <div class="p-6 sm:p-8">
                                @if (
                                    $workflowPlanRequest
                                        ->payment_claim_status
                                    === \App\Models\OrganizationSubscriptionPlanRequest::
                                        PAYMENT_CLAIM_REJECTED
                                )
                                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5 text-red-900 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-100">
                                        <p class="text-xs font-extrabold uppercase tracking-[0.14em]">
                                            Verification unsuccessful
                                        </p>

                                        <h2 class="mt-1 text-lg font-black">
                                            Payment was not verified
                                        </h2>

                                        <p class="mt-2 text-sm font-semibold leading-6">
                                            {{ $workflowPlanRequest->payment_claim_rejection_reason }}
                                        </p>
                                    </div>
                                @endif

                                <div class="flex items-start gap-4">
                                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-sm">
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            class="h-5 w-5"
                                            aria-hidden="true"
                                        >
                                            <path d="M5 12l4 4L19 6"></path>
                                        </svg>
                                    </span>

                                    <div>
                                        <p class="text-xs font-extrabold uppercase tracking-[0.15em] text-emerald-700 dark:text-emerald-400">
                                            Completed your payment?
                                        </p>

                                        <h2 class="mt-1 text-xl font-black text-slate-950 dark:text-white">
                                            Report payment for verification
                                        </h2>

                                        <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-400">
                                            Enter the exact transaction details.
                                            This does not activate the plan until
                                            Platform Billing confirms the payment.
                                        </p>
                                    </div>
                                </div>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'organization-subscription-payment-claims.store',
                                        $workflowPlanRequest
                                    ) }}"
                                    class="mt-7 grid gap-5 sm:grid-cols-2"
                                >
                                    @csrf

                                    <div>
                                        <label
                                            for="payment_method"
                                            class="block text-sm font-bold text-slate-800 dark:text-slate-200"
                                        >
                                            Payment method
                                        </label>

                                        <select
                                            id="payment_method"
                                            name="payment_method"
                                            required
                                            class="mt-2 block w-full rounded-xl border-slate-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950"
                                        >
                                            <option value="">
                                                Select payment method
                                            </option>

                                            @forelse (
                                                $paymentMethodOptions
                                                as $paymentMethod
                                            )
                                                <option
                                                    value="{{ $paymentMethod }}"
                                                    @selected(
                                                        old('payment_method')
                                                        === $paymentMethod
                                                    )
                                                >
                                                    {{ $paymentMethod }}
                                                </option>
                                            @empty
                                                <option
                                                    value=""
                                                    disabled
                                                >
                                                    No payment methods configured
                                                </option>
                                            @endforelse
                                        </select>

                                        @error('payment_method')
                                            <p class="mt-1 text-sm font-semibold text-red-700">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            for="reference"
                                            class="block text-sm font-bold text-slate-800 dark:text-slate-200"
                                        >
                                            Transaction reference
                                        </label>

                                        <input
                                            id="reference"
                                            name="reference"
                                            type="text"
                                            maxlength="120"
                                            required
                                            value="{{ old('reference') }}"
                                            placeholder="Enter the confirmation reference"
                                            class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950"
                                        >

                                        @error('reference')
                                            <p class="mt-1 text-sm font-semibold text-red-700">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            for="amount"
                                            class="block text-sm font-bold text-slate-800 dark:text-slate-200"
                                        >
                                            Amount paid
                                        </label>

                                        <div class="relative mt-2">
                                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-extrabold text-slate-500">
                                                {{ $workflowInvoice->currency }}
                                            </span>

                                            <input
                                                id="amount"
                                                name="amount"
                                                type="number"
                                                min="0.01"
                                                step="0.01"
                                                required
                                                value="{{ old(
                                                    'amount',
                                                    number_format(
                                                        (float) $workflowInvoice->total_amount,
                                                        2,
                                                        '.',
                                                        ''
                                                    )
                                                ) }}"
                                                class="block w-full rounded-xl border-slate-300 pl-14 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950"
                                            >
                                        </div>

                                        @error('amount')
                                            <p class="mt-1 text-sm font-semibold text-red-700">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            for="paid_at"
                                            class="block text-sm font-bold text-slate-800 dark:text-slate-200"
                                        >
                                            Exact payment date and time
                                        </label>

                                        <input
                                            id="paid_at"
                                            name="paid_at"
                                            type="datetime-local"
                                            required
                                            value="{{ old(
                                                'paid_at',
                                                now()->format(
                                                    'Y-m-d\\TH:i'
                                                )
                                            ) }}"
                                            class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950"
                                        >

                                        @error('paid_at')
                                            <p class="mt-1 text-sm font-semibold text-red-700">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <label
                                            for="notes"
                                            class="block text-sm font-bold text-slate-800 dark:text-slate-200"
                                        >
                                            Notes
                                            <span class="font-normal text-slate-500">
                                                (optional)
                                            </span>
                                        </label>

                                        <textarea
                                            id="notes"
                                            name="notes"
                                            rows="3"
                                            maxlength="2000"
                                            placeholder="Add any details that may help Platform Billing verify the payment."
                                            class="mt-2 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950"
                                        >{{ old('notes') }}</textarea>

                                        @error('notes')
                                            <p class="mt-1 text-sm font-semibold text-red-700">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <button
                                            type="submit"
                                            class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto"
                                        >
                                            Submit Payment for Verification
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div
                                class="border-t px-6 py-5 sm:px-8 sm:py-6"
                                style="background-color:#f8fafc !important;border-color:#e2e8f0 !important;"
                                data-invoice-cancel-request-panel
                            >
                                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                                    <div
                                        class="min-w-0 border-l-4 pl-4"
                                        style="border-color:#dc2626 !important;"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
                                                style="background-color:#fef2f2 !important;color:#b91c1c !important;"
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
                                            </span>

                                            <h2
                                                class="text-base font-extrabold"
                                                style="color:#111827 !important;"
                                            >
                                                Cancel this plan change
                                            </h2>
                                        </div>

                                        <p
                                            class="mt-2 max-w-3xl text-sm font-medium leading-6"
                                            style="color:#475569 !important;"
                                        >
                                            Cancel before reporting payment. The
                                            unpaid invoice will be cancelled, this
                                            plan will not be activated, and your
                                            current subscription will remain active.
                                        </p>

                                        <p
                                            class="mt-1 text-xs font-semibold"
                                            style="color:#64748b !important;"
                                        >
                                            Available until payment is reported.
                                        </p>
                                    </div>

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'organization-subscription-payment-claims.cancel',
                                            $workflowPlanRequest
                                        ) }}"
                                        class="shrink-0"
                                        onsubmit="return confirm('Cancel this subscription request and invoice? Your current subscription will remain unchanged.');"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="inline-flex w-full items-center justify-center rounded-xl border px-5 py-3 text-sm font-extrabold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 lg:w-auto text-white hover:opacity-90"
                                            style="background-color:#b91c1c !important;color:#ffffff !important;border-color:#991b1b !important;"
                                        >
                                            Cancel Request
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </section>
            @endif

            <section
                class="overflow-hidden rounded-3xl border-2 shadow-sm"
                style="background-color:#ffffff;border-color:#22c55e;"
            >
                <div
                    class="border-b px-6 py-5 sm:px-8"
                    style="background-color:#f0fdf4;border-color:#86efac;"
                >
                    <h2
                        class="text-xl font-extrabold"
                        style="color:#14532d;"
                    >
                        Payment Instructions
                    </h2>

                    <p
                        class="mt-1 text-sm font-semibold"
                        style="color:#166534;"
                    >
                        Use only the payment information attached to this
                        issued invoice.
                    </p>
                </div>

                <div class="p-6 sm:p-8">
                    @if ($hasPaymentInformation)
                        <div class="grid gap-6 lg:grid-cols-2">
                            @if ($mpesaEnabled)
                                <div
                                    class="rounded-2xl border p-5"
                                    style="background-color:#f0fdf4;border-color:#86efac;"
                                >
                                    <h3
                                        class="text-lg font-extrabold"
                                        style="color:#14532d;"
                                    >
                                        M-Pesa
                                    </h3>

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
                                                class="mt-1 text-lg font-extrabold"
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
                                    class="rounded-2xl border p-5"
                                    style="background-color:#eff6ff;border-color:#93c5fd;"
                                >
                                    <h3
                                        class="text-lg font-extrabold"
                                        style="color:#1e3a8a;"
                                    >
                                        Bank Transfer
                                    </h3>

                                    <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
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
                                class="mt-6 rounded-2xl border p-5"
                                style="background-color:#f9fafb;border-color:#d1d5db;"
                            >
                                <h3
                                    class="font-extrabold"
                                    style="color:#111827;"
                                >
                                    Billing Contact
                                </h3>

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
                            class="rounded-xl border p-4 text-sm font-bold leading-6"
                            style="background-color:#fff7ed;color:#9a3412;border-color:#fdba74;"
                        >
                            Payment details were not attached to this
                            invoice. Contact Platform Billing before
                            sending payment.
                        </div>
                    @endif
                </div>
            </section>

            @include(
                'subscription-invoices.partials.reminder-history',
                [
                    'notifications' =>
                        $invoice->reminderNotifications,

                    'showFailureDetails' =>
                        false,
                ]
            )

            <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        Linked Payments
                    </h2>
                </div>

                @if ($invoice->transactions->isEmpty())
                    <div class="p-6 text-sm text-gray-600 dark:text-gray-400">
                        No transactions are linked to this invoice.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Reference
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Status
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Amount
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                @foreach ($invoice->transactions as $transaction)
                                    <tr>
                                        <td class="px-5 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $transaction->reference }}
                                        </td>

                                        <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $transaction->status?->label() }}
                                        </td>

                                        <td class="px-5 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $transaction->currency }}
                                            {{ number_format(
                                                (float) $transaction->amount,
                                                2
                                            ) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>


</x-app-layout>
