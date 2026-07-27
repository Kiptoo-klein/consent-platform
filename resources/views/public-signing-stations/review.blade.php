<x-layouts.signing-station
    :organization="$station->organization"
    :station="$station"
    :title="$station->consentTemplate?->title ?? 'Review Consent'"
>
    @php
        $consentText =
            data_get($publishedVersion, 'consent_text')
            ?? data_get(
                $publishedVersion,
                'template_schema.consent_text'
            )
            ?? data_get(
                $publishedVersion,
                'template_schema.content'
            );
    @endphp

    <div class="w-full max-w-5xl">
        <div class="overflow-hidden rounded-[2rem] border border-white/10 bg-white shadow-2xl shadow-black/40">
            {{-- Page heading --}}
            <header class="station-brand-panel relative overflow-hidden px-6 py-8 text-white sm:px-10 sm:py-10">
                <div
                    class="pointer-events-none absolute inset-0"
                    aria-hidden="true"
                >
                    <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full border border-white/10"></div>

                    <div class="absolute -right-4 -top-4 h-32 w-32 rounded-full border border-white/10"></div>
                </div>

                <div class="relative">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/70">
                                Step 1 of 3
                            </p>

                            <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                                Review the consent document
                            </h1>

                            <p class="mt-3 max-w-2xl text-base leading-7 text-white/80">
                                Read the information carefully. You will provide your personal details on the next step.
                            </p>
                        </div>

                        <div class="shrink-0 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur">
                            <p class="text-xs font-semibold uppercase tracking-wide text-white/60">
                                Document
                            </p>

                            <p class="mt-1 max-w-xs font-bold text-white">
                                {{ $station->consentTemplate?->title ?? 'Consent form' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-3 gap-2">
                        <div class="h-1.5 rounded-full bg-white"></div>
                        <div class="h-1.5 rounded-full bg-white/20"></div>
                        <div class="h-1.5 rounded-full bg-white/20"></div>
                    </div>

                    <div class="mt-3 grid grid-cols-3 gap-3 text-center text-xs font-semibold text-white/60">
                        <div class="text-white">
                            Review
                        </div>

                        <div>
                            Details
                        </div>

                        <div>
                            Sign
                        </div>
                    </div>
                </div>
            </header>

            <div class="bg-slate-50 px-4 py-5 sm:px-8 sm:py-8">
                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 9v3.75m9-1.5A9 9 0 1 1 3 11.25a9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12V16.5Z"
                                    />
                                </svg>
                            </div>

                            <div>
                                <h2 class="text-sm font-bold text-red-900">
                                    Please confirm your review
                                </h2>

                                <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700">
                                    @foreach ($errors->all() as $error)
                                        <li>
                                            {{ $error }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Consent document --}}
                <article class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 py-5 sm:px-8">
                        <p class="station-primary-text text-xs font-bold uppercase tracking-[0.18em]">
                            Consent information
                        </p>

                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">
                            {{ $station->consentTemplate?->title ?? 'Consent form' }}
                        </h2>

                        @if ($station->consentTemplate?->description)
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ $station->consentTemplate->description }}
                            </p>
                        @endif
                    </div>

                    <div class="px-6 py-7 sm:px-8 sm:py-9">
                        @if ($consentText)
                            <div class="prose prose-slate max-w-none whitespace-pre-line text-base leading-8 text-slate-700">
                                {{ $consentText }}
                            </div>
                        @else
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                                <div class="flex items-start gap-3">
                                    <svg
                                        class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        aria-hidden="true"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M12 9v3.75m9-1.5A9 9 0 1 1 3 11.25a9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12V16.5Z"
                                        />
                                    </svg>

                                    <div>
                                        <p class="font-bold text-amber-900">
                                            Document unavailable
                                        </p>

                                        <p class="mt-1 text-sm leading-6 text-amber-800">
                                            The published consent content could not be displayed. Please contact a staff member.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </article>

                {{-- Review confirmation --}}
                <div class="mt-6 space-y-3">
                    <form
                        method="POST"
                        action="{{ route('public-signing-stations.continue', [
                            'stationToken' => $station->station_token,
                        ]) }}"
                    >
                        @csrf

                        <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4">
                            <input
                                type="checkbox"
                                name="review_confirmed"
                                value="1"
                                required
                                class="mt-1 rounded border-gray-300"
                            >

                            <span class="text-sm leading-6 text-gray-700">
                                I confirm that I have reviewed and understood this consent document.
                            </span>
                        </label>

                        @error('review_confirmed')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                        <button
                            type="submit"
                            class="mt-6 inline-flex w-full items-center justify-center rounded-lg px-5 py-3 font-semibold text-white"
                            style="background-color: {{ $organization->primary_color ?? '#2563eb' }}"
                        >
                            Continue to your details
                        </button>
                    </form>

                    <form
                        method="POST"
                        action="{{ route('public-signing-stations.cancel', [
                            'stationToken' => $station->station_token,
                        ]) }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Cancel and Return Home
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- PUBLIC_STATION_REVIEW_TIMEOUT_INCLUDE --}}
    @include('public-signing-stations.partials.inactivity-timeout', [
        'redirectUrl' => route(
            'public-signing-stations.show',
            $station->station_token
        ),
        'timeoutFormId' => 'public-station-review-timeout-form',
    ])

</x-layouts.signing-station>
