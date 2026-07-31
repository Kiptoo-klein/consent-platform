<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    New Bulk Consent
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Choose one published template to send to up to 20 people.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('consent-campaigns.index') }}"
                    class="inline-flex justify-center rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-100"
                >
                    Bulk Campaigns
                </a>

                <a
                    href="{{ route('consent-templates.new') }}"
                    class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Change Consent Type
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800">
                    <ul class="list-disc space-y-1 pl-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-5">
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-emerald-700">
                        Multiple-recipient workflow
                    </p>

                    <h1 class="mt-2 text-2xl font-bold text-gray-950">
                        Select a published template
                    </h1>

                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600">
                        Every recipient will receive a different secure signing link and an independent consent record, status, signature, PDF, and audit trail.
                    </p>
                </div>

                @if ($consentTemplates->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <h2 class="text-lg font-bold text-gray-950">
                            No published individual templates are available
                        </h2>

                        <p class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-gray-600">
                            Publish an individual or combined-use template before starting a bulk consent campaign.
                        </p>

                        <a
                            href="{{ route('consent-templates.new') }}"
                            class="mt-6 inline-flex rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            Create a Consent Template
                        </a>
                    </div>
                @else
                    <div class="divide-y divide-gray-200">
                        @foreach ($consentTemplates as $consentTemplate)
                            <article class="flex flex-col gap-5 px-6 py-6 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-lg font-bold text-gray-950">
                                            {{ $consentTemplate->title }}
                                        </h2>

                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-bold text-green-700">
                                            Published
                                        </span>
                                    </div>

                                    @if ($consentTemplate->description)
                                        <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600">
                                            {{ $consentTemplate->description }}
                                        </p>
                                    @endif

                                    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-xs font-medium text-gray-500">
                                        <span>
                                            Category:
                                            {{ $consentTemplate->category ?: 'Uncategorized' }}
                                        </span>

                                        <span>
                                            Version:
                                            {{ $consentTemplate->activeVersion?->version_number ?? '—' }}
                                        </span>
                                    </div>
                                </div>

                                <a
                                    href="{{ route('consent-campaigns.create', $consentTemplate) }}"
                                    class="inline-flex shrink-0 items-center justify-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700"
                                >
                                    Use for Bulk Consent
                                </a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
