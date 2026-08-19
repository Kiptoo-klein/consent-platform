<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Consent Records
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Search, filter, review, and export your organization’s consent records.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('consent-campaigns.index') }}"
                    class="inline-flex justify-center rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100"
                >
                    Bulk Campaigns
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

            @if ($errors->any())
                <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800">
                    <p class="mb-2 font-semibold">
                        The action could not be completed:
                    </p>

                    <ul class="list-disc space-y-1 pl-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-signed-consent-capacity
                :capacity="$signedConsentCapacity"
                :show-bar="true"
            />

            <section class="overflow-hidden rounded-xl bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Search and filter
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Your active filters also apply when downloading PDFs or exporting data.
                    </p>
                </div>

                <form
                    method="GET"
                    action="{{ route('consent-sessions.index') }}"
                    class="grid gap-4 px-6 py-5 md:grid-cols-2 xl:grid-cols-4"
                >
                    <div class="md:col-span-2 xl:col-span-2">
                        <label
                            for="search"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Search records
                        </label>

                        <input
                            id="search"
                            name="search"
                            type="search"
                            value="{{ $filters['search'] }}"
                            maxlength="255"
                            placeholder="Signer name, email, reference, or record ID"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >

                        <p class="mt-1 text-xs text-gray-500">
                            Enter a full or partial signer detail. Enter a number to search by record ID as well.
                        </p>
                    </div>

                    <div>
                        <label
                            for="template_id"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Consent template
                        </label>

                        <select
                            id="template_id"
                            name="template_id"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">
                                All templates
                            </option>

                            @foreach ($templates as $template)
                                <option
                                    value="{{ $template->id }}"
                                    @selected((string) $filters['template_id'] === (string) $template->id)
                                >
                                    {{ $template->title }}
                                    @if ($template->category)
                                        — {{ $template->category }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            for="category"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Template category
                        </label>

                        <select
                            id="category"
                            name="category"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">
                                All categories
                            </option>

                            @foreach ($categories as $category)
                                <option
                                    value="{{ $category }}"
                                    @selected($filters['category'] === $category)
                                >
                                    {{ $category }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            for="status"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Status
                        </label>

                        <select
                            id="status"
                            name="status"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">
                                All statuses
                            </option>

                            @foreach ($statuses as $statusValue => $statusLabel)
                                <option
                                    value="{{ $statusValue }}"
                                    @selected($filters['status'] === $statusValue)
                                >
                                    {{ $statusLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            for="signing_station_id"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Signing station
                        </label>

                        <select
                            id="signing_station_id"
                            name="signing_station_id"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">
                                All signing stations
                            </option>

                            @foreach ($signingStations as $station)
                                <option
                                    value="{{ $station->id }}"
                                    @selected((string) $filters['signing_station_id'] === (string) $station->id)
                                >
                                    {{ $station->name }}
                                </option>
                            @endforeach
                        </select>

                        <p class="mt-1 text-xs text-gray-500">
                            Manually created records appear when no station filter is selected.
                        </p>
                    </div>

                    <div>
                        <label
                            for="date_from"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Created from
                        </label>

                        <input
                            id="date_from"
                            name="date_from"
                            type="date"
                            value="{{ $filters['date_from'] }}"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div>
                        <label
                            for="date_to"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Created to
                        </label>

                        <input
                            id="date_to"
                            name="date_to"
                            type="date"
                            value="{{ $filters['date_to'] }}"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div>
                        <label
                            for="sort"
                            class="block text-sm font-medium text-gray-700"
                        >
                            Sort order
                        </label>

                        <select
                            id="sort"
                            name="sort"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option
                                value="newest"
                                @selected($filters['sort'] === 'newest')
                            >
                                Newest first
                            </option>

                            <option
                                value="oldest"
                                @selected($filters['sort'] === 'oldest')
                            >
                                Oldest first
                            </option>
                        </select>
                    </div>

                    <div class="flex flex-wrap items-end gap-2 md:col-span-2 xl:col-span-4">
                        <button
                            type="submit"
                            class="inline-flex justify-center rounded-lg bg-slate-800 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-900"
                        >
                            Apply filters
                        </button>

                        @if ($hasFilters)
                            <a
                                href="{{ route('consent-sessions.index') }}"
                                class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Clear all
                            </a>
                        @endif
                    </div>
                </form>

                @if ($activeFilters !== [])
                    <div class="border-t border-gray-200 bg-indigo-50/50 px-6 py-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="mr-1 text-xs font-semibold uppercase tracking-wide text-indigo-700">
                                Active filters
                            </span>

                            @foreach ($activeFilters as $activeFilter)
                                <span class="inline-flex items-center rounded-full border border-indigo-200 bg-white px-3 py-1 text-xs text-indigo-700">
                                    <span class="font-semibold">
                                        {{ $activeFilter['label'] }}:
                                    </span>

                                    <span class="ml-1">
                                        {{ $activeFilter['value'] }}
                                    </span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex flex-col gap-4 border-t border-gray-200 bg-gray-50 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold text-gray-900">
                            {{ $consentSessions->total() }}
                            {{ $consentSessions->total() === 1 ? 'matching record' : 'matching records' }}
                        </p>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ $downloadableCount }}
                            {{ $downloadableCount === 1 ? 'signed PDF is' : 'signed PDFs are' }}
                            available to download. Export data uses the same matching records.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @if ($downloadableCount > 0)
                            <a
                                href="{{ route('consent-sessions.download-all', $downloadParameters) }}"
                                class="inline-flex shrink-0 items-center justify-center rounded-lg bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                            >
                                Download PDFs
                            </a>
                        @else
                            <span
                                class="inline-flex shrink-0 cursor-not-allowed items-center justify-center rounded-lg bg-gray-300 px-5 py-3 text-sm font-semibold text-gray-600"
                                aria-disabled="true"
                            >
                                No PDFs available
                            </span>
                        @endif

                        @if ($consentSessions->total() > 0)
                            <a
                                href="{{ route('consent-sessions.export-data', $downloadParameters) }}"
                                class="inline-flex shrink-0 items-center justify-center rounded-lg border border-indigo-300 bg-white px-5 py-3 text-sm font-semibold text-indigo-700 hover:bg-indigo-50"
                            >
                                Export data
                            </a>
                        @endif
                    </div>
                </div>
            </section>

            @if ($templates->isNotEmpty())
                <details class="overflow-hidden rounded-xl bg-white shadow">
                    <summary class="cursor-pointer px-6 py-4 font-semibold text-gray-900 hover:bg-gray-50">
                        Assign or change a template category
                    </summary>

                    <form
                        method="POST"
                        action="{{ route('consent-template-categories.update') }}"
                        class="grid gap-4 border-t border-gray-200 px-6 py-5 md:grid-cols-2 xl:grid-cols-[1fr_1fr_auto] xl:items-end"
                    >
                        @csrf
                        @method('PATCH')

                        <div>
                            <label
                                for="category_template_id"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Template
                            </label>

                            <select
                                id="category_template_id"
                                name="template_id"
                                required
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                @foreach ($templates as $template)
                                    <option
                                        value="{{ $template->id }}"
                                        @selected((string) $filters['template_id'] === (string) $template->id)
                                    >
                                        {{ $template->title }}
                                        @if ($template->category)
                                            — current: {{ $template->category }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label
                                for="template_category"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Category
                            </label>

                            <input
                                id="template_category"
                                name="category"
                                type="text"
                                maxlength="100"
                                list="existing_categories"
                                placeholder="Example: Medical Consent"
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >

                            <datalist id="existing_categories">
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}"></option>
                                @endforeach
                            </datalist>

                            <p class="mt-1 text-xs text-gray-500">
                                Leave the category blank to remove the current category.
                            </p>
                        </div>

                        <button
                            type="submit"
                            class="inline-flex justify-center rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2.5 text-sm font-medium text-indigo-700 hover:bg-indigo-100"
                        >
                            Save category
                        </button>
                    </form>
                </details>
            @endif

            <section class="overflow-hidden rounded-xl bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                Consent records
                            </h1>

                            <p class="mt-1 text-sm text-gray-500">
                                Each record remains linked to the exact published template version used for signing.
                            </p>
                        </div>

                        <div class="rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-600">
                            {{ $consentSessions->total() }}
                            {{ $consentSessions->total() === 1 ? 'record' : 'records' }}
                        </div>
                    </div>
                </div>

                @if ($consentSessions->isEmpty())
                    <div class="px-6 py-20 text-center">
                        <div class="mx-auto max-w-md">
                            <h2 class="text-xl font-semibold text-gray-800">
                                {{ $hasAnyRecords ? 'No records match these filters' : 'No consent records yet' }}
                            </h2>

                            <p class="mt-3 text-gray-500">
                                @if ($hasAnyRecords)
                                    Clear or change the active search and filters.
                                @else
                                    Choose a published template to create your first consent record.
                                @endif
                            </p>

                            @if ($hasAnyRecords)
                                <a
                                    href="{{ route('consent-sessions.index') }}"
                                    class="mt-6 inline-flex rounded-lg border border-gray-300 bg-white px-5 py-3 font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Clear filters
                                </a>
                            @else
                                <a
                                    href="{{ route('consent-sessions.select-template') }}"
                                    class="mt-6 inline-flex rounded-lg bg-indigo-600 px-5 py-3 font-medium text-white hover:bg-indigo-700"
                                >
                                    New Individual Consent
                                </a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="border-b bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">
                                        Signer
                                    </th>

                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">
                                        Template
                                    </th>

                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">
                                        Category
                                    </th>

                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">
                                        Version
                                    </th>

                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">
                                        Status
                                    </th>

                                    <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">
                                        Created
                                    </th>

                                    <th class="px-6 py-4 text-right text-sm font-semibold text-gray-700">
                                        Action
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($consentSessions as $consentSession)
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="px-6 py-5">
                                            <p class="font-semibold text-gray-900">
                                                {{ $consentSession->signer_name }}
                                            </p>

                                            <p class="mt-1 text-xs font-medium text-indigo-600">
                                                Record #{{ $consentSession->id }}
                                            </p>

                                            @if ($consentSession->signer_email)
                                                <p class="mt-1 text-sm text-gray-500">
                                                    {{ $consentSession->signer_email }}
                                                </p>
                                            @endif

                                            @if ($consentSession->signer_reference)
                                                <p class="mt-1 text-xs text-gray-400">
                                                    Reference:
                                                    {{ $consentSession->signer_reference }}
                                                </p>
                                            @endif
                                        </td>

                                        <td class="px-6 py-5 text-gray-700">
                                            <p>
                                                {{ $consentSession->consentTemplate?->title ?? 'Unavailable template' }}
                                            </p>

                                            <p class="mt-1 text-xs text-gray-400">
                                                @if ($consentSession->signingStation)
                                                    Station:
                                                    {{ $consentSession->signingStation->name }}
                                                @else
                                                    Created manually
                                                @endif
                                            </p>
                                        </td>

                                        <td class="px-6 py-5">
                                            @if ($consentSession->consentTemplate?->category)
                                                <span class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700">
                                                    {{ $consentSession->consentTemplate->category }}
                                                </span>
                                            @else
                                                <span class="text-sm text-gray-400">
                                                    Uncategorised
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-5">
                                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">
                                                Version
                                                {{ $consentSession->consentTemplateVersion?->version_number ?? '—' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-5">
                                            @if ($consentSession->isExpired())
                                                <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800">
                                                    Expired
                                                </span>
                                            @else
                                                <x-consent-status-badge
                                                    :status="$consentSession->status"
                                                />
                                            @endif
                                        </td>

                                        <td class="px-6 py-5">
                                            <p class="text-sm text-gray-700">
                                                {{ $consentSession->created_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y') }}
                                            </p>

                                            <p class="mt-1 text-xs text-gray-400">
                                                {{ $consentSession->created_at->copy()->timezone(config('app.display_timezone'))->format('H:i') }}
                                            </p>

                                            @if ($consentSession->expires_at)
                                                <p
                                                    @class([
                                                        'mt-2 text-xs font-medium',
                                                        'text-amber-700' => $consentSession->isExpired(),
                                                        'text-gray-500' => ! $consentSession->isExpired(),
                                                    ])
                                                >
                                                    {{ $consentSession->isExpired() ? 'Expired' : 'Expires' }}
                                                    {{ $consentSession->expires_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y H:i') }}
                                                </p>
                                            @endif
                                        </td>

                                        <td class="px-6 py-5 text-right">
                                            <a
                                                href="{{ route('consent-sessions.show', $consentSession) }}"
                                                class="inline-flex rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100"
                                            >
                                                View record
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($consentSessions->hasPages())
                        <div class="border-t border-gray-200 px-6 py-4">
                            {{ $consentSessions->links() }}
                        </div>
                    @endif
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
