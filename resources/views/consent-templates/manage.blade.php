<x-app-layout>
    @php
        $showingArchived = $showingArchived ?? false;
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ $showingArchived
                    ? 'Archived Consent Templates'
                    : 'Consent Templates' }}
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
                                : 'Create, publish, and reuse consents for the way you want to collect signatures.' }}
                        </p>
                    </div>

                    @unless ($showingArchived)
                        <a
                            href="{{ route('consent-templates.create') }}"
                            class="inline-flex justify-center rounded-lg bg-blue-600 px-5 py-3 text-white hover:bg-blue-700"
                        >
                            + New Consent
                        </a>
                    @endunless
                </div>

                <form
                    method="GET"
                    action="{{ $showingArchived
                        ? route('consent-templates.archived')
                        : route('consent-templates.manage') }}"
                    class="mb-6 flex flex-col gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 md:flex-row md:items-end"
                >
                    <label class="min-w-0 flex-1">
                        <span class="block text-sm font-medium text-gray-700">
                            Search templates
                        </span>

                        <input
                            type="search"
                            name="search"
                            value="{{ $search ?? '' }}"
                            placeholder="Search by title, description, or category"
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                    </label>

                    @unless ($showingArchived)
                        <label class="md:w-56">
                            <span class="block text-sm font-medium text-gray-700">
                                Filter
                            </span>

                            <select
                                name="filter"
                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option
                                    value="all"
                                    @selected(($filter ?? 'all') === 'all')
                                >
                                    All templates
                                </option>

                                <option
                                    value="live"
                                    @selected(($filter ?? 'all') === 'live')
                                >
                                    Live
                                </option>

                                <option
                                    value="unpublished"
                                    @selected(($filter ?? 'all') === 'unpublished')
                                >
                                    Unpublished
                                </option>
                            </select>
                        </label>
                    @endunless

                    <button
                        type="submit"
                        class="inline-flex justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700"
                    >
                        Search
                    </button>

                    <a
                        href="{{ $showingArchived
                            ? route('consent-templates.archived')
                            : route('consent-templates.manage') }}"
                        class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100"
                    >
                        Clear
                    </a>
                </form>

                @if ($consentTemplates->isEmpty())
                    <div class="py-20 text-center">
                        <h2 class="mb-3 text-2xl font-semibold text-gray-700">
                            No Consent Templates Yet
                        </h2>

                        <p class="mb-8 text-gray-500">
                            Create your first consent template.
                        </p>

                        @unless ($showingArchived)
                            <a
                                href="{{ route('consent-templates.create') }}"
                                class="inline-flex rounded-lg bg-blue-600 px-6 py-3 text-white hover:bg-blue-700"
                            >
                                Create Your First Consent
                            </a>
                        @endunless
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="border-b bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left">
                                        Consent
                                    </th>

                                    <th class="px-6 py-4 text-left">
                                        Status
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
                                            @php
                                                $templateIsLive =
                                                    $consentTemplate->active_version_id !== null
                                                    && $consentTemplate->status === 'published';

                                                $latestPublishedVersion =
                                                    $consentTemplate->activeVersion
                                                    ?? $consentTemplate->latestVersion;

                                                if ($consentTemplate->status === 'archived') {
                                                    $templateStatusLabel = 'Archived';
                                                    $templateStatusColor = '#6B7280';
                                                } elseif ($templateIsLive) {
                                                    $templateStatusLabel = 'Published';
                                                    $templateStatusColor = '#16A34A';
                                                } elseif ($latestPublishedVersion) {
                                                    $templateStatusLabel = 'Offline';
                                                    $templateStatusColor = '#9CA3AF';
                                                } else {
                                                    $templateStatusLabel = 'Draft';
                                                    $templateStatusColor = '#D97706';
                                                }
                                            @endphp

                                            <div
                                                style="border-left: 4px solid {{ $templateStatusColor }}; padding-left: 0.75rem;"
                                            >
                                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                                    <p class="font-semibold text-gray-900">
                                                        {{ $consentTemplate->title }}
                                                    </p>

                                                </div>

                                                <p class="mt-1 text-sm text-gray-500">
                                                    {{ $consentTemplate->description ?: 'No description' }}
                                                </p>
                                            </div>
                                        </td>

                                        <td class="px-6 py-5">
                                            <div class="flex flex-col items-start gap-1.5">
                                                <span
                                                    class="inline-flex rounded-full px-3 py-1 text-sm font-semibold
                                                        {{ $templateIsLive
                                                            ? 'bg-green-100 text-green-800'
                                                            : ($consentTemplate->status === 'archived'
                                                                ? 'bg-gray-100 text-gray-700'
                                                                : ($latestPublishedVersion
                                                                    ? 'bg-gray-100 text-gray-700'
                                                                    : 'bg-amber-100 text-amber-800')) }}"
                                                >
                                                    {{ $templateStatusLabel }}

                                                    @if ($latestPublishedVersion)
                                                        · Version {{ $latestPublishedVersion->version_number }}
                                                    @endif
                                                </span>

                                                @if (
                                                    $templateIsLive
                                                    && $consentTemplate->has_unpublished_changes
                                                )
                                                    <span class="text-xs font-semibold text-amber-700">
                                                        Draft changes not yet published
                                                    </span>
                                                @elseif (
                                                    ! $templateIsLive
                                                    && $latestPublishedVersion
                                                    && $consentTemplate->status !== 'archived'
                                                )
                                                    <span class="text-xs text-gray-500">
                                                        Not available for new signatures
                                                    </span>
                                                @elseif (
                                                    ! $latestPublishedVersion
                                                    && $consentTemplate->status !== 'archived'
                                                )
                                                    <span class="text-xs text-gray-500">
                                                        Not yet published
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="px-6 py-5 text-gray-600">
                                            @if ($latestPublishedVersion)
                                                {{ $latestPublishedVersion->published_at?->copy()?->timezone(config('app.display_timezone'))?->format('M d, Y H:i') ?? 'Unknown' }}
                                            @else
                                                —
                                            @endif
                                        </td>

                                        <td class="px-6 py-5">
                                            <div
                                                x-data="{ open: false }"
                                                class="flex min-w-[15rem] items-center justify-center gap-2"
                                                x-bind:class="{ 'pb-64': open }"
                                            >
                                                @if ($consentTemplate->status === 'archived')
                                                    <form
                                                        method="POST"
                                                        action="{{ route('consent-templates.restore', $consentTemplate) }}"
                                                        class="w-36"
                                                        x-data
                                                        data-template-restore-confirmation
                                                        data-consent-primary-action="restore"
                                                    >
                                                        @csrf
                                                        @method('PATCH')

                                                        <button
                                                            type="button"
                                                            class="inline-flex w-full items-center justify-center whitespace-nowrap rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700"
                                                            x-on:click="$dispatch(
                                                                'open-modal',
                                                                'restore-template-{{ $consentTemplate->id }}'
                                                            )"
                                                        >
                                                            Restore
                                                        </button>

                                                        <x-action-confirmation-modal
                                                            name="restore-template-{{ $consentTemplate->id }}"
                                                            title="Restore consent template?"
                                                            message="The template will return to the active templates list. It will remain offline until you publish it again."
                                                            confirm-text="Restore template"
                                                            variant="success"
                                                        />
                                                    </form>
                                                @elseif ($templateIsLive)
                                                    <a
                                                        href="{{ route('consent-templates.published', $consentTemplate) }}"
                                                        class="inline-flex w-36 items-center justify-center whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                                        data-consent-primary-action="use"
                                                    >
                                                        Use Consent
                                                    </a>
                                                @else
                                                    <a
                                                        href="{{ route('consent-templates.edit', $consentTemplate) }}"
                                                        class="inline-flex w-36 items-center justify-center whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                                        data-consent-primary-action="edit"
                                                    >
                                                        Continue Editing
                                                    </a>
                                                @endif

                                                <div
                                                    class="relative w-24"
                                                    x-on:click.outside="open = false"
                                                    x-on:keydown.escape.window="open = false"
                                                >
                                                    <button
                                                        type="button"
                                                        x-on:click="open = ! open"
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
                                                        class="absolute right-0 top-full z-20 mt-2 w-64 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg"
                                                    >
                                                        <a
                                                            href="{{ route('consent-templates.preview', $consentTemplate) }}"
                                                            class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50"
                                                        >
                                                            Preview
                                                        </a>

                                                        @if (
                                                            $consentTemplate->status !== 'archived'
                                                            && $templateIsLive
                                                        )
                                                            <a
                                                                href="{{ route('consent-templates.edit', $consentTemplate) }}"
                                                                class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50"
                                                            >
                                                                Edit Consent
                                                            </a>
                                                        @endif

                                                        @if ($consentTemplate->status !== 'archived')
                                                            @if (
                                                                $consentTemplate->has_unpublished_changes
                                                                || $consentTemplate->active_version_id === null
                                                            )
                                                                <form
                                                                    method="POST"
                                                                    action="{{ route('consent-templates.publish', $consentTemplate) }}"
                                                                    class="border-t border-gray-100"
                                                                    x-data
                                                                    data-template-publish-confirmation
                                                                >
                                                                    @csrf

                                                                    <button
                                                                        type="button"
                                                                        class="block w-full px-4 py-3 text-left text-sm font-medium text-green-700 hover:bg-green-50"
                                                                        x-on:click="$dispatch(
                                                                            'open-modal',
                                                                            'publish-template-{{ $consentTemplate->id }}'
                                                                        )"
                                                                    >
                                                                        {{ $latestPublishedVersion
                                                                            ? 'Publish'
                                                                            : 'Publish Consent' }}
                                                                    </button>

                                                                    <x-action-confirmation-modal
                                                                        name="publish-template-{{ $consentTemplate->id }}"
                                                                        title="Publish consent template?"
                                                                        :message="$consentTemplate->has_unpublished_changes
                                                                            ? 'Publish these changes as a new immutable version?'
                                                                            : 'Make the latest published version live again?'"
                                                                        confirm-text="Publish template"
                                                                        variant="success"
                                                                    />
                                                                </form>
                                                            @else
                                                                <form
                                                                    method="POST"
                                                                    action="{{ route('consent-templates.unpublish', $consentTemplate) }}"
                                                                    class="border-t border-gray-100"
                                                                    x-data
                                                                    data-template-unpublish-confirmation
                                                                >
                                                                    @csrf

                                                                    <button
                                                                        type="button"
                                                                        class="block w-full px-4 py-3 text-left text-sm font-medium text-amber-700 hover:bg-amber-50"
                                                                        x-on:click="$dispatch(
                                                                            'open-modal',
                                                                            'unpublish-template-{{ $consentTemplate->id }}'
                                                                        )"
                                                                    >
                                                                        Take Offline
                                                                    </button>

                                                                    <x-action-confirmation-modal
                                                                        name="unpublish-template-{{ $consentTemplate->id }}"
                                                                        title="Take this template offline?"
                                                                        message="The template will no longer be available for new consent requests. Its published history will be preserved."
                                                                        confirm-text="Take template offline"
                                                                        variant="warning"
                                                                    />
                                                                </form>
                                                            @endif
                                                        @endif

                                                        @if ($latestPublishedVersion)
                                                            <a
                                                                href="{{ route('consent-templates.history', $consentTemplate) }}"
                                                                class="block border-t border-gray-100 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50"
                                                            >
                                                                Version History
                                                            </a>
                                                        @endif

                                                        @if (
                                                            $consentTemplate->status !== 'archived'
                                                            && $consentTemplate->active_version_id === null
                                                        )
                                                            <form
                                                                method="POST"
                                                                action="{{ route('consent-templates.archive', $consentTemplate) }}"
                                                                class="border-t border-gray-100"
                                                                x-data
                                                                data-template-archive-confirmation
                                                            >
                                                                @csrf

                                                                <button
                                                                    type="button"
                                                                    class="block w-full px-4 py-3 text-left text-sm font-medium text-red-700 hover:bg-red-50"
                                                                    x-on:click="$dispatch(
                                                                        'open-modal',
                                                                        'archive-template-{{ $consentTemplate->id }}'
                                                                    )"
                                                                >
                                                                    Archive
                                                                </button>

                                                                <x-action-confirmation-modal
                                                                    name="archive-template-{{ $consentTemplate->id }}"
                                                                    title="Archive consent template?"
                                                                    message="The template will be removed from the active templates list. Its versions, history and existing consent records will be preserved."
                                                                    confirm-text="Archive template"
                                                                    variant="danger"
                                                                />
                                                            </form>
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
