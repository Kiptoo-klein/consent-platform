<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Welcome back, {{ auth()->user()->name }}.
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <a
                    href="{{ route('consent-templates.create') }}"
                    class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    Create Template
                </a>

                <a
                    href="{{ route('consent-sessions.index') }}"
                    class="inline-flex justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
                >
                    View Consent Records
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">

            {{-- Primary Statistics --}}
            <section>
                <div class="mb-4">
                    <h2 class="text-lg font-bold text-gray-900">
                        Overview
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Current activity across your organization.
                    </p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">

                    {{-- Total Templates --}}
                    <a
                        href="{{ route('consent-templates.index') }}"
                        class="group overflow-hidden rounded-xl bg-white shadow transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">
                                        Consent Templates
                                    </p>

                                    <p class="mt-3 text-3xl font-bold text-gray-900">
                                        {{ number_format($totalTemplates) }}
                                    </p>

                                    <p class="mt-2 text-sm text-gray-500">
                                        {{ number_format($publishedTemplates) }}
                                        with published versions
                                    </p>
                                </div>

                                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-6 w-6"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </a>

                    {{-- Total Records --}}
                    <a
                        href="{{ route('consent-sessions.index') }}"
                        class="group overflow-hidden rounded-xl bg-white shadow transition hover:-translate-y-0.5 hover:shadow-md"
                    >
                        <div class="p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">
                                        Consent Records
                                    </p>

                                    <p class="mt-3 text-3xl font-bold text-gray-900">
                                        {{ number_format($totalRecords) }}
                                    </p>

                                    <p class="mt-2 text-sm text-gray-500">
                                        All records created
                                    </p>
                                </div>

                                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-6 w-6"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a3 3 0 006 0M9 5a3 3 0 016 0"
                                        />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </a>

                    {{-- Completed Records --}}
                    <div class="overflow-hidden rounded-xl bg-white shadow">
                        <div class="p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">
                                        Completed Records
                                    </p>

                                    <p class="mt-3 text-3xl font-bold text-gray-900">
                                        {{ number_format($completedRecords) }}
                                    </p>

                                    <p class="mt-2 text-sm text-green-700">
                                        {{ number_format($completedToday) }}
                                        completed today
                                    </p>
                                </div>

                                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-green-100 text-green-700">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-6 w-6"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Awaiting Completion --}}
                    <div class="overflow-hidden rounded-xl bg-white shadow">
                        <div class="p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">
                                        Awaiting Completion
                                    </p>

                                    <p class="mt-3 text-3xl font-bold text-gray-900">
                                        {{ number_format(
                                            $pendingRecords + $inProgressRecords
                                        ) }}
                                    </p>

                                    <p class="mt-2 text-sm text-gray-500">
                                        Pending and in progress
                                    </p>
                                </div>

                                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-yellow-100 text-yellow-700">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-6 w-6"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            {{-- Status Breakdown --}}
            <section class="overflow-hidden rounded-xl bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h2 class="text-lg font-bold text-gray-900">
                        Consent Record Status
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Breakdown of records by their current signing status.
                    </p>
                </div>

                <div class="grid gap-px bg-gray-200 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="bg-white p-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Pending
                                </p>

                                <p class="mt-2 text-2xl font-bold text-gray-900">
                                    {{ number_format($pendingRecords) }}
                                </p>
                            </div>

                            <span class="h-3 w-3 rounded-full bg-yellow-400"></span>
                        </div>
                    </div>

                    <div class="bg-white p-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    In Progress
                                </p>

                                <p class="mt-2 text-2xl font-bold text-gray-900">
                                    {{ number_format($inProgressRecords) }}
                                </p>
                            </div>

                            <span class="h-3 w-3 rounded-full bg-blue-500"></span>
                        </div>
                    </div>

                    <div class="bg-white p-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Completed
                                </p>

                                <p class="mt-2 text-2xl font-bold text-gray-900">
                                    {{ number_format($completedRecords) }}
                                </p>
                            </div>

                            <span class="h-3 w-3 rounded-full bg-green-500"></span>
                        </div>
                    </div>

                    <div class="bg-white p-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Cancelled
                                </p>

                                <p class="mt-2 text-2xl font-bold text-gray-900">
                                    {{ number_format($cancelledRecords) }}
                                </p>
                            </div>

                            <span class="h-3 w-3 rounded-full bg-red-500"></span>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-8 xl:grid-cols-3">

                {{-- Recent Consent Records --}}
                <section class="overflow-hidden rounded-xl bg-white shadow xl:col-span-2">
                    <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-6 py-5">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">
                                Recent Consent Records
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                The latest records created by your organization.
                            </p>
                        </div>

                        <a
                            href="{{ route('consent-sessions.index') }}"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                        >
                            View all
                        </a>
                    </div>

                    @if ($recentConsentRecords->isEmpty())
                        <div class="p-10 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"
                                    />
                                </svg>
                            </div>

                            <p class="mt-4 font-medium text-gray-700">
                                No consent records yet
                            </p>

                            <p class="mt-2 text-sm text-gray-500">
                                Create a record from one of your published templates.
                            </p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Signer
                                        </th>

                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Template
                                        </th>

                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Status
                                        </th>

                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Created
                                        </th>

                                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            Action
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach ($recentConsentRecords as $record)
                                        <tr class="hover:bg-gray-50">
                                            <td class="whitespace-nowrap px-6 py-4">
                                                <p class="font-medium text-gray-900">
                                                    {{ $record->signer_name }}
                                                </p>

                                                @if ($record->signer_email)
                                                    <p class="mt-1 text-xs text-gray-500">
                                                        {{ $record->signer_email }}
                                                    </p>
                                                @endif
                                            </td>

                                            <td class="px-6 py-4 text-sm text-gray-700">
                                                {{ $record->consentTemplate?->title ?? 'Unavailable template' }}
                                            </td>

                                            <td class="whitespace-nowrap px-6 py-4">
                                                <x-consent-status-badge
                                                    :status="$record->status"
                                                />
                                            </td>

                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                {{ $record->created_at->diffForHumans() }}
                                            </td>

                                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                                <a
                                                    href="{{ route('consent-sessions.show', $record) }}"
                                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                                                >
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                {{-- Recent Templates --}}
                <section class="overflow-hidden rounded-xl bg-white shadow">
                    <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-6 py-5">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">
                                Recent Templates
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                Recently created consent templates.
                            </p>
                        </div>

                        <a
                            href="{{ route('consent-templates.index') }}"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                        >
                            View all
                        </a>
                    </div>

                    @if ($recentTemplates->isEmpty())
                        <div class="p-10 text-center">
                            <p class="font-medium text-gray-700">
                                No templates yet
                            </p>

                            <a
                                href="{{ route('consent-templates.create') }}"
                                class="mt-4 inline-flex rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                            >
                                Create Template
                            </a>
                        </div>
                    @else
                        <div class="divide-y divide-gray-200">
                            @foreach ($recentTemplates as $template)
                                <div class="p-5">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-gray-900">
                                                {{ $template->title }}
                                            </p>

                                            <div class="mt-2 flex flex-wrap gap-2 text-xs text-gray-500">
                                                <span>
                                                    {{ $template->versions_count }}
                                                    {{ Str::plural('version', $template->versions_count) }}
                                                </span>

                                                <span aria-hidden="true">
                                                    •
                                                </span>

                                                <span>
                                                    {{ $template->consent_sessions_count }}
                                                    {{ Str::plural('record', $template->consent_sessions_count) }}
                                                </span>
                                            </div>
                                        </div>

                                        <a
                                            href="{{ route('consent-templates.edit', $template) }}"
                                            class="shrink-0 text-sm font-medium text-indigo-600 hover:text-indigo-800"
                                        >
                                            Open
                                        </a>
                                    </div>

                                    <p class="mt-3 text-xs text-gray-400">
                                        Created {{ $template->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

            </div>
        </div>
    </div>
</x-app-layout>
