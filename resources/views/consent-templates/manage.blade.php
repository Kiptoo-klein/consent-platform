<x-app-layout>
    @php
        $showingArchived = $showingArchived ?? false;
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ $showingArchived
                    ? 'Archived Consent Templates'
                    : 'Manage Consent Templates' }}
            </h2>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ $showingArchived
                        ? route('consent-templates.manage')
                        : route('consent-templates.archived') }}"
                    class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    {{ $showingArchived
                        ? 'Active Templates'
                        : 'Archived Templates' }}
                </a>

                <a
                    href="{{ route('consent-sessions.index') }}"
                    class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Consent Records
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow">

                @if (session('success'))
                    <div class="mb-6 rounded-lg border border-green-300 bg-green-100 px-4 py-3 text-green-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800">
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

                <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            {{ $showingArchived
                                ? 'Archived Templates'
                                : 'Consent Templates' }}
                        </h1>

                        <p class="mt-2 text-gray-500">
                            {{ $showingArchived
                                ? 'View consent templates removed from the active template list.'
                                : 'Prepare changes privately while the current version remains live.' }}
                        </p>
                    </div>

                    <a
                        href="{{ route('consent-templates.new') }}"
                        class="inline-flex justify-center rounded-lg bg-blue-600 px-5 py-3 text-white hover:bg-blue-700"
                    >
                        + New Consent
                    </a>
                </div>

                @if ($consentTemplates->isEmpty())
                    <div class="py-20 text-center">
                        <h2 class="mb-3 text-2xl font-semibold text-gray-700">
                            No Consent Templates Yet
                        </h2>

                        <p class="mb-8 text-gray-500">
                            Create your first consent template.
                        </p>

                        <a
                            href="{{ route('consent-templates.new') }}"
                            class="inline-flex rounded-lg bg-blue-600 px-6 py-3 text-white hover:bg-blue-700"
                        >
                            Create Your First Consent
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="border-b bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left">
                                        Template
                                    </th>

                                    <th class="px-6 py-4 text-left">
                                        Usage
                                    </th>

                                    <th class="px-6 py-4 text-left">
                                        Live Version
                                    </th>

                                    <th class="px-6 py-4 text-left">
                                        Working Copy
                                    </th>

                                    <th class="px-6 py-4 text-left">
                                        Last Published
                                    </th>

                                    <th class="px-6 py-4 text-center">
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($consentTemplates as $consentTemplate)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-6 py-5">
                                            <p class="font-semibold text-gray-900">
                                                {{ $consentTemplate->title }}
                                            </p>

                                            <p class="mt-1 text-sm text-gray-500">
                                                {{ $consentTemplate->description ?: 'No description' }}
                                            </p>

                                            @if ($consentTemplate->status === 'archived')
                                                <span class="mt-2 inline-flex rounded-full bg-gray-200 px-3 py-1 text-xs font-medium text-gray-700">
                                                    Archived
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-5">
                                            @php
                                                $usageBadgeClasses = match ($consentTemplate->usage_type ?? \App\Models\ConsentTemplate::USAGE_BOTH) {
                                                    \App\Models\ConsentTemplate::USAGE_INDIVIDUAL =>
                                                        'bg-blue-100 text-blue-800',

                                                    \App\Models\ConsentTemplate::USAGE_SIGNING_STATION =>
                                                        'bg-purple-100 text-purple-800',

                                                    default =>
                                                        'bg-indigo-100 text-indigo-800',
                                                };
                                            @endphp

                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $usageBadgeClasses }}">
                                                {{ $consentTemplate->usageLabel() }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-5">
                                            @if ($consentTemplate->activeVersion)
                                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                                                    Version {{ $consentTemplate->activeVersion->version_number }} Live
                                                </span>
                                            @else
                                                <span class="inline-flex rounded-full bg-yellow-100 px-3 py-1 text-sm font-medium text-yellow-800">
                                                    Not Published
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-5">
                                            @if ($consentTemplate->has_unpublished_changes)
                                                <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800">
                                                    Unpublished Changes
                                                </span>
                                            @elseif ($consentTemplate->activeVersion)
                                                <span class="inline-flex rounded-full bg-green-50 px-3 py-1 text-sm font-medium text-green-700">
                                                    Up to Date
                                                </span>
                                            @else
                                                <span class="text-gray-500">
                                                    Draft
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-5 text-gray-600">
                                            @if ($consentTemplate->activeVersion)
                                                {{ $consentTemplate->activeVersion->published_at?->format('M d, Y H:i') ?? 'Unknown' }}
                                            @else
                                                —
                                            @endif
                                        </td>

                                        <td class="px-6 py-5">
                                            <div
                                                x-data="{ open: false }"
                                                class="mx-auto grid w-[20rem] grid-cols-3 items-center justify-items-center gap-2"
                                            >
                                                <a
                                                    href="{{ route('consent-templates.preview', $consentTemplate) }}"
                                                    class="col-start-1 inline-flex w-24 items-center justify-center whitespace-nowrap rounded-lg border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100"
                                                >
                                                    Preview
                                                </a>

                                                @if ($consentTemplate->status === 'archived')
                                                    <span
                                                        class="col-start-2 inline-flex w-24 cursor-not-allowed items-center justify-center whitespace-nowrap rounded-lg border border-gray-200 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-400"
                                                        aria-disabled="true"
                                                    >
                                                        Archived
                                                    </span>
                                                @elseif (
                                                    $consentTemplate->has_unpublished_changes
                                                    || $consentTemplate->active_version_id === null
                                                )
                                                    <form
                                                        method="POST"
                                                        action="{{ route('consent-templates.publish', $consentTemplate) }}"
                                                        class="col-start-2 w-24"
                                                        onsubmit="return confirm('{{ $consentTemplate->has_unpublished_changes
                                                            ? 'Publish these changes as a new immutable version?'
                                                            : 'Make the latest published version live again?' }}');"
                                                    >
                                                        @csrf

                                                        <button
                                                            type="submit"
                                                            class="w-full whitespace-nowrap rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                                                        >
                                                            Publish
                                                        </button>
                                                    </form>
                                                @else
                                                    <form
                                                        method="POST"
                                                        action="{{ route('consent-templates.unpublish', $consentTemplate) }}"
                                                        class="col-start-2 w-24"
                                                        onsubmit="return confirm('Take this consent template offline? Published history will be preserved.');"
                                                    >
                                                        @csrf

                                                        <button
                                                            type="submit"
                                                            class="w-full whitespace-nowrap rounded-lg bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700"
                                                        >
                                                            Unpublish
                                                        </button>
                                                    </form>
                                                @endif

                                                <div class="relative col-start-3 w-24">
                                                    <button
                                                        type="button"
                                                        x-on:click="open = ! open"
                                                        x-on:click.outside="open = false"
                                                        x-on:keydown.escape.window="open = false"
                                                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                                        aria-haspopup="true"
                                                        x-bind:aria-expanded="open"
                                                    >
                                                        More

                                                        <svg
                                                            class="h-4 w-4"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                            xmlns="http://www.w3.org/2000/svg"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 9l-7 7-7-7"
                                                            />
                                                        </svg>
                                                    </button>

                                                    <div
                                                        x-show="open"
                                                        x-cloak
                                                        class="absolute right-0 z-20 mt-2 w-60 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg"
                                                    >
                                                        @if ($consentTemplate->status !== 'archived')
                                                            <a
                                                                href="{{ route('consent-templates.edit', $consentTemplate) }}"
                                                                class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50"
                                                            >
                                                                {{ $consentTemplate->activeVersion
                                                                    ? 'Edit Working Copy'
                                                                    : 'Edit Draft' }}
                                                            </a>
                                                        @endif

                                                        @if ($consentTemplate->activeVersion)
                                                            <a
                                                                href="{{ route('consent-templates.published', $consentTemplate) }}"
                                                                class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50"
                                                            >
                                                                View Published
                                                            </a>

                                                            <a
                                                                href="{{ route('consent-templates.history', $consentTemplate) }}"
                                                                class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50"
                                                            >
                                                                Version History
                                                            </a>
                                                        @endif

                                                        @if (
                                                            $consentTemplate->activeVersion
                                                            && ! $consentTemplate->has_unpublished_changes
                                                        )
                                                            <div class="border-t border-gray-100 px-4 py-3 text-sm text-gray-400">
                                                                Working copy is up to date
                                                            </div>
                                                        @endif

                                                        @if (
                                                            $consentTemplate->status !== 'archived'
                                                            && $consentTemplate->active_version_id === null
                                                        )
                                                            <form
                                                                method="POST"
                                                                action="{{ route('consent-templates.archive', $consentTemplate) }}"
                                                                class="border-t border-gray-100"
                                                                onsubmit="return confirm('Archive this consent template? It will be hidden from the normal template list, but its history will be preserved.');"
                                                            >
                                                                @csrf

                                                                <button
                                                                    type="submit"
                                                                    class="block w-full px-4 py-3 text-left text-sm font-medium text-red-700 hover:bg-red-50"
                                                                >
                                                                    Archive
                                                                </button>
                                                            </form>
                                                        @endif

                                                        @if ($consentTemplate->status === 'archived')
                                                            <div class="px-4 py-3 text-sm text-gray-500">
                                                                Archived template
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
