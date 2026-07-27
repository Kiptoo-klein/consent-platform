<x-layouts.signing-station
    :organization="$station->organization"
    :station="$station"
    :title="$station->name"
>
    <div class="w-full max-w-6xl">
        @if (! $station->active)
            <div class="mx-auto max-w-2xl overflow-hidden rounded-3xl border border-white/10 bg-white shadow-2xl shadow-black/30">
                <div class="p-8 text-center sm:p-12">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-amber-100 text-amber-700">
                        <svg
                            class="h-10 w-10"
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
                    </div>

                    <p class="mt-6 text-sm font-bold uppercase tracking-[0.2em] text-amber-600">
                        Signing paused
                    </p>

                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950">
                        Station unavailable
                    </h2>

                    <p class="mx-auto mt-4 max-w-lg text-base leading-7 text-slate-600">
                        This signing station is currently paused. Please contact a staff member for assistance.
                    </p>
                </div>
            </div>
        @else
            <div class="grid overflow-hidden rounded-[2rem] border border-white/10 bg-white shadow-2xl shadow-black/40 lg:grid-cols-[0.9fr_1.1fr]">
                {{-- Branded information panel --}}
                <section class="station-brand-panel relative overflow-hidden px-6 py-8 text-white sm:px-10 sm:py-12 lg:px-12 lg:py-14">
                    <div
                        class="pointer-events-none absolute inset-0"
                        aria-hidden="true"
                    >
                        <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full border border-white/10"></div>

                        <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full border border-white/10"></div>

                        <div
                            class="absolute bottom-0 left-0 h-48 w-48 -translate-x-1/2 translate-y-1/2 rounded-full blur-2xl"
                            style="
                                background-color:
                                    color-mix(
                                        in srgb,
                                        var(--station-accent) 15%,
                                        transparent
                                    );
                            "
                        ></div>
                    </div>

                    <div class="relative flex h-full flex-col">
                        <div>
                            <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.18em] text-white/90">
                                <span
                                    class="h-2 w-2 rounded-full"
                                    style="background-color: var(--station-accent);"
                                ></span>

                                Secure digital consent
                            </div>

                            <h2 class="mt-7 text-4xl font-bold tracking-tight sm:text-5xl">
                                Welcome
                            </h2>

                            <p class="mt-5 max-w-md text-base leading-7 text-white/80 sm:text-lg">
                                You will first review the consent document before entering any personal information.
                            </p>
                        </div>

                        <div class="mt-10">
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-white/70">
                                Consent document
                            </p>

                            <div class="mt-4 rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-white">
                                        <svg
                                            class="h-6 w-6"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M19.5 14.25v-2.625A3.375 3.375 0 0 0 16.125 8.25h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m0 12.75h7.5m-7.5 3h4.5m-6-15.75H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.625a9.375 9.375 0 0 0-9.375-9.375Z"
                                            />
                                        </svg>
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-white/70">
                                            Document
                                        </p>

                                        <p class="mt-1 text-lg font-bold leading-6 text-white">
                                            {{ $station->consentTemplate?->title ?? 'Consent form' }}
                                        </p>

                                       <p class="mt-2 text-sm leading-6 text-white/75">
    {{
        $station->consentTemplate?->description
        ?: 'Please review this consent document carefully before continuing.'
    }}
</p>

                                        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-white/80">
                                            <span class="inline-flex items-center gap-1.5">
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
                                                        stroke-width="1.8"
                                                        d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                                    />
                                                </svg>

                                                About 2 minutes
                                            </span>

                                            <span class="inline-flex items-center gap-1.5">
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
                                                        stroke-width="1.8"
                                                        d="M9 12.75 11.25 15 15 9.75m3-3.75A2.25 2.25 0 0 1 20.25 8.25v9A2.25 2.25 0 0 1 18 19.5H6A2.25 2.25 0 0 1 3.75 17.25v-9A2.25 2.25 0 0 1 6 6h2.25m7.5 0H18M9 6V4.875A1.875 1.875 0 0 1 10.875 3h2.25A1.875 1.875 0 0 1 15 4.875V6M9 6h6"
                                                    />
                                                </svg>

                                                Digital signature
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Progress indicator --}}
                        <div class="mt-10 lg:mt-auto lg:pt-12">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-semibold text-white">
                                    Start
                                </span>

                                <span class="text-white/70">
                                    Secure consent process
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-3 gap-2">
                                <div class="h-1.5 rounded-full bg-white"></div>
                                <div class="h-1.5 rounded-full bg-white/20"></div>
                                <div class="h-1.5 rounded-full bg-white/20"></div>
                            </div>

                            <div class="mt-7 grid grid-cols-3 gap-3 text-center text-xs font-semibold text-white/60">
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
                    </div>
                </section>

                {{-- Welcome panel --}}
                <section class="bg-white px-6 py-8 sm:px-10 sm:py-12 lg:px-12 lg:py-14">
                    <div class="mx-auto flex h-full max-w-xl flex-col justify-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                            <svg
                                class="h-8 w-8"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M12 6.75a.75.75 0 0 1 .75.75v3.75H16.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1-.75-.75V7.5a.75.75 0 0 1 .75-.75Zm0-4.5a9.75 9.75 0 1 0 9.75 9.75A9.761 9.761 0 0 0 12 2.25Z"
                                />
                            </svg>
                        </div>

                        <p class="station-primary-text mt-7 text-sm font-bold uppercase tracking-[0.18em]">
                            Before you begin
                        </p>

                        <h3 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                            Review before providing your details
                        </h3>

                        <p class="mt-4 text-base leading-7 text-slate-600">
                            The next page contains the full consent information. Read it carefully before choosing to continue.
                        </p>

                        <div class="mt-8 space-y-4">
                            <div class="flex items-start gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-slate-700 shadow-sm">
                                    <span class="font-bold">
                                        1
                                    </span>
                                </div>

                                <div>
                                    <p class="font-bold text-slate-900">
                                        Review the consent
                                    </p>

                                    <p class="mt-1 text-sm leading-6 text-slate-600">
                                        Read the complete document and confirm that you have reviewed it.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-slate-700 shadow-sm">
                                    <span class="font-bold">
                                        2
                                    </span>
                                </div>

                                <div>
                                    <p class="font-bold text-slate-900">
                                        Enter your information
                                    </p>

                                    <p class="mt-1 text-sm leading-6 text-slate-600">
                                        Provide the details required to identify your consent record.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-slate-700 shadow-sm">
                                    <span class="font-bold">
                                        3
                                    </span>
                                </div>

                                <div>
                                    <p class="font-bold text-slate-900">
                                        Sign and submit
                                    </p>

                                    <p class="mt-1 text-sm leading-6 text-slate-600">
                                        Draw your signature and complete the consent process.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <a
                            href="{{ route(
                                'public-signing-stations.review',
                                $station->station_token
                            ) }}"
                            class="station-submit-button group mt-8 inline-flex w-full items-center justify-center gap-3 rounded-2xl px-6 py-4 text-base font-bold text-white shadow-lg shadow-slate-950/20 transition duration-200 hover:-translate-y-0.5 hover:shadow-xl focus:outline-none active:translate-y-0"
                        >
                            <span>
                                Review Consent
                            </span>

                            <svg
                                class="h-5 w-5 transition-transform duration-200 group-hover:translate-x-1"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"
                                />
                            </svg>
                        </a>

                        <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                            <div class="flex items-start gap-3">
                                <svg
                                    class="mt-0.5 h-5 w-5 shrink-0 text-emerald-700"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M16.5 10.5V6.75a4.5 4.5 0 0 0-9 0v3.75m-.75 11.25h10.5A2.25 2.25 0 0 0 19.5 19.5v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"
                                    />
                                </svg>

                                <p class="text-sm leading-6 text-emerald-800">
                                    No personal information is collected until after you review the document.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-xs font-medium text-slate-300">
                <span>
                    Secure consent record
                </span>

                <span>
                    Privacy protected
                </span>

                <span>
                    Digital signature
                </span>
            </div>
        @endif
    </div>
</x-layouts.signing-station>
