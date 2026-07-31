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
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
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

            <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-6 py-6 dark:border-gray-800">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wide text-gray-500">
                                Invoice number
                            </p>

                            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $invoice->invoice_number }}
                            </p>
                        </div>

                        <span class="inline-flex w-fit rounded-full border border-gray-300 bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            {{ $invoice->status?->label() }}
                        </span>
                    </div>
                </div>

                <dl class="grid gap-px bg-gray-200 dark:bg-gray-800 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        [
                            'label' => 'Organization',
                            'value' => $invoice->organization->name,
                        ],
                        [
                            'label' => 'Plan',
                            'value' => $invoice->plan->name,
                        ],
                        [
                            'label' => 'Subtotal',
                            'value' => $invoice->currency.' '.number_format(
                                (float) $invoice->subtotal,
                                2
                            ),
                        ],
                        [
                            'label' => 'Tax',
                            'value' => $invoice->currency.' '.number_format(
                                (float) $invoice->tax_amount,
                                2
                            ),
                        ],
                        [
                            'label' => 'Total',
                            'value' => $invoice->currency.' '.number_format(
                                (float) $invoice->total_amount,
                                2
                            ),
                        ],
                        [
                            'label' => 'Status',
                            'value' => $invoice->status?->label(),
                        ],
                        [
                            'label' => 'Issue date',
                            'value' => $invoice->issue_date?->format(
                                'M d, Y'
                            ) ?? '—',
                        ],
                        [
                            'label' => 'Due date',
                            'value' => $invoice->due_date?->format(
                                'M d, Y'
                            ) ?? '—',
                        ],
                        [
                            'label' => 'Paid at',
                            'value' => $invoice->paid_at?->format(
                                'M d, Y H:i'
                            ) ?? '—',
                        ],
                        [
                            'label' => 'Issued by',
                            'value' => $invoice->issuedBy?->name
                                ?? 'Platform Administrator',
                        ],
                    ] as $item)
                        <div class="bg-white p-6 dark:bg-gray-900">
                            <dt class="text-sm font-medium text-gray-500">
                                {{ $item['label'] }}
                            </dt>

                            <dd class="mt-2 font-semibold text-gray-900 dark:text-white">
                                {{ $item['value'] }}
                            </dd>
                        </div>
                    @endforeach
                </dl>

                @if ($invoice->notes)
                    <div class="border-t border-gray-200 p-6 dark:border-gray-800">
                        <h2 class="font-semibold text-gray-900 dark:text-white">
                            Notes
                        </h2>

                        <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">
                            {{ $invoice->notes }}
                        </p>
                    </div>
                @endif
            </section>

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
