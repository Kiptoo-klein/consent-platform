<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    Subscription Payment History
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    {{ $organization->name }}
                </p>
            </div>

            <a
                href="{{ route(
                    'platform.organizations.show',
                    $organization
                ) }}"
                class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                Back to Organization
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">
                    Record Transaction
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Record an external payment result or renew the current
                    subscription after confirming payment.
                </p>

                @if ($subscription?->isEvaluation())
                    <div
                        data-evaluation-transaction-guard
                        class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-900"
                    >
                        Successful payments and renewals cannot activate a Free evaluation from this form. Use the plan request and invoice payment workflow.
                    </div>
                @else
                <form
                    method="POST"
                    action="{{ route(
                        'platform.organizations.subscription-transactions.store',
                        $organization
                    ) }}"
                    class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
                >
                    @csrf

                    <div>
                        <label
                            for="reference"
                            class="block text-sm font-semibold text-gray-900"
                        >
                            Reference
                        </label>

                        <input
                            id="reference"
                            name="reference"
                            type="text"
                            required
                            maxlength="120"
                            value="{{ old('reference') }}"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >

                        @error('reference')
                            <p class="mt-2 text-sm font-medium text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="transaction_type"
                            class="block text-sm font-semibold text-gray-900"
                        >
                            Type
                        </label>

                        <select
                            id="transaction_type"
                            name="type"
                            required
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >
                            @foreach ($transactionTypes as $type)
                                <option
                                    value="{{ $type->value }}"
                                    @selected(
                                        old(
                                            'type',
                                            \App\Enums\SubscriptionTransactionType::PAYMENT->value
                                        ) === $type->value
                                    )
                                >
                                    {{ $type->label() }}
                                </option>
                            @endforeach
                        </select>

                        @error('type')
                            <p class="mt-2 text-sm font-medium text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="transaction_status"
                            class="block text-sm font-semibold text-gray-900"
                        >
                            Status
                        </label>

                        <select
                            id="transaction_status"
                            name="status"
                            required
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >
                            @foreach ($transactionStatuses as $status)
                                <option
                                    value="{{ $status->value }}"
                                    @selected(
                                        old(
                                            'status',
                                            \App\Enums\SubscriptionTransactionStatus::SUCCESSFUL->value
                                        ) === $status->value
                                    )
                                >
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>

                        @error('status')
                            <p class="mt-2 text-sm font-medium text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="subscription_invoice_id"
                            class="block text-sm font-semibold text-gray-900"
                        >
                            Invoice
                        </label>

                        <select
                            id="subscription_invoice_id"
                            name="subscription_invoice_id"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >
                            <option value="">
                                No linked invoice
                            </option>

                            @foreach ($outstandingInvoices as $invoice)
                                <option
                                    value="{{ $invoice->id }}"
                                    @selected(
                                        (string) old(
                                            'subscription_invoice_id'
                                        ) === (string) $invoice->id
                                    )
                                >
                                    {{ $invoice->invoice_number }}
                                    — {{ $invoice->currency }}
                                    {{ number_format(
                                        (float) $invoice->total_amount,
                                        2
                                    ) }}
                                    — {{ $invoice->status?->label() }}
                                </option>
                            @endforeach
                        </select>

                        @error('subscription_invoice_id')
                            <p class="mt-2 text-sm font-medium text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="amount"
                            class="block text-sm font-semibold text-gray-900"
                        >
                            Amount
                        </label>

                        <input
                            id="amount"
                            name="amount"
                            type="number"
                            min="0.01"
                            step="0.01"
                            required
                            value="{{ old('amount') }}"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >

                        @error('amount')
                            <p class="mt-2 text-sm font-medium text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="currency"
                            class="block text-sm font-semibold text-gray-900"
                        >
                            Currency
                        </label>

                        <input
                            id="currency"
                            name="currency"
                            type="text"
                            required
                            maxlength="3"
                            value="{{ old('currency', 'KES') }}"
                            class="mt-2 block w-full uppercase rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >

                        @error('currency')
                            <p class="mt-2 text-sm font-medium text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="payment_method"
                            class="block text-sm font-semibold text-gray-900"
                        >
                            Payment method
                        </label>

                        <input
                            id="payment_method"
                            name="payment_method"
                            type="text"
                            maxlength="100"
                            value="{{ old('payment_method') }}"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >
                    </div>

                    <div>
                        <label
                            for="paid_at"
                            class="block text-sm font-semibold text-gray-900"
                        >
                            Paid at
                        </label>

                        <input
                            id="paid_at"
                            name="paid_at"
                            type="datetime-local"
                            value="{{ old(
                                'paid_at',
                                now()->startOfMinute()->format('Y-m-d\TH:i')
                            ) }}"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >

                        @error('paid_at')
                            <p class="mt-2 text-sm font-medium text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="period_starts_at"
                            class="block text-sm font-semibold text-gray-900"
                        >
                            Period starts
                        </label>

                        <input
                            id="period_starts_at"
                            name="period_starts_at"
                            type="datetime-local"
                            value="{{ old('period_starts_at') }}"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >

                        @error('period_starts_at')
                            <p class="mt-2 text-sm font-medium text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="period_ends_at"
                            class="block text-sm font-semibold text-gray-900"
                        >
                            Period ends
                        </label>

                        <input
                            id="period_ends_at"
                            name="period_ends_at"
                            type="datetime-local"
                            value="{{ old('period_ends_at') }}"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >

                        @error('period_ends_at')
                            <p class="mt-2 text-sm font-medium text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3">
                        <label
                            for="notes"
                            class="block text-sm font-semibold text-gray-900"
                        >
                            Notes
                        </label>

                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            maxlength="5000"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >{{ old('notes') }}</textarea>
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3">
                        <button
                            type="submit"
                            class="inline-flex rounded-lg border border-teal-700 bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-800"
                        >
                            Record Transaction
                        </button>
                    </div>
                </form>
                @endif
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Transaction History
                    </h2>
                </div>

                @if ($transactions->isEmpty())
                    <div class="p-6 text-sm text-gray-500">
                        No subscription transactions have been recorded.
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
                                        Invoice
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Type
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Status
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Amount
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Paid
                                    </th>

                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Receipt
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($transactions as $transaction)
                                    <tr>
                                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">
                                            {{ $transaction->reference }}
                                        </td>

                                        <td class="px-5 py-4 text-sm text-gray-700">
                                            {{ $transaction->invoice?->invoice_number ?? '—' }}
                                        </td>

                                        <td class="px-5 py-4 text-sm text-gray-700">
                                            {{ $transaction->type?->label() }}
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

                                        <td class="px-5 py-4 text-sm text-gray-700">
                                            {{ $transaction->paid_at?->copy()?->timezone(config('app.display_timezone'))?->format('M d, Y H:i') ?? '—' }}
                                        </td>

                                        <td class="px-5 py-4 text-right">
                                            <a
                                                href="{{ route(
                                                    'platform.organizations.subscription-transactions.show',
                                                    [
                                                        $organization,
                                                        $transaction,
                                                    ]
                                                ) }}"
                                                class="text-sm font-semibold text-teal-700 hover:text-teal-900"
                                            >
                                                View Receipt
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-gray-200 px-5 py-4">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
