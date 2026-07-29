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
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-6 py-6 dark:border-gray-800">
                    <p class="text-sm font-medium uppercase tracking-wide text-gray-500">
                        Transaction reference
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $transaction->reference }}
                    </p>
                </div>

                <dl class="grid gap-px bg-gray-200 dark:bg-gray-800 sm:grid-cols-2">
                    @foreach ([
                        [
                            'label' => 'Organization',
                            'value' => $transaction->organization->name,
                        ],
                        [
                            'label' => 'Plan',
                            'value' => $transaction->plan->name,
                        ],
                        [
                            'label' => 'Type',
                            'value' => $transaction->type?->label(),
                        ],
                        [
                            'label' => 'Status',
                            'value' => $transaction->status?->label(),
                        ],
                        [
                            'label' => 'Amount',
                            'value' => $transaction->currency.' '.number_format(
                                (float) $transaction->amount,
                                2
                            ),
                        ],
                        [
                            'label' => 'Payment method',
                            'value' => $transaction->payment_method ?? '—',
                        ],
                        [
                            'label' => 'Paid at',
                            'value' => $transaction->paid_at?->format(
                                'M d, Y H:i'
                            ) ?? '—',
                        ],
                        [
                            'label' => 'Recorded by',
                            'value' => $transaction->recordedBy?->name
                                ?? 'System',
                        ],
                        [
                            'label' => 'Period starts',
                            'value' => $transaction->period_starts_at?->format(
                                'M d, Y H:i'
                            ) ?? '—',
                        ],
                        [
                            'label' => 'Period ends',
                            'value' => $transaction->period_ends_at?->format(
                                'M d, Y H:i'
                            ) ?? '—',
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

                @if ($transaction->notes)
                    <div class="border-t border-gray-200 p-6 dark:border-gray-800">
                        <h2 class="font-semibold text-gray-900 dark:text-white">
                            Notes
                        </h2>

                        <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">
                            {{ $transaction->notes }}
                        </p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
