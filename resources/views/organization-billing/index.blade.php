<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Billing & Receipts
                </h1>

                <p class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Review your subscription payments and download receipts.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route(
                        'organization-billing.reminder-preferences.index'
                    ) }}"
                    class="inline-flex items-center rounded-xl bg-indigo-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Reminder Preferences
                </a>

                <a
                    href="{{ route(
                        'organization-billing.reminder-recipients.index'
                    ) }}"
                    class="inline-flex items-center rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                >
                    Reminder Recipients
                </a>

                <a
                    href="{{ route('organization-subscription.show') }}"
                    class="inline-flex items-center rounded-xl bg-gray-900 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-black focus:outline-none focus:ring-2 focus:ring-gray-600 focus:ring-offset-2 dark:bg-gray-100 dark:text-gray-950 dark:hover:bg-white"
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
            {{-- BILLING_OWNER_ROLE_ASSIGNMENT --}}
            <section class="overflow-hidden rounded-3xl border border-teal-200 bg-white shadow-lg dark:border-teal-900 dark:bg-gray-900">
                <div class="bg-gradient-to-r from-teal-900 via-teal-800 to-teal-700 px-6 py-6 sm:px-8">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-100">
                                Billing Role
                            </p>

                            <h2 class="mt-2 text-2xl font-bold text-white">
                                Billing Owner
                            </h2>

                            <p class="mt-3 text-base font-medium leading-7 text-teal-50">
                                Exactly one active organization user manages
                                billing records, invoices, receipts, reminder
                                settings, and billing notifications.
                            </p>

                            <p class="mt-2 text-sm leading-6 text-teal-100">
                                This billing assignment is separate from Staff,
                                Auditor, Consent Manager, and Organization
                                Admin permissions.
                            </p>
                        </div>

                        <span class="inline-flex w-fit items-center rounded-full bg-white/15 px-4 py-2 text-sm font-bold text-white ring-1 ring-inset ring-white/30">
                            Single assignment
                        </span>
                    </div>
                </div>

                <div class="grid gap-6 p-6 sm:p-8 lg:grid-cols-2">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-950">
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                            Current Billing Owner
                        </p>

                        @if ($billingOwner)
                            <h3 class="mt-4 text-xl font-bold text-gray-950 dark:text-white">
                                {{ $billingOwner->name }}
                            </h3>

                            <p class="mt-1 break-all text-base font-medium text-gray-700 dark:text-gray-300">
                                {{ $billingOwner->email }}
                            </p>

                            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                                <div class="rounded-xl border border-teal-200 bg-teal-50 p-4 dark:border-teal-900 dark:bg-teal-950/40">
                                    <dt class="text-xs font-bold uppercase tracking-wide text-teal-800 dark:text-teal-300">
                                        Billing role
                                    </dt>

                                    <dd class="mt-2 text-base font-bold text-teal-950 dark:text-teal-100">
                                        Billing Owner
                                    </dd>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                                    <dt class="text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                        Organization role
                                    </dt>

                                    <dd class="mt-2 text-base font-bold text-gray-950 dark:text-white">
                                        {{ $billingOwner->roles
                                            ->first()?->name
                                            ?? 'No organization role' }}
                                    </dd>
                                </div>
                            </dl>
                        @else
                            <div class="mt-4 rounded-xl border-2 border-red-300 bg-red-50 p-4 text-red-900 dark:border-red-800 dark:bg-red-950/40 dark:text-red-200">
                                <p class="font-bold">
                                    Billing Owner unavailable
                                </p>

                                <p class="mt-1 text-sm leading-6">
                                    Assign an active organization user before
                                    continuing with billing management.
                                </p>
                            </div>
                        @endif
                    </div>

                    @if ($isOrganizationAdministrator)
                        <form
                            method="POST"
                            action="{{ route(
                                'organization-billing.owner.update'
                            ) }}"
                            class="rounded-2xl border border-teal-200 bg-teal-50/50 p-6 shadow-sm dark:border-teal-900 dark:bg-teal-950/20"
                        >
                            @csrf
                            @method('PATCH')

                            <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                                Assign Billing Owner
                            </h3>

                            <p class="mt-2 text-base font-medium leading-7 text-gray-700 dark:text-gray-300">
                                Choose an active organization user to manage
                                billing.
                            </p>

                            <p class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-300">
                                Their normal organization role will not be changed.
                            </p>

                            <label
                                for="billing_owner_user_id"
                                class="mt-5 block text-sm font-bold text-gray-950 dark:text-white"
                            >
                                Organization user
                            </label>

                            <select
                                id="billing_owner_user_id"
                                name="billing_owner_user_id"
                                required
                                class="mt-2 block w-full rounded-xl border-2 border-gray-300 bg-white px-4 py-3 text-base font-semibold text-gray-950 shadow-sm focus:border-teal-700 focus:ring-teal-700 dark:border-gray-600 dark:bg-gray-950 dark:text-white"
                            >
                                @foreach ($billingUsers as $billingUser)
                                    <option
                                        value="{{ $billingUser->id }}"
                                        @selected(
                                            (int) old(
                                                'billing_owner_user_id',
                                                $subscription
                                                    ->billing_owner_user_id
                                            )
                                            === (int) $billingUser->id
                                        )
                                    >
                                        {{ $billingUser->name }}
                                        — {{ $billingUser->email }}
                                        — {{ $billingUser->roles
                                            ->first()?->name
                                            ?? 'No organization role' }}
                                    </option>
                                @endforeach
                            </select>

                            @error('billing_owner_user_id')
                                <p class="mt-2 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-bold text-red-800 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
                                    {{ $message }}
                                </p>
                            @enderror

                            <div class="mt-5 rounded-xl border-2 border-amber-300 bg-amber-50 p-4 text-amber-950 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
                                <p class="font-bold">
                                    Billing access changes immediately
                                </p>

                                <p class="mt-1 text-sm font-medium leading-6">
                                    The selected user gains access to billing
                                    records, invoices, receipts, reminder
                                    settings, and billing notifications. The
                                    previous Billing Owner loses this access
                                    unless they are an Organization Admin.
                                </p>
                            </div>

                            <button
                                type="submit"
                                class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-teal-800 px-5 py-3 text-base font-bold text-white shadow-sm transition hover:bg-teal-900 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2 sm:w-auto"
                            >
                                Assign Billing Owner
                            </button>
                        </form>
                    @else
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-6 shadow-sm dark:border-gray-700 dark:bg-gray-950">
                            <p class="text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                Billing access
                            </p>

                            <h3 class="mt-3 text-lg font-bold text-gray-950 dark:text-white">
                                You are the assigned Billing Owner
                            </h3>

                            <p class="mt-2 text-base font-medium leading-7 text-gray-700 dark:text-gray-300">
                                You can review billing records and manage
                                billing preferences.
                            </p>

                            <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-300">
                                Only an Organization Admin can transfer the
                                Billing Owner assignment to another user.
                            </p>
                        </div>
                    @endif
                </div>
            </section>

            <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                            Organization
                        </p>

                        <h2 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $organization->name }}
                        </h2>

                        <p class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            Current plan:
                            <span class="font-semibold text-gray-900 dark:text-white">
                                {{ $plan?->name ?? 'Unavailable' }}
                            </span>
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-gray-50 px-5 py-4 dark:bg-gray-800">
                            <p class="text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                Payment status
                            </p>

                            <p class="mt-2 text-lg font-bold text-gray-900 dark:text-white">
                                {{ $formatStatus(
                                    $subscription->payment_status?->value
                                ) }}
                            </p>
                        </div>

                        <div class="rounded-2xl bg-gray-50 px-5 py-4 dark:bg-gray-800">
                            <p class="text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
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
                        <dt class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Current period starts
                        </dt>

                        <dd class="mt-1 font-semibold text-gray-900 dark:text-white">
                            {{ $subscription->current_period_starts_at?->format('M d, Y H:i') ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm font-semibold text-gray-700 dark:text-gray-300">
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

                    <p class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Review issued invoices and download official PDF copies.
                    </p>
                </div>

                @if ($invoices->isEmpty())
                    <div class="p-6 text-sm font-medium text-gray-700 dark:text-gray-300 sm:p-8">
                        No subscription invoices have been issued.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                        Invoice
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                        Status
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                        Total
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                        Issued
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                        Due
                                    </th>

                                    <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
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

                    <p class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                        This history is read-only. Payment changes are managed by the Platform Administrator.
                    </p>
                </div>

                @if ($transactions->isEmpty())
                    <div class="p-6 text-sm font-medium text-gray-700 dark:text-gray-300 sm:p-8">
                        No subscription transactions have been recorded.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                        Reference
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                        Type
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                        Status
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                        Amount
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                        Paid
                                    </th>

                                    <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">
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
