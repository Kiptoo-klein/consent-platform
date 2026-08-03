<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Bulk Consent Campaigns
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Track consent requests sent to groups of recipients.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('consent-sessions.index') }}"
                    class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Consent Records
                </a>

                <a
                    href="{{ route('consent-campaigns.select-template') }}"
                    class="inline-flex justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    New Consent
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

            @if ($campaigns->isEmpty())
                <section class="rounded-xl border border-gray-200 bg-white px-6 py-14 text-center shadow-sm">
                    <h1 class="text-xl font-bold text-gray-950">
                        No bulk campaigns yet
                    </h1>

                    <p class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-gray-600">
                        Choose a published individual-consent template, then use
                        <span class="font-semibold">Up to 20 People</span>
                        to send separate secure consent requests to a group.
                    </p>

                    <a
                        href="{{ route('consent-campaigns.select-template') }}"
                        class="mt-6 inline-flex rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Start a Campaign
                    </a>
                </section>
            @else
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Campaign
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Recipients
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Progress
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Deadline
                                    </th>

                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        Action
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($campaigns as $campaign)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <p class="font-semibold text-gray-950">
                                                {{ $campaign->name }}
                                            </p>

                                            <p class="mt-1 text-sm text-gray-500">
                                                {{ $campaign->consentTemplate?->title }}
                                            </p>

                                            <p class="mt-1 text-xs text-gray-400">
                                                Created {{ $campaign->created_at?->copy()?->timezone(config('app.display_timezone'))?->format('d M Y, H:i') }}
                                            </p>
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                            {{ $campaign->consent_sessions_count }}
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-2 text-xs font-semibold">
                                                <span class="rounded-full bg-green-100 px-2.5 py-1 text-green-700">
                                                    {{ $campaign->completed_count }} completed
                                                </span>

                                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-blue-700">
                                                    {{ $campaign->in_progress_count }} in progress
                                                </span>

                                                <span class="rounded-full bg-yellow-100 px-2.5 py-1 text-yellow-700">
                                                    {{ $campaign->pending_count }} pending
                                                </span>

                                                @if ($campaign->expired_count > 0)
                                                    <span class="rounded-full bg-gray-200 px-2.5 py-1 text-gray-700">
                                                        {{ $campaign->expired_count }} expired
                                                    </span>
                                                @endif

                                                @if ($campaign->cancelled_count > 0)
                                                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-red-700">
                                                        {{ $campaign->cancelled_count }} cancelled
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                            {{ $campaign->expires_at?->copy()?->timezone(config('app.display_timezone'))?->format('d M Y, H:i') ?? 'No deadline' }}
                                        </td>

                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <a
                                                href="{{ route('consent-campaigns.show', $campaign) }}"
                                                class="text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                                            >
                                                View Campaign
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($campaigns->hasPages())
                        <div class="border-t border-gray-200 px-6 py-4">
                            {{ $campaigns->links() }}
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
