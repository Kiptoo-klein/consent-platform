<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    Subscription Receipt
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    {{ $transaction->reference }}
                </p>
            </div>

            <a
                href="{{ route(
                    'platform.organizations.subscription-transactions.index',
                    $organization
                ) }}"
                class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                Back to Payment History
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-6">
                    <p class="text-sm font-medium uppercase tracking-wide text-gray-500">
                        Transaction reference
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        {{ $transaction->reference }}
                    </p>
                </div>

                <dl class="grid gap-px bg-gray-200 sm:grid-cols-2">
                    <div class="bg-white p-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Organization
                        </dt>

                        <dd class="mt-2 font-semibold text-gray-900">
                            {{ $transaction->organization->name }}
                        </dd>
                    </div>

                    <div class="bg-white p-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Plan
                        </dt>

                        <dd class="mt-2 font-semibold text-gray-900">
                            {{ $transaction->plan->name }}
                        </dd>
                    </div>

                    <div class="bg-white p-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Type
                        </dt>

                        <dd class="mt-2 font-semibold text-gray-900">
                            {{ $transaction->type?->label() }}
                        </dd>
                    </div>

                    <div class="bg-white p-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Status
                        </dt>

                        <dd class="mt-2 font-semibold text-gray-900">
                            {{ $transaction->status?->label() }}
                        </dd>
                    </div>

                    <div class="bg-white p-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Amount
                        </dt>

                        <dd class="mt-2 text-xl font-bold text-gray-900">
                            {{ $transaction->currency }}
                            {{ number_format(
                                (float) $transaction->amount,
                                2
                            ) }}
                        </dd>
                    </div>

                    <div class="bg-white p-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Payment method
                        </dt>

                        <dd class="mt-2 font-semibold text-gray-900">
                            {{ $transaction->payment_method ?? '—' }}
                        </dd>
                    </div>

                    <div class="bg-white p-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Paid at
                        </dt>

                        <dd class="mt-2 font-semibold text-gray-900">
                            {{ $transaction->paid_at?->copy()?->timezone(config('app.display_timezone'))?->format('M d, Y H:i') ?? '—' }}
                        </dd>
                    </div>

                    <div class="bg-white p-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Recorded by
                        </dt>

                        <dd class="mt-2 font-semibold text-gray-900">
                            {{ $transaction->recordedBy?->name ?? 'System' }}
                        </dd>
                    </div>

                    <div class="bg-white p-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Period starts
                        </dt>

                        <dd class="mt-2 font-semibold text-gray-900">
                            {{ $transaction->period_starts_at?->copy()?->timezone(config('app.display_timezone'))?->format('M d, Y H:i') ?? '—' }}
                        </dd>
                    </div>

                    <div class="bg-white p-6">
                        <dt class="text-sm font-medium text-gray-500">
                            Period ends
                        </dt>

                        <dd class="mt-2 font-semibold text-gray-900">
                            {{ $transaction->period_ends_at?->copy()?->timezone(config('app.display_timezone'))?->format('M d, Y H:i') ?? '—' }}
                        </dd>
                    </div>
                </dl>

                @if ($transaction->notes)
                    <div class="border-t border-gray-200 p-6">
                        <h2 class="font-semibold text-gray-900">
                            Notes
                        </h2>

                        <p class="mt-2 whitespace-pre-line text-sm text-gray-700">
                            {{ $transaction->notes }}
                        </p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
