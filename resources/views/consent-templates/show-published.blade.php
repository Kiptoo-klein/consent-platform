<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Published Consent
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Ready to collect signatures
                </p>
            </div>

            <a
                href="{{ route('consent-templates.manage') }}"
                class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Back to Templates
            </a>
        </div>
    </x-slot>

    @php
        $schema = $publishedVersion->template_schema ?? [];
        $consentHtml = $schema['consent_html'] ?? null;
        $consentText = $schema['consent_text'] ?? '';
        $additionalFields = $schema['additional_fields'] ?? [];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">

            @if (session('success'))
                <div
                    class="mb-6 rounded-xl border border-green-300 bg-green-50 px-5 py-4 text-green-800"
                    role="status"
                >
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-6 rounded-lg border border-green-300 bg-green-50 p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold text-green-900">
                            Version {{ $publishedVersion->version_number }} is currently live
                        </p>

                        <p class="mt-1 text-sm text-green-700">
                            This published version is read-only and cannot be edited.
                        </p>
                    </div>

                    <span class="inline-flex w-fit rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                        Live
                    </span>
                </div>
            </div>

            <section
                class="mb-6 overflow-hidden rounded-2xl border border-indigo-200 bg-white shadow-sm"
                data-use-consent
            >
                <div class="border-b border-indigo-100 bg-indigo-50 px-6 py-5">
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-indigo-700">
                        Use this consent
                    </p>

                    <h2 class="mt-2 text-2xl font-bold text-gray-950">
                        How would you like to collect signatures?
                    </h2>

                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600">
                        Use this published consent for one person, several people, or a shared signing station.
                    </p>
                </div>

                <div class="grid gap-4 p-6 md:grid-cols-3">
                    <a
                        href="{{ route('consent-sessions.create', $consentTemplate) }}"
                        class="group rounded-xl border border-blue-200 bg-blue-50 p-5 transition hover:border-blue-400 hover:bg-blue-100"
                    >
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-600 text-white">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0" />
                            </svg>
                        </div>

                        <h3 class="mt-4 text-lg font-bold text-gray-950">
                            Send to One Person
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-gray-600">
                            Create a secure consent request for one recipient.
                        </p>

                        <span class="mt-4 inline-flex font-semibold text-blue-700">
                            Continue
                        </span>
                    </a>

                    <a
                        href="{{ route('consent-campaigns.create', $consentTemplate) }}"
                        class="group rounded-xl border border-emerald-200 bg-emerald-50 p-5 transition hover:border-emerald-400 hover:bg-emerald-100"
                    >
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-600 text-white">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198A11.944 11.944 0 0 1 12 21c-2.17 0-4.205-.576-5.963-1.584M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </div>

                        <h3 class="mt-4 text-lg font-bold text-gray-950">
                            Send to Multiple People
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-gray-600">
                            Send separate secure consent requests to several recipients.
                        </p>

                        <span class="mt-4 inline-flex font-semibold text-emerald-700">
                            Continue
                        </span>
                    </a>

                    <a
                        href="{{ route('signing-stations.create', [
                            'consent_template' => $consentTemplate->id,
                        ]) }}"
                        class="group rounded-xl border border-purple-200 bg-purple-50 p-5 transition hover:border-purple-400 hover:bg-purple-100"
                    >
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-600 text-white">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 3.75h15A1.5 1.5 0 0 1 21 5.25v9.75a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 15V5.25a1.5 1.5 0 0 1 1.5-1.5Z" />
                            </svg>
                        </div>

                        <h3 class="mt-4 text-lg font-bold text-gray-950">
                            Use at Signing Station
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-gray-600">
                            Use this consent on a shared kiosk, tablet, or QR station.
                        </p>

                        <span class="mt-4 inline-flex font-semibold text-purple-700">
                            Continue
                        </span>
                    </a>
                </div>
            </section>

            <div class="overflow-hidden rounded-lg bg-white shadow">

                <div class="border-b border-gray-200 p-6">
                    <div class="flex flex-col gap-6 sm:flex-row sm:justify-between">

                        <div>
                            <p class="text-sm font-medium uppercase tracking-wide text-gray-500">
                                Published template
                            </p>

                            <h1 class="mt-2 text-3xl font-bold text-gray-900">
                                {{ $publishedVersion->title }}
                            </h1>

                            @if ($publishedVersion->description)
                                <p class="mt-3 text-gray-600">
                                    {{ $publishedVersion->description }}
                                </p>
                            @endif
                        </div>

                        <div class="min-w-64 rounded-lg bg-gray-50 p-4 text-sm">
                            <dl class="space-y-3">

                                <div>
                                    <dt class="font-medium text-gray-500">
                                        Version
                                    </dt>

                                    <dd class="mt-1 text-gray-900">
                                        Version {{ $publishedVersion->version_number }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="font-medium text-gray-500">
                                        Published
                                    </dt>

                                    <dd class="mt-1 text-gray-900">
                                        {{ $publishedVersion->published_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y H:i') }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="font-medium text-gray-500">
                                        Published by
                                    </dt>

                                    <dd class="mt-1 text-gray-900">
                                        {{ $publishedVersion->publisher?->name ?? 'Unknown user' }}
                                    </dd>
                                </div>

                            </dl>
                        </div>

                    </div>
                </div>

                <div class="border-b border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Consent Text
                    </h2>

                    <x-consent-template-content
                        :html="$consentHtml"
                        :text="$consentText"
                        :organization-id="$consentTemplate->organization_id"
                        class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-5 leading-7 text-gray-800"
                    />
                </div>

                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Additional Fields
                        </h2>

                        <span class="text-sm text-gray-500">
                            {{ count($additionalFields) }}
                            {{ count($additionalFields) === 1 ? 'field' : 'fields' }}
                        </span>
                    </div>

                    @if (empty($additionalFields))
                        <div class="mt-4 rounded-lg border border-dashed border-gray-300 p-8 text-center text-gray-500">
                            No additional fields were included in this version.
                        </div>
                    @else
                        <div class="mt-4 space-y-4">

                            @foreach ($additionalFields as $field)
                                <div class="rounded-lg border border-gray-200 p-4">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                                        <div>
                                            <p class="font-medium text-gray-900">
                                                {{ $field['label'] ?? 'Unnamed field' }}
                                            </p>

                                            <p class="mt-1 text-xs text-gray-500">
                                                Field ID:
                                                {{ $field['id'] ?? 'Not available' }}
                                            </p>
                                        </div>

                                        @if ($field['required'] ?? false)
                                            <span class="inline-flex w-fit rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700">
                                                Required
                                            </span>
                                        @else
                                            <span class="inline-flex w-fit rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
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

            @if ($consentTemplate->has_unpublished_changes)
                <div class="mt-6 rounded-lg border border-blue-300 bg-blue-50 p-5">
                    <p class="font-semibold text-blue-900">
                        A newer working copy is being prepared
                    </p>

                    <p class="mt-1 text-sm text-blue-700">
                        The changes in the working copy are not displayed here.
                        Version {{ $publishedVersion->version_number }} remains live until a new version is published.
                    </p>

                    <a
                        href="{{ route('consent-templates.edit', $consentTemplate) }}"
                        class="mt-4 inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
                    >
                        Edit Working Copy
                    </a>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
