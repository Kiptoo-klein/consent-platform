<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ $campaign->name }}
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    {{ $campaign->consentTemplate?->title }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('consent-campaigns.index') }}"
                    class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    All Campaigns
                </a>

                <a
                    href="{{ route('consent-sessions.index', ['template_id' => $campaign->consent_template_id]) }}"
                    class="inline-flex justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    Consent Records
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg border border-green-300 bg-green-100 px-4 py-3 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('email_warning'))
                <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-amber-900">
                    {{ session('email_warning') }}
                </div>
            @endif

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ([
                    ['label' => 'Recipients', 'value' => $campaign->recipient_count, 'class' => 'text-gray-950'],
                    ['label' => 'Completed', 'value' => $statusCounts['completed'], 'class' => 'text-green-700'],
                    ['label' => 'In progress', 'value' => $statusCounts['in_progress'], 'class' => 'text-blue-700'],
                    ['label' => 'Pending', 'value' => $statusCounts['pending'], 'class' => 'text-yellow-700'],
                    ['label' => 'Expired / Cancelled', 'value' => $statusCounts['expired'] + $statusCounts['cancelled'], 'class' => 'text-red-700'],
                ] as $summary)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-gray-500">
                            {{ $summary['label'] }}
                        </p>

                        <p class="mt-2 text-3xl font-bold tabular-nums {{ $summary['class'] }}">
                            {{ $summary['value'] }}
                        </p>
                    </div>
                @endforeach
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <dl class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Template version
                        </dt>

                        <dd class="mt-1 font-semibold text-gray-950">
                            Version {{ $campaign->consentTemplateVersion?->version_number }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Created by
                        </dt>

                        <dd class="mt-1 font-semibold text-gray-950">
                            {{ $campaign->creator?->name ?? 'Former user' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Created
                        </dt>

                        <dd class="mt-1 font-semibold text-gray-950">
                            {{ $campaign->created_at?->format('d M Y, H:i') }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Signing deadline
                        </dt>

                        <dd class="mt-1 font-semibold text-gray-950">
                            {{ $campaign->expires_at?->format('d M Y, H:i') ?? 'No deadline' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h2 class="text-lg font-bold text-gray-950">
                        Recipient progress
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Each row is an independent consent record with its own secure link and audit trail.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Recipient
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Reference
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Consent status
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Email delivery
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($campaign->consentSessions as $session)
                                @php
                                    $latestNotification =
                                        $session
                                            ->notifications
                                            ->first();

                                    $statusClasses = match ($session->status) {
                                        'completed' => 'bg-green-100 text-green-700',
                                        'in_progress' => 'bg-blue-100 text-blue-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                        'expired' => 'bg-gray-200 text-gray-700',
                                        default => 'bg-yellow-100 text-yellow-700',
                                    };

                                    $deliveryClasses = match ($latestNotification?->status) {
                                        'sent' => 'bg-green-100 text-green-700',
                                        'failed' => 'bg-red-100 text-red-700',
                                        'processing' => 'bg-blue-100 text-blue-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp

                                <tr>
                                    <td class="px-6 py-4">
                                        <p class="font-semibold text-gray-950">
                                            {{ $session->signer_name }}
                                        </p>

                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ $session->signer_email }}
                                        </p>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                        {{ $session->signer_reference ?: '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                                            {{ str($session->status)->replace('_', ' ')->title() }}
                                        </span>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $deliveryClasses }}">
                                            {{ $latestNotification
                                                ? str($latestNotification->status)->title()
                                                : 'Not sent' }}
                                        </span>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <a
                                            href="{{ route('consent-sessions.show', $session) }}"
                                            class="text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                                        >
                                            View Record
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
