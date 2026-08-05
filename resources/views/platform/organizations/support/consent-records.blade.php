<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-teal-700">
                    {{ $organization->name }}
                </p>

                <h2 class="text-2xl font-bold text-gray-900">
                    Consent record recovery
                </h2>
            </div>

            <a
                href="{{ route(
                    'platform.organizations.support.index',
                    $organization
                ) }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
            >
                Back to support
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="GET"
                class="grid gap-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:grid-cols-[1fr_220px_auto]"
            >
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Search signer, email, reference, or template"
                    class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600"
                >

                <select
                    name="status"
                    class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600"
                >
                    <option value="">All statuses</option>

                    @foreach ($allowedStatuses as $allowedStatus)
                        <option
                            value="{{ $allowedStatus }}"
                            @selected($status === $allowedStatus)
                        >
                            {{ ucwords(str_replace('_', ' ', $allowedStatus)) }}
                        </option>
                    @endforeach
                </select>

                <button
                    type="submit"
                    class="rounded-lg bg-teal-700 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800"
                >
                    Search
                </button>
            </form>

            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="divide-y divide-gray-200">
                    @forelse ($consentSessions as $consentSession)
                        <article class="grid gap-5 px-6 py-5 lg:grid-cols-[1fr_420px] lg:items-center">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-gray-900">
                                        {{ $consentSession->signer_name }}
                                    </h3>

                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-800">
                                        {{ ucwords(str_replace('_', ' ', $consentSession->status)) }}
                                    </span>
                                </div>

                                <p class="mt-1 text-sm text-gray-600">
                                    {{ $consentSession->signer_email ?: 'No email recorded' }}
                                </p>

                                <p class="mt-2 text-sm text-gray-700">
                                    {{ $consentSession->consentTemplate?->title ?? 'Unavailable template' }}
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    Completed:
                                    {{ $consentSession->completed_at
                                        ? \App\Support\DisplayTime::format(
                                            $consentSession->completed_at,
                                            'd M Y, H:i'
                                        )
                                        : '—' }}
                                </p>
                            </div>

                            <form
                                method="POST"
                                action="{{ route(
                                    'platform.organizations.support.consent-records.download',
                                    [
                                        $organization,
                                        $consentSession,
                                    ]
                                ) }}"
                                class="flex flex-col gap-2 sm:flex-row"
                            >
                                @csrf

                                <input
                                    type="text"
                                    name="support_reason"
                                    required
                                    minlength="10"
                                    maxlength="1000"
                                    placeholder="Reason for recovering this PDF"
                                    class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-600 focus:ring-teal-600"
                                >

                                <button
                                    type="submit"
                                    @disabled(
                                        ! $consentSession->isCompleted()
                                        || blank($consentSession->pdf_path)
                                    )
                                    class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Download PDF
                                </button>
                            </form>
                        </article>
                    @empty
                        <p class="px-6 py-12 text-center text-sm text-gray-500">
                            No consent records matched the selected filters.
                        </p>
                    @endforelse
                </div>

                @if ($consentSessions->hasPages())
                    <div class="border-t border-gray-200 px-5 py-4">
                        {{ $consentSessions->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
