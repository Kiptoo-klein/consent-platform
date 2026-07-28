<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    New Individual Consent
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Choose a published template enabled for individual consent.
                </p>
            </div>

            <a
                href="{{ route('consent-sessions.index') }}"
                class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Back to Consent Records
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800">
                    <ul class="list-disc space-y-1 pl-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="overflow-hidden rounded-xl bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h1 class="text-xl font-bold text-gray-900">
                        Select a consent template
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Only live templates marked for individual consent are shown.
                    </p>
                </div>

                @if ($consentTemplates->isEmpty())
                    <div class="px-6 py-14 text-center">
                        <h2 class="text-lg font-semibold text-gray-800">
                            No individual-consent templates are available
                        </h2>

                        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-gray-500">
                            Create or edit a template, select
                            <span class="font-semibold">Individual consent</span>,
                            and publish it before creating a signer-specific record.
                        </p>

                        <a
                            href="{{ route('consent-templates.index') }}"
                            class="mt-6 inline-flex rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            Manage Consent Templates
                        </a>
                    </div>
                @else
                    <div class="divide-y divide-gray-200">
                        @foreach ($consentTemplates as $consentTemplate)
                            <article class="flex flex-col gap-5 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="font-semibold text-gray-900">
                                            {{ $consentTemplate->title }}
                                        </h2>

                                        <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-medium text-indigo-800">
                                            {{ $consentTemplate->usageLabel() }}
                                        </span>

                                        @if ($consentTemplate->category)
                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                                {{ $consentTemplate->category }}
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-2 text-sm leading-6 text-gray-500">
                                        {{ $consentTemplate->description ?: 'No description provided.' }}
                                    </p>

                                    <p class="mt-2 text-xs text-gray-500">
                                        Published version
                                        {{ $consentTemplate->activeVersion?->version_number }}
                                    </p>
                                </div>

                                <a
                                    href="{{ route('consent-sessions.create', $consentTemplate) }}"
                                    class="inline-flex shrink-0 items-center justify-center rounded-lg bg-green-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700"
                                >
                                    Use Template
                                </a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
