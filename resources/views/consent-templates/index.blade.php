<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    New Consent
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Choose the workflow before opening the template builder.
                </p>
            </div>

            <a
                href="{{ route('consent-templates.manage') }}"
                class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Manage Existing Templates
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-xl border border-green-300 bg-green-50 px-5 py-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-300 bg-red-50 px-5 py-4 text-red-800">
                    <p class="font-semibold">
                        The action could not be completed:
                    </p>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-8 text-center sm:px-10">
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-indigo-600">
                        Start from scratch
                    </p>

                    <h1 class="mt-3 text-3xl font-bold tracking-tight text-gray-950 sm:text-4xl">
                        What type of consent are you creating?
                    </h1>

                    <p class="mx-auto mt-3 max-w-2xl text-base leading-7 text-gray-600">
                        Your choice prepares the correct template workflow. Signer details and deadlines are added later when an individual consent record is created.
                    </p>
                </div>

                <div class="grid gap-6 p-6 md:grid-cols-3 sm:p-10">
                    <a
                        href="{{ route('consent-templates.individual.create') }}"
                        class="group flex min-h-80 flex-col rounded-3xl border-2 border-blue-200 bg-blue-50 p-7 transition duration-200 hover:-translate-y-1 hover:border-blue-500 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-blue-200"
                    >
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-sm">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0" />
                            </svg>
                        </div>

                        <div class="mt-7">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">
                                Named signer workflow
                            </p>

                            <h2 class="mt-2 text-2xl font-bold text-gray-950">
                                New Individual Consent
                            </h2>

                            <p class="mt-3 leading-7 text-gray-700">
                                Build a reusable form for one named signer at a time. After publishing, create a consent record with the signer, deadline, email delivery and WhatsApp sharing.
                            </p>
                        </div>

                        <div class="mt-auto pt-8">
                            <span class="inline-flex items-center gap-2 font-bold text-blue-700">
                                Build template and signer record

                                <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6" />
                                </svg>
                            </span>
                        </div>
                    </a>

                    <a
                        href="{{ route('consent-campaigns.select-template') }}"
                        class="group flex min-h-80 flex-col rounded-3xl border-2 border-emerald-200 bg-emerald-50 p-7 transition duration-200 hover:-translate-y-1 hover:border-emerald-500 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-emerald-200"
                    >
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-sm">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.205-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                            </svg>
                        </div>

                        <div class="mt-7">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">
                                Multiple recipient workflow
                            </p>

                            <h2 class="mt-2 text-2xl font-bold text-gray-950">
                                New Bulk Consent
                            </h2>

                            <p class="mt-3 leading-7 text-gray-700">
                                Choose one published template and send separate secure consent requests to as many as 20 people in one campaign.
                            </p>
                        </div>

                        <div class="mt-auto pt-8">
                            <span class="inline-flex items-center gap-2 font-bold text-emerald-700">
                                Choose template and recipients

                                <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6" />
                                </svg>
                            </span>
                        </div>
                    </a>

                    <a
                        href="{{ route('consent-templates.create', [
                            'type' => \App\Models\ConsentTemplate::USAGE_SIGNING_STATION,
                        ]) }}"
                        class="group flex min-h-80 flex-col rounded-3xl border-2 border-purple-200 bg-purple-50 p-7 transition duration-200 hover:-translate-y-1 hover:border-purple-500 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-purple-200"
                    >
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-purple-600 text-white shadow-sm">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17.25v1.007c0 .597-.237 1.17-.659 1.591L6.75 21.44m8.25-4.19v1.007c0 .597.237 1.17.659 1.591l1.591 1.591M4.5 3.75h15A1.5 1.5 0 0 1 21 5.25v9.75a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 15V5.25a1.5 1.5 0 0 1 1.5-1.5Z" />
                            </svg>
                        </div>

                        <div class="mt-7">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-purple-700">
                                Shared public workflow
                            </p>

                            <h2 class="mt-2 text-2xl font-bold text-gray-950">
                                New Public Consent
                            </h2>

                            <p class="mt-3 leading-7 text-gray-700">
                                Build a reusable form for a public signing station, kiosk or shared tablet where multiple people complete separate consent records.
                            </p>
                        </div>

                        <div class="mt-auto pt-8">
                            <span class="inline-flex items-center gap-2 font-bold text-purple-700">
                                Open public template builder

                                <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6" />
                                </svg>
                            </span>
                        </div>
                    </a>
                </div>

                <div class="border-t border-gray-200 bg-gray-50 px-6 py-5 text-center sm:px-10">
                    <a
                        href="{{ route('consent-templates.manage') }}"
                        class="font-semibold text-indigo-700 hover:text-indigo-900 hover:underline"
                    >
                        View and manage existing templates
                    </a>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
