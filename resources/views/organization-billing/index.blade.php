<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Billing & Receipts
                </h1>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Review your subscription payments and download receipts.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route(
                        'organization-billing.reminder-preferences.index'
                    ) }}"
                    class="inline-flex rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 dark:border-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300"
                >
                    Reminder Preferences
                </a>

                <a
                    href="{{ route(
                        'organization-billing.reminder-recipients.index'
                    ) }}"
                    class="inline-flex rounded-lg border border-teal-300 bg-teal-50 px-4 py-2 text-sm font-semibold text-teal-700 transition hover:bg-teal-100 dark:border-teal-700 dark:bg-teal-950/40 dark:text-teal-300"
                >
                    Reminder Recipients
                </a>

                <a
                    href="{{ route('organization-subscription.show') }}"
                    class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                >
                    Subscription Status
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $plan = $subscription->plan;

        $formatStatus = static fn (?string $status): string =>
            $status
                ? ucwords(str_replace('_', ' ', $status))
                : 'Unavailable';
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                            Organization
                        </p>

                        <h2 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $organization->name }}
                        </h2>

                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Current plan:
                            <span class="font-semibold text-gray-900 dark:text-white">
                                {{ $plan?->name ?? 'Unavailable' }}
                            </span>
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-gray-50 px-5 py-4 dark:bg-gray-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Payment status
                            </p>

                            <p class="mt-2 text-lg font-bold text-gray-900 dark:text-white">
                                {{ $formatStatus(
                                    $subscription->payment_status?->value
                                ) }}
                            </p>
                        </div>

                        <div class="rounded-2xl bg-gray-50 px-5 py-4 dark:bg-gray-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Subscription status
                            </p>

                            <p class="mt-2 text-lg font-bold text-gray-900 dark:text-white">
                                {{ $formatStatus(
                                    $subscription->status?->value
                                ) }}
                            </p>
                        </div>
                    </div>
                </div>

                <dl class="mt-6 grid gap-4 border-t border-gray-200 pt-6 dark:border-gray-800 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">
                            Current period starts
                        </dt>

                        <dd class="mt-1 font-semibold text-gray-900 dark:text-white">
                            {{ $subscription->current_period_starts_at?->format('M d, Y H:i') ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-500">
                            Current period ends
                        </dt>

                        <dd class="mt-1 font-semibold text-gray-900 dark:text-white">
                            {{ $subscription->current_period_ends_at?->format('M d, Y H:i') ?? '—' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800 sm:px-8">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        Subscription Invoices
                    </h2>

                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Review issued invoices and download official PDF copies.
                    </p>
                </div>

                @if ($invoices->isEmpty())
                    <div class="p-6 text-sm text-gray-600 dark:text-gray-400 sm:p-8">
                        No subscription invoices have been issued.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                            <thead class="bg-gray-50 dark:bg-gray-800">
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
                                        Invoice
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-900">
                                @foreach ($invoices as $invoice)
                                    <tr>
                                        <td class="px-5 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $invoice->invoice_number }}
                                        </td>

                                        <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $invoice->status?->label() }}
                                        </td>

                                        <td class="px-5 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $invoice->currency }}
                                            {{ number_format(
                                                (float) $invoice->total_amount,
                                                2
                                            ) }}
                                        </td>

                                        <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $invoice->issue_date?->format('M d, Y') ?? '—' }}
                                        </td>

                                        <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $invoice->due_date?->format('M d, Y') ?? '—' }}
                                        </td>

                                        <td class="px-5 py-4">
                                            <div class="flex justify-end gap-3">
                                                <a
                                                    href="{{ route(
                                                        'organization-billing.invoices.show',
                                                        $invoice
                                                    ) }}"
                                                    class="text-sm font-semibold text-teal-700 hover:text-teal-900 dark:text-teal-400"
                                                >
                                                    View
                                                </a>

                                                <a
                                                    href="{{ route(
                                                        'organization-billing.invoices.download',
                                                        $invoice
                                                    ) }}"
                                                    class="text-sm font-semibold text-indigo-700 hover:text-indigo-900 dark:text-indigo-400"
                                                >
                                                    Download PDF
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                        {{ $invoices->links() }}
                    </div>
                @endif
            </section>

            <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800 sm:px-8">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        Transaction History
                    </h2>

                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        This history is read-only. Payment changes are managed by the Platform Administrator.
                    </p>
                </div>

                @if ($transactions->isEmpty())
                    <div class="p-6 text-sm text-gray-600 dark:text-gray-400 sm:p-8">
                        No subscription transactions have been recorded.
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

                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-800 dark:bg-gray-900">
                                @foreach ($transactions as $transaction)
                                    <tr>
                                        <td class="px-5 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $transaction->reference }}
                                        </td>

                                        <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $transaction->type?->label() }}
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

                                        <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $transaction->paid_at?->format('M d, Y H:i') ?? '—' }}
                                        </td>

                                        <td class="px-5 py-4">
                                            <div class="flex justify-end gap-3">
                                                <a
                                                    href="{{ route(
                                                        'organization-billing.receipts.show',
                                                        $transaction
                                                    ) }}"
                                                    class="text-sm font-semibold text-teal-700 hover:text-teal-900 dark:text-teal-400"
                                                >
                                                    View
                                                </a>

                                                <a
                                                    href="{{ route(
                                                        'organization-billing.receipts.download',
                                                        $transaction
                                                    ) }}"
                                                    class="text-sm font-semibold text-indigo-700 hover:text-indigo-900 dark:text-indigo-400"
                                                >
                                                    Download PDF
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
