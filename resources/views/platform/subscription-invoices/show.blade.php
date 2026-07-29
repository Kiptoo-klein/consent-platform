<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    Subscription Invoice
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    {{ $invoice->invoice_number }}
                </p>
            </div>

            <a
                href="{{ route(
                    'platform.organizations.subscription-invoices.index',
                    $organization
                ) }}"
                class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                Back to Invoices
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wide text-gray-500">
                                Invoice number
                            </p>

                            <p class="mt-2 text-2xl font-bold text-gray-900">
                                {{ $invoice->invoice_number }}
                            </p>
                        </div>

                        <span class="inline-flex rounded-full border border-gray-300 bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-800">
                            {{ $invoice->status?->label() }}
                        </span>
                    </div>
                </div>

                <dl class="grid gap-px bg-gray-200 sm:grid-cols-2 lg:grid-cols-3">
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
                            'label' => 'Issued by',
                            'value' => $invoice->issuedBy?->name ?? '—',
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
                    ] as $item)
                        <div class="bg-white p-6">
                            <dt class="text-sm font-medium text-gray-500">
                                {{ $item['label'] }}
                            </dt>

                            <dd class="mt-2 font-semibold text-gray-900">
                                {{ $item['value'] }}
                            </dd>
                        </div>
                    @endforeach
                </dl>

                @if ($invoice->notes)
                    <div class="border-t border-gray-200 p-6">
                        <h2 class="font-semibold text-gray-900">
                            Notes
                        </h2>

                        <p class="mt-2 whitespace-pre-line text-sm text-gray-700">
                            {{ $invoice->notes }}
                        </p>
                    </div>
                @endif
            </section>

            @if (
                $invoice->status
                    === \App\Enums\SubscriptionInvoiceStatus::DRAFT
            )
                <section class="rounded-2xl border border-teal-200 bg-teal-50 p-6">
                    <h2 class="text-lg font-semibold text-teal-950">
                        Issue Invoice
                    </h2>

                    <form
                        method="POST"
                        action="{{ route(
                            'platform.organizations.subscription-invoices.issue',
                            [
                                $organization,
                                $invoice,
                            ]
                        ) }}"
                        class="mt-5 grid gap-4 sm:grid-cols-2"
                    >
                        @csrf
                        @method('PATCH')

                        <div>
                            <label
                                for="issue_date"
                                class="block text-sm font-semibold text-teal-950"
                            >
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
                                class="mt-2 block w-full rounded-lg border-teal-300 bg-white shadow-sm focus:border-teal-600 focus:ring-teal-600"
                            >

                            @error('issue_date')
                                <p class="mt-2 text-sm font-medium text-red-700">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="due_date"
                                class="block text-sm font-semibold text-teal-950"
                            >
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
                                class="mt-2 block w-full rounded-lg border-teal-300 bg-white shadow-sm focus:border-teal-600 focus:ring-teal-600"
                            >

                            @error('due_date')
                                <p class="mt-2 text-sm font-medium text-red-700">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <button
                                type="submit"
                                class="inline-flex rounded-lg border border-teal-700 bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-800"
                            >
                                Issue Invoice
                            </button>
                        </div>
                    </form>
                </section>
            @endif

            @include(
                'subscription-invoices.partials.reminder-history',
                [
                    'notifications' =>
                        $invoice->reminderNotifications,

                    'showFailureDetails' =>
                        true,
                ]
            )

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Linked Transactions
                    </h2>
                </div>

                @if ($invoice->transactions->isEmpty())
                    <div class="p-6 text-sm text-gray-500">
                        No transactions have been linked to this invoice.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
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

                            <tbody class="divide-y divide-gray-200">
                                @foreach ($invoice->transactions as $transaction)
                                    <tr>
                                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">
                                            {{ $transaction->reference }}
                                        </td>

                                        <td class="px-5 py-4 text-sm text-gray-700">
                                            {{ $transaction->status?->label() }}
                                        </td>

                                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">
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
