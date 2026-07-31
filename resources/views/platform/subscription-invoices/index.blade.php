<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    Subscription Invoices
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
                    Create Invoice
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Create a draft invoice for the current subscription plan.
                </p>

                <form
                    method="POST"
                    action="{{ route(
                        'platform.organizations.subscription-invoices.store',
                        $organization
                    ) }}"
                    class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4"
                >
                    @csrf

                    <div>
                        <label
                            for="invoice_number"
                            class="block text-sm font-semibold text-gray-900"
                        >
                            Invoice number
                        </label>

                        <input
                            id="invoice_number"
                            name="invoice_number"
                            type="text"
                            required
                            maxlength="120"
                            value="{{ old('invoice_number') }}"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >

                        @error('invoice_number')
                            <p class="mt-2 text-sm font-medium text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="subtotal"
                            class="block text-sm font-semibold text-gray-900"
                        >
                            Subtotal
                        </label>

                        <input
                            id="subtotal"
                            name="subtotal"
                            type="number"
                            min="0.01"
                            step="0.01"
                            required
                            value="{{ old('subtotal') }}"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >

                        @error('subtotal')
                            <p class="mt-2 text-sm font-medium text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="tax_amount"
                            class="block text-sm font-semibold text-gray-900"
                        >
                            Tax amount
                        </label>

                        <input
                            id="tax_amount"
                            name="tax_amount"
                            type="number"
                            min="0"
                            step="0.01"
                            required
                            value="{{ old('tax_amount', '0.00') }}"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >

                        @error('tax_amount')
                            <p class="mt-2 text-sm font-medium text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="invoice_currency"
                            class="block text-sm font-semibold text-gray-900"
                        >
                            Currency
                        </label>

                        <input
                            id="invoice_currency"
                            name="currency"
                            type="text"
                            maxlength="3"
                            required
                            value="{{ old('currency', 'KES') }}"
                            class="mt-2 block w-full rounded-lg border-gray-300 uppercase shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >

                        @error('currency')
                            <p class="mt-2 text-sm font-medium text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2 lg:col-span-4">
                        <label
                            for="invoice_notes"
                            class="block text-sm font-semibold text-gray-900"
                        >
                            Notes
                        </label>

                        <textarea
                            id="invoice_notes"
                            name="notes"
                            rows="3"
                            maxlength="5000"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >{{ old('notes') }}</textarea>

                        @error('notes')
                            <p class="mt-2 text-sm font-medium text-red-700">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2 lg:col-span-4">
                        <button
                            type="submit"
                            class="inline-flex rounded-lg border border-teal-700 bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-800"
                        >
                            Create Draft Invoice
                        </button>
                    </div>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Invoice History
                    </h2>
                </div>

                @if ($invoices->isEmpty())
                    <div class="p-6 text-sm text-gray-500">
                        No subscription invoices have been created.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Invoice
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Status
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Total
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Issued
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Due
                                    </th>

                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Details
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($invoices as $invoice)
                                    <tr>
                                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">
                                            {{ $invoice->invoice_number }}
                                        </td>

                                        <td class="px-5 py-4 text-sm text-gray-700">
                                            {{ $invoice->status?->label() }}
                                        </td>

                                        <td class="px-5 py-4 text-sm font-semibold text-gray-900">
                                            {{ $invoice->currency }}
                                            {{ number_format(
                                                (float) $invoice->total_amount,
                                                2
                                            ) }}
                                        </td>

                                        <td class="px-5 py-4 text-sm text-gray-700">
                                            {{ $invoice->issue_date?->format('M d, Y') ?? '—' }}
                                        </td>

                                        <td class="px-5 py-4 text-sm text-gray-700">
                                            {{ $invoice->due_date?->format('M d, Y') ?? '—' }}
                                        </td>

                                        <td class="px-5 py-4 text-right">
                                            <a
                                                href="{{ route(
                                                    'platform.organizations.subscription-invoices.show',
                                                    [
                                                        $organization,
                                                        $invoice,
                                                    ]
                                                ) }}"
                                                class="text-sm font-semibold text-teal-700 hover:text-teal-900"
                                            >
                                                View Invoice
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-gray-200 px-5 py-4">
                        {{ $invoices->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
