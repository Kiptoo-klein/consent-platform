<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Version History
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    {{ $consentTemplate->title }}
                </p>
            </div>

            <a
                href="{{ route('consent-templates.index') }}"
                class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Back to Templates
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">

            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-wide text-gray-500">
                            Consent template
                        </p>

                        <h1 class="mt-2 text-2xl font-bold text-gray-900">
                            {{ $consentTemplate->title }}
                        </h1>

                        @if ($consentTemplate->description)
                            <p class="mt-2 text-gray-600">
                                {{ $consentTemplate->description }}
                            </p>
                        @endif
                    </div>

                    <div class="rounded-lg bg-gray-50 px-5 py-4">
                        <p class="text-sm text-gray-500">
                            Published versions
                        </p>

                        <p class="mt-1 text-2xl font-bold text-gray-900">
                            {{ $versions->count() }}
                        </p>
                    </div>
                </div>
            </div>

            @if ($versions->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 bg-white p-12 text-center shadow-sm">
                    <h2 class="text-xl font-semibold text-gray-800">
                        No Published Versions
                    </h2>

                    <p class="mt-2 text-gray-500">
                        This template has not been published yet.
                    </p>

                    @if ($consentTemplate->status !== 'archived')
                        <a
                            href="{{ route('consent-templates.edit', $consentTemplate) }}"
                            class="mt-6 inline-flex rounded-lg bg-indigo-600 px-5 py-3 text-white hover:bg-indigo-700"
                        >
                            Edit Draft
                        </a>
                    @endif
                </div>
            @else
                <div class="space-y-5">

                    @foreach ($versions as $version)
                        @php
                            $schema = $version->template_schema ?? [];
                            $consentText = $schema['consent_text'] ?? '';
                            $additionalFields = $schema['additional_fields'] ?? [];

                            $isActive =
                                (int) $consentTemplate->active_version_id ===
                                (int) $version->id;
                        @endphp

                        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">

                            <div class="border-b border-gray-200 p-6">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <h2 class="text-xl font-bold text-gray-900">
                                                Version {{ $version->version_number }}
                                            </h2>

                                            @if ($isActive)
                                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800">
                                                    Currently Live
                                                </span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                                                    Historical
                                                </span>
                                            @endif
                                        </div>

                                        <h3 class="mt-3 text-lg font-semibold text-gray-800">
                                            {{ $version->title }}
                                        </h3>

                                        @if ($version->description)
                                            <p class="mt-2 text-gray-600">
                                                {{ $version->description }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="min-w-56 rounded-lg bg-gray-50 p-4 text-sm">
                                        <dl class="space-y-3">
                                            <div>
                                                <dt class="font-medium text-gray-500">
                                                    Published
                                                </dt>

                                                <dd class="mt-1 text-gray-900">
                                                    {{ $version->published_at?->format('M d, Y H:i') ?? 'Unknown' }}
                                                </dd>
                                            </div>

                                            <div>
                                                <dt class="font-medium text-gray-500">
                                                    Published by
                                                </dt>

                                                <dd class="mt-1 text-gray-900">
                                                    {{ $version->publisher?->name ?? 'Unknown user' }}
                                                </dd>
                                            </div>

                                            <div>
                                                <dt class="font-medium text-gray-500">
                                                    Additional fields
                                                </dt>

                                                <dd class="mt-1 text-gray-900">
                                                    {{ count($additionalFields) }}
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>

                                </div>
                            </div>

                            <div
                                x-data="{ open: false }"
                                class="p-6"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <p class="text-sm text-gray-500">
                                        This version is immutable and cannot be edited.
                                    </p>

                                    <button
                                        type="button"
                                        x-on:click="open = ! open"
                                        class="rounded-lg bg-slate-700 px-4 py-2 text-sm text-white hover:bg-slate-800"
                                    >
                                        <span x-show="! open">
                                            View Details
                                        </span>

                                        <span x-show="open" x-cloak>
                                            Hide Details
                                        </span>
                                    </button>
                                </div>

                                <div
                                    x-show="open"
                                    x-cloak
                                    class="mt-6 space-y-6"
                                >
                                    <div>
                                        <h4 class="font-semibold text-gray-900">
                                            Consent Text
                                        </h4>

                                        <div class="mt-3 whitespace-pre-wrap rounded-lg border border-gray-200 bg-gray-50 p-5 leading-7 text-gray-800">{{ $consentText }}</div>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between">
                                            <h4 class="font-semibold text-gray-900">
                                                Additional Fields
                                            </h4>

                                            <span class="text-sm text-gray-500">
                                                {{ count($additionalFields) }}
                                                {{ count($additionalFields) === 1 ? 'field' : 'fields' }}
                                            </span>
                                        </div>

                                        @if (empty($additionalFields))
                                            <div class="mt-3 rounded-lg border border-dashed border-gray-300 p-6 text-center text-gray-500">
                                                No additional fields were included in this version.
                                            </div>
                                        @else
                                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                                @foreach ($additionalFields as $field)
                                                    <div class="rounded-lg border border-gray-200 p-4">
                                                        <div class="flex items-start justify-between gap-3">
                                                            <div>
                                                                <p class="font-medium text-gray-900">
                                                                    {{ $field['label'] ?? 'Unnamed field' }}
                                                                </p>

                                                                <p class="mt-1 text-xs text-gray-500">
                                                                    ID:
                                                                    {{ $field['id'] ?? 'Not available' }}
                                                                </p>
                                                            </div>

                                                            @if ($field['required'] ?? false)
                                                                <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700">
                                                                    Required
                                                                </span>
                                                            @else
                                                                <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                                                                    Optional
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                        </div>
                    @endforeach

                </div>
            @endif

        </div>
    </div>
</x-app-layout>
