<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-indigo-700">
                    Billing account
                </p>

                <h1 class="mt-1 text-2xl font-black text-slate-950">
                    Subscription Invoices
                </h1>

                <p class="mt-1 text-sm font-medium text-slate-600">
                    {{ $organization->name }}
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
                        'platform.organizations.show',
                        $organization
                    ) }}"
                    class="inline-flex rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-white shadow-sm hover:bg-slate-800"
                >
                    Organization
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $outstandingCount =
            $invoices
                ->getCollection()
                ->filter(
                    static fn ($invoice): bool =>
                        $invoice->status?->isOutstanding()
                )
                ->count();

        $paidCount =
            $invoices
                ->getCollection()
                ->filter(
                    static fn ($invoice): bool =>
                        $invoice->status?->value === 'paid'
                )
                ->count();
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-7 px-4 sm:px-6 lg:px-8">
            <section
                class="overflow-hidden rounded-3xl p-6 text-white shadow-xl sm:p-8"
                style="background:linear-gradient(135deg,#111827 0%,#312e81 60%,#4338ca 100%);"
                data-platform-invoice-list-overview
            >
                <div class="grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-indigo-200">
                            {{ $organization->name }}
                        </p>

                        <h2 class="mt-3 text-3xl font-black">
                            Invoice history
                        </h2>

                        <p class="mt-2 text-sm font-medium text-indigo-100">
                            Current plan:
                            <span class="font-extrabold text-white">
                                {{ $subscription->plan?->name ?? 'Unavailable' }}
                            </span>
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        @foreach ([
                            [
                                'label' => 'Invoices',
                                'value' => $invoices->total(),
                            ],
                            [
                                'label' => 'Outstanding',
                                'value' => $outstandingCount,
                            ],
                            [
                                'label' => 'Paid',
                                'value' => $paidCount,
                            ],
                        ] as $metric)
                            <div class="rounded-2xl border border-white/15 bg-white/10 p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-indigo-200">
                                    {{ $metric['label'] }}
                                </p>

                                <p class="mt-2 text-2xl font-black">
                                    {{ number_format($metric['value']) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <details
                class="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"
                @if ($errors->any()) open @endif
            >
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[0.15em] text-slate-500">
                            Manual billing tool
                        </p>

                        <h2 class="mt-1 text-lg font-black text-slate-950">
                            Create Invoice
                        </h2>

                        <p class="mt-1 text-sm text-slate-600">
                            Create a manual draft only for exceptional billing cases.
                        </p>
                    </div>

                    <span class="inline-flex rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-extrabold text-indigo-700">
                        Open form
                    </span>
                </summary>

                <div class="border-t border-slate-200 bg-slate-50 p-6">
                    <form
                        method="POST"
                        action="{{ route(
                            'platform.organizations.subscription-invoices.store',
                            $organization
                        ) }}"
                        class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        @csrf

                        <div>
                            <label for="invoice_number" class="block text-sm font-extrabold text-slate-800">
                                Invoice number
                            </label>

                            <input
                                id="invoice_number"
                                name="invoice_number"
                                type="text"
                                required
                                maxlength="120"
                                value="{{ old('invoice_number') }}"
                                class="mt-2 block w-full rounded-xl border-slate-300 bg-white shadow-sm focus:border-indigo-600 focus:ring-indigo-600"
                            >

                            @error('invoice_number')
                                <p class="mt-2 text-sm font-semibold text-red-700">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="subtotal" class="block text-sm font-extrabold text-slate-800">
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
                                class="mt-2 block w-full rounded-xl border-slate-300 bg-white shadow-sm focus:border-indigo-600 focus:ring-indigo-600"
                            >

                            @error('subtotal')
                                <p class="mt-2 text-sm font-semibold text-red-700">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="tax_amount" class="block text-sm font-extrabold text-slate-800">
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
                                class="mt-2 block w-full rounded-xl border-slate-300 bg-white shadow-sm focus:border-indigo-600 focus:ring-indigo-600"
                            >

                            @error('tax_amount')
                                <p class="mt-2 text-sm font-semibold text-red-700">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="invoice_currency" class="block text-sm font-extrabold text-slate-800">
                                Currency
                            </label>

                            <input
                                id="invoice_currency"
                                name="currency"
                                type="text"
                                maxlength="3"
                                required
                                value="{{ old('currency', 'KES') }}"
                                class="mt-2 block w-full rounded-xl border-slate-300 bg-white uppercase shadow-sm focus:border-indigo-600 focus:ring-indigo-600"
                            >

                            @error('currency')
                                <p class="mt-2 text-sm font-semibold text-red-700">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2 lg:col-span-4">
                            <label for="invoice_notes" class="block text-sm font-extrabold text-slate-800">
                                Notes
                            </label>

                            <textarea
                                id="invoice_notes"
                                name="notes"
                                rows="3"
                                maxlength="5000"
                                class="mt-2 block w-full rounded-xl border-slate-300 bg-white shadow-sm focus:border-indigo-600 focus:ring-indigo-600"
                            >{{ old('notes') }}</textarea>

                            @error('notes')
                                <p class="mt-2 text-sm font-semibold text-red-700">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2 lg:col-span-4">
                            <button
                                type="submit"
                                class="inline-flex rounded-xl bg-indigo-700 px-5 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-800"
                            >
                                Create Draft Invoice
                            </button>
                        </div>
                    </form>
                </div>
            </details>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-xl font-black text-slate-950">
                        Invoice History
                    </h2>

                    <p class="mt-1 text-sm font-medium text-slate-600">
                        Open an invoice to issue it, verify payment, or review transactions.
                    </p>
                </div>

                @if ($invoices->isEmpty())
                    <div class="p-10 text-center">
                        <p class="font-extrabold text-slate-950">
                            No subscription invoices have been created.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    @foreach ([
                                        'Invoice',
                                        'Status',
                                        'Plan',
                                        'Total',
                                        'Issued',
                                        'Due',
                                        'Action',
                                    ] as $heading)
                                        <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-[0.1em] text-slate-500">
                                            {{ $heading }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach ($invoices as $invoice)
                                    @php
                                        $statusValue =
                                            $invoice->status?->value;

                                        $statusStyle =
                                            match ($statusValue) {
                                                'paid' =>
                                                    'background-color:#ecfdf5;color:#065f46;border-color:#a7f3d0;',

                                                'issued' =>
                                                    'background-color:#eff6ff;color:#1e40af;border-color:#bfdbfe;',

                                                'overdue' =>
                                                    'background-color:#fef2f2;color:#991b1b;border-color:#fecaca;',

                                                'draft' =>
                                                    'background-color:#fffbeb;color:#92400e;border-color:#fde68a;',

                                                default =>
                                                    'background-color:#f8fafc;color:#475569;border-color:#cbd5e1;',
                                            };
                                    @endphp

                                    <tr class="transition hover:bg-slate-50/70">
                                        <td class="px-5 py-4">
                                            <p class="font-extrabold text-slate-950">
                                                {{ $invoice->invoice_number }}
                                            </p>

                                            <p class="mt-1 text-xs text-slate-500">
                                                Created
                                                {{ $invoice->created_at?->copy()?->timezone(config('app.display_timezone'))?->format('M d, Y') }}
                                            </p>
                                        </td>

                                        <td class="px-5 py-4">
                                            <span
                                                class="inline-flex rounded-full border px-3 py-1 text-xs font-extrabold"
                                                style="{{ $statusStyle }}"
                                            >
                                                {{ $invoice->status?->label() }}
                                            </span>
                                        </td>

                                        <td class="px-5 py-4 text-sm font-bold text-slate-800">
                                            {{ $invoice->plan?->name ?? 'Unavailable' }}
                                        </td>

                                        <td class="px-5 py-4 font-extrabold text-slate-950">
                                            {{ $invoice->currency }}
                                            {{ number_format(
                                                (float) $invoice->total_amount,
                                                2
                                            ) }}
                                        </td>

                                        <td class="px-5 py-4 text-sm font-medium text-slate-700">
                                            {{ $invoice->issue_date?->copy()?->timezone(config('app.display_timezone'))?->format('M d, Y') ?? '—' }}
                                        </td>

                                        <td class="px-5 py-4 text-sm font-medium text-slate-700">
                                            {{ $invoice->due_date?->copy()?->timezone(config('app.display_timezone'))?->format('M d, Y') ?? '—' }}
                                        </td>

                                        <td class="px-5 py-4">
                                            <a
                                                href="{{ route(
                                                    'platform.organizations.subscription-invoices.show',
                                                    [
                                                        $organization,
                                                        $invoice,
                                                    ]
                                                ) }}"
                                                class="inline-flex rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-extrabold text-white shadow-sm hover:bg-slate-800"
                                            >
                                                View Invoice
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-slate-200 px-5 py-4">
                        {{ $invoices->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
