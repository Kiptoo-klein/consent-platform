<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Subscription Receipt
                </h1>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ $transaction->reference }}
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route(
                        'organization-billing.receipts.download',
                        $transaction
                    ) }}"
                    class="inline-flex rounded-xl bg-indigo-700 px-4 py-2.5 text-sm font-extrabold text-white shadow-sm transition hover:bg-indigo-800"
                >
                    Download PDF
                </a>

                <a
                    href="{{ route('organization-billing.index') }}"
                    class="inline-flex rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-extrabold text-white shadow-sm transition hover:bg-black"
                >
                    Back to Billing
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $workflowTransaction =
            $subscriptionTransaction
            ?? $transaction
            ?? null;

        $workflowReceiptInvoice =
            $workflowTransaction?->invoice;

        $workflowReceiptRequest =
            $workflowReceiptInvoice?->planRequest;

        $workflowOrganizationEmail =
            $workflowTransaction?->organization?->email
            ?: $workflowTransaction?->organization?->support_email
            ?: $workflowTransaction?->subscription?->billingOwner?->email;

        $receiptStatus =
            $transaction->status?->label()
            ?? 'Recorded';

        $receiptAmount =
            $transaction->currency.' '.number_format(
                (float) $transaction->amount,
                2
            );
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
            <section
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-lg"
                data-subscription-receipt
            >
                <div
                    class="px-6 py-7 sm:px-8"
                    style="background-color:#0f172a !important;"
                >
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p
                                class="text-xs font-extrabold uppercase tracking-[0.18em]"
                                style="color:#99f6e4 !important;"
                            >
                                Official subscription receipt
                            </p>

                            <h2
                                class="mt-2 text-2xl font-black sm:text-3xl"
                                style="color:#ffffff !important;"
                            >
                                Payment {{ $receiptStatus }}
                            </h2>

                            <p
                                class="mt-2 text-sm font-medium"
                                style="color:#cbd5e1 !important;"
                            >
                                {{ $transaction->organization->name }}
                                · {{ $transaction->plan->name }}
                            </p>
                        </div>

                        <div
                            class="inline-flex w-fit items-center gap-2 rounded-full px-4 py-2 text-sm font-extrabold"
                            style="background-color:#dcfce7 !important;color:#166534 !important;"
                        >
                            <span
                                class="h-2.5 w-2.5 rounded-full"
                                style="background-color:#16a34a !important;"
                                aria-hidden="true"
                            ></span>

                            Payment {{ $receiptStatus }}
                        </div>
                    </div>

                    <div class="mt-7 grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
                        <div>
                            <p
                                class="text-xs font-bold uppercase tracking-wide"
                                style="color:#94a3b8 !important;"
                            >
                                Amount paid
                            </p>

                            <p
                                class="mt-1 text-3xl font-black sm:text-4xl"
                                style="color:#ffffff !important;"
                            >
                                {{ $receiptAmount }}
                            </p>
                        </div>

                        <div
                            class="rounded-2xl border px-4 py-3"
                            style="background-color:#1e293b !important;border-color:#334155 !important;"
                        >
                            <p
                                class="text-xs font-bold uppercase tracking-wide"
                                style="color:#94a3b8 !important;"
                            >
                                Receipt reference
                            </p>

                            <p
                                class="mt-1 break-all text-sm font-extrabold"
                                style="color:#ffffff !important;"
                            >
                                {{ $transaction->reference }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-6 sm:p-8">
                    <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                        <section>
                            <div>
                                <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                    Receipt summary
                                </p>

                                <h3 class="mt-1 text-lg font-black text-slate-950">
                                    Payment details
                                </h3>
                            </div>

                            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                                @foreach ([
                                    [
                                        'label' => 'Organization',
                                        'value' => $transaction->organization->name,
                                    ],
                                    [
                                        'label' => 'Subscription plan',
                                        'value' => $transaction->plan->name,
                                    ],
                                    [
                                        'label' => 'Transaction type',
                                        'value' => $transaction->type?->label(),
                                    ],
                                    [
                                        'label' => 'Payment method',
                                        'value' => $transaction->payment_method ?? '—',
                                    ],
                                    [
                                        'label' => 'Paid at',
                                        'value' => $transaction->paid_at?->copy()?->timezone(config('app.display_timezone'))?->format(
                                            'M j, Y g:i A'
                                        ) ?? '—',
                                    ],
                                    [
                                        'label' => 'Recorded by',
                                        'value' => $transaction->recordedBy?->name
                                            ?? 'System',
                                    ],
                                    [
                                        'label' => 'Billing period starts',
                                        'value' => $transaction->period_starts_at?->copy()?->timezone(config('app.display_timezone'))?->format(
                                            'M j, Y g:i A'
                                        ) ?? '—',
                                    ],
                                    [
                                        'label' => 'Billing period ends',
                                        'value' => $transaction->period_ends_at?->copy()?->timezone(config('app.display_timezone'))?->format(
                                            'M j, Y g:i A'
                                        ) ?? '—',
                                    ],
                                ] as $item)
                                    <div
                                        class="rounded-2xl border p-4"
                                        style="background-color:#f8fafc !important;border-color:#e2e8f0 !important;"
                                    >
                                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                            {{ $item['label'] }}
                                        </dt>

                                        <dd class="mt-2 break-words text-sm font-extrabold text-slate-950">
                                            {{ $item['value'] }}
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>

                        <section
                            class="rounded-2xl border p-5"
                            style="background-color:#f0fdfa !important;border-color:#99f6e4 !important;"
                            data-receipt-verification-metadata
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                                    style="background-color:#ccfbf1 !important;color:#0f766e !important;"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        class="h-5 w-5"
                                        aria-hidden="true"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M9 12l2 2 4-4"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 3l7 3v5c0 4.4-2.9 8.4-7 10-4.1-1.6-7-5.6-7-10V6l7-3z"
                                        />
                                    </svg>
                                </div>

                                <div>
                                    <p
                                        class="text-xs font-extrabold uppercase tracking-wide"
                                        style="color:#0f766e !important;"
                                    >
                                        Verified payment
                                    </p>

                                    <h3
                                        class="mt-1 text-lg font-black"
                                        style="color:#134e4a !important;"
                                    >
                                        Verified payment details
                                    </h3>
                                </div>
                            </div>

                            <dl class="mt-5 space-y-4">
                                @foreach ([
                                    [
                                        'label' => 'Organization email',
                                        'value' => $workflowOrganizationEmail ?: '—',
                                    ],
                                    [
                                        'label' => 'Invoice reference',
                                        'value' => $workflowReceiptInvoice?->invoice_number ?? '—',
                                    ],
                                    [
                                        'label' => 'Billing cycle',
                                        'value' => ucfirst(
                                            $workflowReceiptRequest?->billing_cycle
                                            ?? '—'
                                        ),
                                    ],
                                    [
                                        'label' => 'Exact payment time',
                                        'value' => $workflowTransaction?->paid_at?->copy()?->timezone(config('app.display_timezone'))?->format(
                                            'M j, Y g:i:s A T'
                                        ) ?? '—',
                                    ],
                                    [
                                        'label' => 'Confirmed by',
                                        'value' => $workflowTransaction?->recordedBy?->name
                                            ?? 'Platform Billing',
                                    ],
                                    [
                                        'label' => 'Receipt reference',
                                        'value' => $workflowTransaction?->reference ?? '—',
                                    ],
                                ] as $item)
                                    <div class="border-b border-teal-100 pb-3 last:border-b-0 last:pb-0">
                                        <dt
                                            class="text-xs font-bold uppercase tracking-wide"
                                            style="color:#0f766e !important;"
                                        >
                                            {{ $item['label'] }}
                                        </dt>

                                        <dd
                                            class="mt-1 break-words text-sm font-extrabold"
                                            style="color:#134e4a !important;"
                                        >
                                            {{ $item['value'] }}
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    </div>

                    @if ($transaction->notes)
                        <section
                            class="mt-6 rounded-2xl border p-5"
                            style="background-color:#fffbeb !important;border-color:#fde68a !important;"
                        >
                            <p class="text-xs font-extrabold uppercase tracking-wide text-amber-700">
                                Notes
                            </p>

                            <p class="mt-2 whitespace-pre-line text-sm font-medium leading-6 text-amber-950">
                                {{ $transaction->notes }}
                            </p>
                        </section>
                    @endif
                </div>
            </section>

            <p class="text-center text-xs font-medium text-slate-500">
                This receipt was generated by the eConsent subscription billing system.
            </p>
        </div>
    </div>
</x-app-layout>
