<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-indigo-700">
                    Platform billing
                </p>

                <h1 class="mt-1 text-2xl font-black text-slate-950">
                    Subscription Invoice
                </h1>

                <p class="mt-1 text-sm font-medium text-slate-600">
                    {{ $invoice->invoice_number }}
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('platform.billing.index') }}"
                    class="inline-flex rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-extrabold text-slate-700 shadow-sm hover:bg-slate-50"
                >
                    Billing Management
                </a>

                <a
                    href="{{ route(
                        'platform.organizations.subscription-invoices.index',
                        $organization
                    ) }}"
                    class="inline-flex rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-white shadow-sm hover:bg-slate-800"
                >
                    Back to Invoices
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $workflowRequest =
            $invoice->planRequest
            ?? $planRequest;

        $workflowClaimPending =
            $workflowRequest?->hasPendingPaymentClaim()
            ?? false;

        $workflowClaimRejected =
            $workflowRequest?->payment_claim_status
            === \App\Models\OrganizationSubscriptionPlanRequest::
                PAYMENT_CLAIM_REJECTED;

        $statusValue =
            $invoice->status?->value;

        [
            $statusBackground,
            $statusColor,
            $statusBorder,
            $statusDot,
        ] = match ($statusValue) {
            'paid' => [
                '#ecfdf5',
                '#065f46',
                '#a7f3d0',
                '#10b981',
            ],

            'issued' => [
                '#eff6ff',
                '#1e40af',
                '#bfdbfe',
                '#3b82f6',
            ],

            'overdue' => [
                '#fef2f2',
                '#991b1b',
                '#fecaca',
                '#ef4444',
            ],

            'draft' => [
                '#fffbeb',
                '#92400e',
                '#fde68a',
                '#f59e0b',
            ],

            default => [
                '#f8fafc',
                '#475569',
                '#cbd5e1',
                '#94a3b8',
            ],
        };

        $billingCycle =
            $workflowRequest?->billing_cycle
            ?? $invoice->subscription?->billing_cycle;

        $billingCycleLabel =
            match ($billingCycle) {
                'monthly' => 'Monthly',
                'annual' => 'Annual',
                default => 'Subscription',
            };

        $issuerLabel =
            $invoice->issuedBy?->name
            ?? 'System generated';

        $paymentStateLabel =
            match (true) {
                $invoice->paid_at !== null =>
                    'Payment confirmed',

                $workflowClaimPending =>
                    'Verification required',

                $workflowClaimRejected =>
                    'Payment report rejected',

                $invoice->status?->isOutstanding() =>
                    'Awaiting payment',

                $statusValue === 'draft' =>
                    'Draft invoice',

                default =>
                    $invoice->status?->label()
                    ?? 'Invoice update',
            };

        $showManualPaymentForm =
            $canRecordInvoicePayment
            && ! $workflowClaimPending;
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl space-y-7 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <section
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"
                data-platform-professional-invoice
            >
                <div
                    class="relative overflow-hidden px-6 py-7 text-white sm:px-8 sm:py-9"
                    style="background:linear-gradient(135deg,#0f172a 0%,#172554 62%,#312e81 100%);"
                >
                    <div
                        aria-hidden="true"
                        class="absolute -right-16 -top-20 h-56 w-56 rounded-full border"
                        style="border-color:rgba(255,255,255,.08);"
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

                                <span class="text-xs font-bold uppercase tracking-[0.14em] text-slate-300">
                                    {{ $paymentStateLabel }}
                                </span>
                            </div>

                            <p class="mt-6 text-xs font-bold uppercase tracking-[0.2em] text-indigo-300">
                                Subscription invoice
                            </p>

                            <h2 class="mt-2 break-all text-2xl font-black tracking-tight sm:text-3xl">
                                {{ $invoice->invoice_number }}
                            </h2>

                            <div class="mt-6 flex flex-wrap gap-x-8 gap-y-3 text-sm">
                                <div>
                                    <span class="text-slate-400">
                                        Organization
                                    </span>

                                    <span class="ml-2 font-extrabold text-white">
                                        {{ $organization->name }}
                                    </span>
                                </div>

                                <div>
                                    <span class="text-slate-400">
                                        Plan
                                    </span>

                                    <span class="ml-2 font-extrabold text-white">
                                        {{ $workflowRequest?->requestedPlan?->name
                                            ?? $invoice->plan->name }}
                                        · {{ $billingCycleLabel }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="min-w-[260px] rounded-2xl border border-white/15 bg-white/10 p-5 lg:text-right">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-indigo-200">
                                {{ $invoice->paid_at ? 'Invoice total' : 'Amount due' }}
                            </p>

                            <p class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">
                                <span class="text-lg font-extrabold text-indigo-200">
                                    {{ $invoice->currency }}
                                </span>

                                {{ number_format(
                                    $invoice->paid_at
                                        ? (float) $invoice->total_amount
                                        : (float) $invoiceOutstandingAmount,
                                    2
                                ) }}
                            </p>

                            <p class="mt-3 text-sm font-semibold text-slate-300">
                                @if ($invoice->paid_at)
                                    Paid
                                    {{ $invoice->paid_at->copy()->timezone(config('app.display_timezone'))->format(
                                        'M j, Y · g:i A'
                                    ) }}
                                @elseif ($invoice->due_date)
                                    Due
                                    {{ $invoice->due_date->copy()->timezone(config('app.display_timezone'))->format('M j, Y') }}
                                @else
                                    Due date not set
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid lg:grid-cols-[1.15fr_.85fr]">
                    <div class="border-b border-slate-200 p-6 sm:p-8 lg:border-b-0 lg:border-r">
                        <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">
                            Invoice details
                        </p>

                        <dl class="mt-6 grid gap-x-8 gap-y-6 sm:grid-cols-2">
                            @foreach ([
                                [
                                    'label' => 'Organization',
                                    'value' => $organization->name,
                                ],
                                [
                                    'label' => 'Subscription',
                                    'value' =>
                                        (
                                            $workflowRequest?->requestedPlan?->name
                                            ?? $invoice->plan->name
                                        )
                                        .' · '
                                        .$billingCycleLabel,
                                ],
                                [
                                    'label' => 'Issue date',
                                    'value' =>
                                        $invoice->issue_date?->copy()?->timezone(config('app.display_timezone'))?->format('M j, Y')
                                        ?? '—',
                                ],
                                [
                                    'label' => 'Due date',
                                    'value' =>
                                        $invoice->due_date?->copy()?->timezone(config('app.display_timezone'))?->format('M j, Y')
                                        ?? '—',
                                ],
                                [
                                    'label' => 'Created by',
                                    'value' => $issuerLabel,
                                ],
                                [
                                    'label' => 'Payment status',
                                    'value' => $paymentStateLabel,
                                ],
                            ] as $detail)
                                <div>
                                    <dt class="text-sm font-medium text-slate-500">
                                        {{ $detail['label'] }}
                                    </dt>

                                    <dd class="mt-1 font-extrabold text-slate-950">
                                        {{ $detail['value'] }}
                                    </dd>
                                </div>
                            @endforeach
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
                        <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">
                            Billing summary
                        </p>

                        <dl class="mt-6 space-y-4">
                            <div class="flex items-center justify-between gap-6">
                                <dt class="text-sm font-medium text-slate-700">
                                    Subscription subtotal
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
                        </dl>

                        <div class="my-6 border-t border-dashed border-slate-300"></div>

                        <div class="flex items-end justify-between gap-6">
                            <div>
                                <p class="text-sm font-extrabold text-slate-800">
                                    {{ $invoice->paid_at
                                        ? 'Total paid'
                                        : 'Outstanding balance' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $invoice->currency }}
                                </p>
                            </div>

                            <p class="text-right text-2xl font-black text-slate-950">
                                {{ number_format(
                                    $invoice->paid_at
                                        ? (float) $invoice->total_amount
                                        : (float) $invoiceOutstandingAmount,
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
                            <p class="text-sm font-extrabold">
                                {{ $paymentStateLabel }}
                            </p>

                            <p class="mt-1 text-xs font-semibold leading-5 opacity-80">
                                @if ($workflowClaimPending)
                                    The organization submitted payment details
                                    that require platform verification.
                                @elseif ($invoice->paid_at)
                                    The payment is linked and the invoice is paid.
                                @elseif ($invoice->status?->isOutstanding())
                                    This invoice remains open for payment.
                                @else
                                    Review the invoice status before taking action.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            @if (
                $invoice->status
                === \App\Enums\SubscriptionInvoiceStatus::DRAFT
            )
                <section class="rounded-3xl border border-amber-200 bg-amber-50 p-6 shadow-sm sm:p-8">
                    <p class="text-xs font-extrabold uppercase tracking-[0.15em] text-amber-700">
                        Draft workflow
                    </p>

                    <h2 class="mt-1 text-xl font-black text-amber-950">
                        Issue Invoice
                    </h2>

                    <p class="mt-1 text-sm font-medium text-amber-800">
                        Confirm the issue and due dates before making this
                        invoice payable.
                    </p>

                    <form
                        method="POST"
                        action="{{ route(
                            'platform.organizations.subscription-invoices.issue',
                            [
                                $organization,
                                $invoice,
                            ]
                        ) }}"
                        class="mt-6 grid gap-5 sm:grid-cols-2"
                    >
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="issue_date" class="block text-sm font-extrabold text-amber-950">
                                Issue date
                            </label>

                            <input
                                id="issue_date"
                                name="issue_date"
                                type="date"
                                required
                                value="{{ old(
                                    'issue_date',
                                    now()->toDateString()
                                ) }}"
                                class="mt-2 block w-full rounded-xl border-amber-300 bg-white shadow-sm focus:border-amber-600 focus:ring-amber-600"
                            >

                            @error('issue_date')
                                <p class="mt-2 text-sm font-semibold text-red-700">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="due_date" class="block text-sm font-extrabold text-amber-950">
                                Due date
                            </label>

                            <input
                                id="due_date"
                                name="due_date"
                                type="date"
                                required
                                value="{{ old(
                                    'due_date',
                                    now()->addDays(14)->toDateString()
                                ) }}"
                                class="mt-2 block w-full rounded-xl border-amber-300 bg-white shadow-sm focus:border-amber-600 focus:ring-amber-600"
                            >

                            @error('due_date')
                                <p class="mt-2 text-sm font-semibold text-red-700">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <button
                                type="submit"
                                class="inline-flex rounded-xl bg-amber-700 px-5 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-amber-800"
                            >
                                Issue Invoice
                            </button>
                        </div>
                    </form>
                </section>
            @endif

            @if ($workflowClaimPending)
                <section
                    class="overflow-hidden rounded-3xl border border-blue-200 bg-white shadow-sm"
                    data-platform-payment-claim
                >
                    <div class="border-b border-blue-200 px-6 py-5 sm:px-8" style="background:linear-gradient(135deg,#eff6ff 0%,#eef2ff 100%);">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs font-extrabold uppercase tracking-[0.15em] text-blue-700">
                                    Customer payment report
                                </p>

                                <h2 class="mt-1 text-2xl font-black text-blue-950">
                                    Verification required
                                </h2>

                                <p class="mt-1 text-sm font-semibold text-blue-800">
                                    Compare these details against the actual bank,
                                    M-Pesa, or processor account.
                                </p>
                            </div>

                            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-blue-300 bg-white px-3 py-1.5 text-xs font-extrabold text-blue-800 shadow-sm">
                                <span class="h-2 w-2 animate-pulse rounded-full bg-blue-500"></span>
                                Pending
                            </span>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8">
                        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ([
                                [
                                    'label' => 'Reference',
                                    'value' =>
                                        $workflowRequest
                                            ->payment_claim_reference,
                                ],
                                [
                                    'label' => 'Method',
                                    'value' =>
                                        $workflowRequest
                                            ->payment_claim_method,
                                ],
                                [
                                    'label' => 'Amount',
                                    'value' =>
                                        $workflowRequest
                                            ->payment_claim_currency
                                        .' '
                                        .number_format(
                                            (float) $workflowRequest
                                                ->payment_claim_amount,
                                            2
                                        ),
                                ],
                                [
                                    'label' => 'Paid at',
                                    'value' =>
                                        $workflowRequest
                                            ->payment_claim_paid_at
                                            ?->copy()?->timezone(config('app.display_timezone'))?->format(
                                                'M j, Y · g:i:s A T'
                                            )
                                        ?? '—',
                                ],
                            ] as $claimDetail)
                                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <dt class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">
                                        {{ $claimDetail['label'] }}
                                    </dt>

                                    <dd class="mt-2 break-words font-black text-slate-950">
                                        {{ $claimDetail['value'] }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>

                        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">
                                Submitted by
                            </p>

                            <p class="mt-2 font-extrabold text-slate-950">
                                {{ $workflowRequest->paymentClaimSubmittedBy?->name
                                    ?? 'Organization billing contact' }}
                            </p>

                            <p class="mt-1 text-sm text-slate-600">
                                {{ $workflowRequest->paymentClaimSubmittedBy?->email
                                    ?? '—' }}
                            </p>

                            @if ($workflowRequest->payment_claim_notes)
                                <p class="mt-4 whitespace-pre-line text-sm font-medium leading-6 text-slate-700">
                                    {{ $workflowRequest->payment_claim_notes }}
                                </p>
                            @endif
                        </div>

                        @if ($canManageInvoicePayments)
                            <div class="mt-6 grid gap-5 lg:grid-cols-2">
                                <form
                                    method="POST"
                                    action="{{ route(
                                        'platform.organizations.subscription-invoices.payment-claim.confirm',
                                        [
                                            $organization,
                                            $invoice,
                                        ]
                                    ) }}"
                                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5"
                                    onsubmit="return confirm('Confirm this payment and activate the requested plan?');"
                                >
                                    @csrf

                                    <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-emerald-700">
                                        Verified
                                    </p>

                                    <h3 class="mt-1 text-lg font-black text-emerald-950">
                                        Confirm Payment &amp; Activate Plan
                                    </h3>

                                    <p class="mt-2 text-sm font-semibold leading-6 text-emerald-800">
                                        This records the transaction, activates the
                                        plan, and queues the customer receipt.
                                    </p>

                                    <button
                                        type="submit"
                                        class="mt-5 inline-flex rounded-xl bg-emerald-700 px-5 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-emerald-800"
                                    >
                                        Confirm Payment &amp; Activate
                                    </button>
                                </form>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'platform.organizations.subscription-invoices.payment-claim.reject',
                                        [
                                            $organization,
                                            $invoice,
                                        ]
                                    ) }}"
                                    class="rounded-2xl border border-red-200 bg-red-50 p-5"
                                >
                                    @csrf

                                    <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-red-700">
                                        Not verified
                                    </p>

                                    <h3 class="mt-1 text-lg font-black text-red-950">
                                        Payment Not Found
                                    </h3>

                                    <label for="rejection_reason" class="mt-4 block text-sm font-extrabold text-red-900">
                                        Rejection reason
                                    </label>

                                    <textarea
                                        id="rejection_reason"
                                        name="rejection_reason"
                                        rows="4"
                                        required
                                        maxlength="2000"
                                        class="mt-2 block w-full rounded-xl border-red-200 bg-white shadow-sm focus:border-red-500 focus:ring-red-500"
                                    >{{ old('rejection_reason') }}</textarea>

                                    @error('rejection_reason')
                                        <p class="mt-2 text-sm font-semibold text-red-700">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                    <button
                                        type="submit"
                                        class="mt-4 inline-flex rounded-xl border border-red-300 bg-white px-5 py-3 text-sm font-extrabold text-red-700 shadow-sm hover:bg-red-100"
                                    >
                                        Reject Payment Report
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </section>
            @elseif ($showManualPaymentForm)
                <section
                    class="overflow-hidden rounded-3xl border border-emerald-200 bg-white shadow-sm"
                    data-invoice-payment-form
                >
                    <div class="border-b border-emerald-200 bg-emerald-50 px-6 py-5 sm:px-8">
                        <p class="text-xs font-extrabold uppercase tracking-[0.15em] text-emerald-700">
                            Manual payment entry
                        </p>

                        <h2 class="mt-1 text-2xl font-black text-emerald-950">
                            Record Payment
                        </h2>

                        <p class="mt-1 text-sm font-semibold text-emerald-800">
                            The invoice, amount, billing cycle, payment date, and
                            subscription dates are controlled by the system.
                        </p>
                    </div>

                    <div class="p-6 sm:p-8">
                        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ([
                                [
                                    'label' => 'Organization',
                                    'value' => $organization->name,
                                ],
                                [
                                    'label' => 'Invoice',
                                    'value' => $invoice->invoice_number,
                                ],
                                [
                                    'label' => 'Plan',
                                    'value' =>
                                        $planRequest->requestedPlan?->name
                                        ?? $invoice->plan->name,
                                ],
                                [
                                    'label' => 'Billing cycle',
                                    'value' =>
                                        ucfirst($planRequest->billing_cycle),
                                ],
                                [
                                    'label' => 'Amount to record',
                                    'value' =>
                                        $invoice->currency
                                        .' '
                                        .number_format(
                                            (float) $invoiceOutstandingAmount,
                                            2
                                        ),
                                ],
                                [
                                    'label' => 'Payment date',
                                    'value' =>
                                        $invoicePaymentPreview['paid_at']
                                            ->copy()->timezone(config('app.display_timezone'))->format('M d, Y H:i:s'),
                                ],
                                [
                                    'label' => 'Subscription starts',
                                    'value' =>
                                        $invoicePaymentPreview[
                                            'period_starts_at'
                                        ]->copy()->timezone(config('app.display_timezone'))->format('M d, Y H:i:s'),
                                ],
                                [
                                    'label' => 'Subscription ends',
                                    'value' =>
                                        $invoicePaymentPreview[
                                            'period_ends_at'
                                        ]->copy()->timezone(config('app.display_timezone'))->format('M d, Y H:i:s'),
                                ],
                            ] as $paymentDetail)
                                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <dt class="text-xs font-extrabold uppercase tracking-[0.12em] text-slate-500">
                                        {{ $paymentDetail['label'] }}
                                    </dt>

                                    <dd class="mt-2 break-words font-extrabold text-slate-950">
                                        {{ $paymentDetail['value'] }}
                                    </dd>
                                </div>
                            @endforeach
                        </dl>

                        @error('invoice')
                            <p class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-extrabold text-red-800">
                                {{ $message }}
                            </p>
                        @enderror

                        <form
                            method="POST"
                            action="{{ route(
                                'platform.organizations.subscription-invoices.payment.store',
                                [
                                    $organization,
                                    $invoice,
                                ]
                            ) }}"
                            class="mt-7 grid gap-5 sm:grid-cols-2"
                        >
                            @csrf

                            <div>
                                <label for="payment_reference" class="block text-sm font-extrabold text-slate-800">
                                    Payment reference
                                </label>

                                <input
                                    id="payment_reference"
                                    name="reference"
                                    type="text"
                                    required
                                    maxlength="120"
                                    autocomplete="off"
                                    value="{{ old('reference') }}"
                                    class="mt-2 block w-full rounded-xl border-slate-300 bg-white shadow-sm focus:border-emerald-600 focus:ring-emerald-600"
                                >

                                <p class="mt-2 text-xs font-medium text-slate-500">
                                    Enter the M-Pesa code, bank reference, receipt
                                    number, or other unique payment identifier.
                                </p>

                                @error('reference')
                                    <p class="mt-2 text-sm font-semibold text-red-700">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label for="invoice_payment_method" class="block text-sm font-extrabold text-slate-800">
                                    Payment method
                                </label>

                                <input
                                    id="invoice_payment_method"
                                    name="payment_method"
                                    type="text"
                                    required
                                    maxlength="100"
                                    list="invoice-payment-method-options"
                                    autocomplete="off"
                                    value="{{ old('payment_method') }}"
                                    class="mt-2 block w-full rounded-xl border-slate-300 bg-white shadow-sm focus:border-emerald-600 focus:ring-emerald-600"
                                >

                                <datalist id="invoice-payment-method-options">
                                    <option value="M-Pesa">
                                    <option value="Bank transfer">
                                    <option value="Card">
                                    <option value="Cash">
                                </datalist>

                                @error('payment_method')
                                    <p class="mt-2 text-sm font-semibold text-red-700">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="invoice_payment_notes" class="block text-sm font-extrabold text-slate-800">
                                    Notes
                                </label>

                                <textarea
                                    id="invoice_payment_notes"
                                    name="notes"
                                    rows="3"
                                    maxlength="5000"
                                    class="mt-2 block w-full rounded-xl border-slate-300 bg-white shadow-sm focus:border-emerald-600 focus:ring-emerald-600"
                                >{{ old('notes') }}</textarea>

                                @error('notes')
                                    <p class="mt-2 text-sm font-semibold text-red-700">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <button
                                    type="submit"
                                    class="inline-flex rounded-xl bg-emerald-700 px-5 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-emerald-800"
                                >
                                    Record Payment and Activate Subscription
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            @endif

            @if ($workflowClaimRejected)
                <section class="rounded-3xl border border-red-200 bg-red-50 p-6 shadow-sm">
                    <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-red-700">
                        Previous payment report
                    </p>

                    <h2 class="mt-1 text-lg font-black text-red-950">
                        Payment report rejected
                    </h2>

                    <p class="mt-2 text-sm font-semibold leading-6 text-red-800">
                        {{ $workflowRequest->payment_claim_rejection_reason }}
                    </p>
                </section>
            @endif

            @include(
                'subscription-invoices.partials.reminder-history',
                [
                    'notifications' =>
                        $invoice->reminderNotifications,

                    'showFailureDetails' =>
                        true,

                    'showRetryControls' =>
                        true,
                ]
            )

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-black text-slate-950">
                                Linked Transactions
                            </h2>

                            <p class="mt-1 text-sm font-medium text-slate-600">
                                Payments and adjustments linked to this invoice.
                            </p>
                        </div>

                        <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-extrabold text-slate-700">
                            {{ number_format($invoice->transactions->count()) }}
                        </span>
                    </div>
                </div>

                @if ($invoice->transactions->isEmpty())
                    <div class="p-8 text-center">
                        <p class="font-extrabold text-slate-950">
                            No transactions have been linked to this invoice.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    @foreach ([
                                        'Reference',
                                        'Status',
                                        'Method',
                                        'Paid at',
                                        'Amount',
                                    ] as $heading)
                                        <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-[0.1em] text-slate-500">
                                            {{ $heading }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-200">
                                @foreach ($invoice->transactions as $transaction)
                                    <tr>
                                        <td class="px-5 py-4 font-extrabold text-slate-950">
                                            {{ $transaction->reference }}
                                        </td>

                                        <td class="px-5 py-4 text-sm font-bold text-slate-700">
                                            {{ $transaction->status?->label() }}
                                        </td>

                                        <td class="px-5 py-4 text-sm font-medium text-slate-700">
                                            {{ $transaction->payment_method ?? '—' }}
                                        </td>

                                        <td class="px-5 py-4 text-sm font-medium text-slate-700">
                                            {{ $transaction->paid_at?->copy()?->timezone(config('app.display_timezone'))?->format(
                                                'M j, Y · g:i A'
                                            ) ?? '—' }}
                                        </td>

                                        <td class="px-5 py-4 font-extrabold text-slate-950">
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
