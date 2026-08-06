<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Preview Working Copy
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

    @php
        $schema = $consentTemplate->template_schema ?? [];
        $consentHtml = $schema['consent_html'] ?? null;
        $consentText = $schema['consent_text'] ?? '';
        $additionalFields = $schema['additional_fields'] ?? [];

        $nextVersionNumber =
            ($consentTemplate->latestVersion?->version_number ?? 0) + 1;
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">

            @if ($consentTemplate->has_unpublished_changes)
                <div class="rounded-lg border border-blue-300 bg-blue-50 p-5 text-blue-900">
                    <div class="flex items-start gap-3">
                        <div class="text-xl">
                            ℹ
                        </div>

                        <div>
                            <h3 class="font-semibold">
                                You are previewing unpublished changes
                            </h3>

                            @if ($consentTemplate->activeVersion)
                                <p class="mt-1 text-sm">
                                    Version
                                    {{ $consentTemplate->activeVersion->version_number }}
                                    remains live. Publishing this working copy will create
                                    Version {{ $nextVersionNumber }}.
                                </p>
                            @else
                                <p class="mt-1 text-sm">
                                    This template has never been published.
                                    Publishing it will create Version 1.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif ($consentTemplate->activeVersion)
                <div class="rounded-lg border border-green-300 bg-green-50 p-5 text-green-900">
                    <h3 class="font-semibold">
                        The working copy matches the live version
                    </h3>

                    <p class="mt-1 text-sm">
                        There are currently no unpublished changes.
                    </p>
                </div>
            @else
                <div class="rounded-lg border border-yellow-300 bg-yellow-50 p-5 text-yellow-900">
                    <h3 class="font-semibold">
                        Draft preview
                    </h3>

                    <p class="mt-1 text-sm">
                        This template has not been published yet.
                    </p>
                </div>
            @endif

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow">

                <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wide text-gray-500">
                                Working copy
                            </p>

                            <p class="mt-1 text-sm text-gray-600">
                                This preview shows what the next published version will contain.
                            </p>
                        </div>

                        @if ($consentTemplate->has_unpublished_changes)
                            <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800">
                                Unpublished Changes
                            </span>
                        @else
                            <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800">
                                Up to Date
                            </span>
                        @endif
                    </div>
                </div>

                <div class="space-y-8 p-6 sm:p-8">
                    <section>
                        <h1 class="text-3xl font-bold text-gray-900">
                            {{ $consentTemplate->title }}
                        </h1>

                        @if ($consentTemplate->description)
                            <p class="mt-3 leading-7 text-gray-600">
                                {{ $consentTemplate->description }}
                            </p>
                        @endif
                    </section>

                    <section>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Consent Information
                        </h2>

                        <x-consent-template-content
                            :html="$consentHtml"
                            :text="$consentText"
                            :organization-id="$consentTemplate->organization_id"
                            class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-5 leading-7 text-gray-800"
                        />
                    </section>

                    <section>
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="text-lg font-semibold text-gray-900">
                                Information Requested
                            </h2>

                            <span class="text-sm text-gray-500">
                                {{ count($additionalFields) }}
                                {{ count($additionalFields) === 1 ? 'field' : 'fields' }}
                            </span>
                        </div>

                        @if (empty($additionalFields))
                            <div class="mt-3 rounded-lg border border-dashed border-gray-300 p-6 text-center text-gray-500">
                                No additional information will be requested.
                            </div>
                        @else
                            <div class="mt-4 space-y-5">
                                @foreach ($additionalFields as $field)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-800">
                                            {{ $field['label'] ?? 'Unnamed field' }}

                                            @if ($field['required'] ?? false)
                                                <span class="text-red-600">
                                                    *
                                                </span>
                                            @endif
                                        </label>

                                        <input
                                            type="text"
                                            disabled
                                            placeholder="Patient response"
                                            class="mt-2 block w-full cursor-not-allowed rounded-lg border-gray-300 bg-gray-100 text-gray-500 shadow-sm"
                                        >

                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ ($field['required'] ?? false)
                                                ? 'Required field'
                                                : 'Optional field' }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section class="border-t border-gray-200 pt-6">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Signature
                        </h2>

                        <div class="mt-4 grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-800">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    disabled
                                    placeholder="Patient's full name"
                                    class="mt-2 block w-full cursor-not-allowed rounded-lg border-gray-300 bg-gray-100 text-gray-500 shadow-sm"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-800">
                                    Date
                                </label>

                                <input
                                    type="text"
                                    disabled
                                    value="{{ now()->copy()->timezone(config('app.display_timezone'))->format('M d, Y') }}"
                                    class="mt-2 block w-full cursor-not-allowed rounded-lg border-gray-300 bg-gray-100 text-gray-500 shadow-sm"
                                >
                            </div>
                        </div>

                        <div class="mt-5">
                            <label class="block text-sm font-medium text-gray-800">
                                Signature
                            </label>

                            <div class="mt-2 flex h-32 items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 text-gray-400">
                                Signature area
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <a
                        href="{{ route('consent-templates.index') }}"
                        class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-5 py-3 text-gray-700 hover:bg-gray-50"
                    >
                        Back to Templates
                    </a>

                    @if ($consentTemplate->activeVersion)
                        <a
                            href="{{ route('consent-templates.published', $consentTemplate) }}"
                            class="inline-flex justify-center rounded-lg bg-slate-700 px-5 py-3 text-white hover:bg-slate-800"
                        >
                            Compare with Live Version
                        </a>
                    @endif
                </div>

                @if ($consentTemplate->status !== 'archived')
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <a
                            href="{{ route('consent-templates.edit', $consentTemplate) }}"
                            class="inline-flex justify-center rounded-lg bg-indigo-600 px-5 py-3 text-white hover:bg-indigo-700"
                        >
                            Edit Working Copy
                        </a>

                        @if ($consentTemplate->has_unpublished_changes)
                            <form
                                method="POST"
                                action="{{ route('consent-templates.publish', $consentTemplate) }}"
                                x-data
                                data-preview-publish-confirmation
                            >
                                @csrf

                                <button
                                    type="button"
                                    class="w-full cursor-pointer rounded-lg bg-green-600 px-5 py-3 text-white hover:bg-green-700"
                                    x-on:click="$dispatch('open-modal', 'preview-publish-template-{{ $consentTemplate->id }}')"
                                >
                                    Publish Version {{ $nextVersionNumber }}
                                </button>

                                <x-action-confirmation-modal
                                    name="preview-publish-template-{{ $consentTemplate->id }}"
                                    title="Publish Version {{ $nextVersionNumber }}?"
                                    message="This working copy will become a new immutable published version and will be made live."
                                    confirm-text="Publish Version {{ $nextVersionNumber }}"
                                    variant="success"
                                />
                            </form>
                        @endif
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
