<x-layouts.public-consent
    title="Consent Submitted"
    :organization="$consentSession->organization"
    :organization-name="$consentSession->organization?->name ?? config('app.name')"
>
    @php
        $signingStation = $consentSession->signingStation;

        $resetSeconds = $signingStation
            ? max((int) $signingStation->auto_reset_seconds, 1)
            : null;

        $stationUrl = $signingStation
            ? route(
                'public-signing-stations.show',
                $signingStation->station_token
            )
            : null;
    @endphp

    <div class="mx-auto w-full max-w-3xl">
        <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-xl shadow-gray-200/60">
            <div class="bg-gradient-to-br from-emerald-600 via-emerald-600 to-teal-700 px-6 py-10 text-center text-white sm:px-10 sm:py-12">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/15 ring-1 ring-white/25 backdrop-blur">
                    <svg
                        class="h-11 w-11"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.2"
                            d="m4.5 12.75 6 6 9-13.5"
                        />
                    </svg>
                </div>

                <p class="mt-6 text-sm font-bold uppercase tracking-[0.2em] text-emerald-100">
                    Submission complete
                </p>

                <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                    Consent submitted successfully
                </h1>

                <p class="mx-auto mt-4 max-w-xl text-base leading-7 text-emerald-50">
                    The consent response and electronic signature have been securely recorded.
                </p>
            </div>

            <div class="p-6 sm:p-10">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-gray-500">
                            Signer
                        </p>

                        <p class="mt-2 break-words text-base font-semibold text-gray-900">
                            {{ $consentSession->signer_name }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-gray-500">
                            Consent
                        </p>

                        <p class="mt-2 break-words text-base font-semibold text-gray-900">
                            {{ $consentSession->consentTemplate?->title ?? 'Consent Form' }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-gray-500">
                            Completed
                        </p>

                        <p class="mt-2 text-base font-semibold text-gray-900">
                            {{ $consentSession->completed_at?->copy()?->timezone(config('app.display_timezone'))?->format('M d, Y H:i') ?? 'Just now' }}
                        </p>
                    </div>
                </div>

                @if ($signingStation)
                    <div class="mt-8 rounded-2xl border border-indigo-200 bg-indigo-50 p-5 sm:p-6">
                        <div class="flex items-start gap-4">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-700">
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
                                        d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                    />
                                </svg>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-indigo-950">
                                    Preparing for the next signer
                                </p>

                                <p class="mt-1 text-sm leading-6 text-indigo-800">
                                    This station will securely reset in
                                    <span
                                        id="reset-countdown"
                                        class="font-bold tabular-nums"
                                    >
                                        {{ $resetSeconds }}
                                    </span>
                                    seconds.
                                </p>

                                <div
                                    class="mt-4 h-2 overflow-hidden rounded-full bg-indigo-200"
                                    aria-hidden="true"
                                >
                                    <div
                                        id="reset-progress"
                                        class="h-full w-full origin-left rounded-full bg-indigo-600 transition-transform duration-1000 ease-linear"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
                        <a
                            id="next-signer-button"
                            href="{{ $stationUrl }}"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-6 py-4 text-base font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200 sm:w-auto"
                        >
                            <span>Next signer</span>

                            <svg
                                class="h-5 w-5"
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
                    </div>

                    <p class="mt-5 text-center text-xs leading-5 text-gray-500">
                        Personal details from this session will not be carried into the next consent.
                    </p>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const totalSeconds = Number(@json($resetSeconds));
                            const destination = @json($stationUrl);

                            const countdown =
                                document.getElementById('reset-countdown');

                            const progress =
                                document.getElementById('reset-progress');

                            const nextSignerButton =
                                document.getElementById('next-signer-button');

                            let remaining = totalSeconds;
                            let redirected = false;

                            function redirectToStation() {
                                if (redirected) {
                                    return;
                                }

                                redirected = true;

                                window.location.replace(destination);
                            }

                            function updateDisplay() {
                                if (countdown) {
                                    countdown.textContent =
                                        Math.max(remaining, 0);
                                }

                                if (progress && totalSeconds > 0) {
                                    const percentage =
                                        Math.max(remaining, 0) / totalSeconds;

                                    progress.style.transform =
                                        `scaleX(${percentage})`;
                                }
                            }

                            updateDisplay();

                            const timer = window.setInterval(function () {
                                remaining -= 1;
                                updateDisplay();

                                if (remaining <= 0) {
                                    window.clearInterval(timer);
                                    redirectToStation();
                                }
                            }, 1000);

                            if (nextSignerButton) {
                                nextSignerButton.addEventListener(
                                    'click',
                                    function () {
                                        window.clearInterval(timer);
                                    }
                                );
                            }

                            window.addEventListener('pageshow', function (event) {
                                if (event.persisted) {
                                    redirectToStation();
                                }
                            });
                        });
                    </script>
                @else
                    <div class="mt-8 rounded-2xl border border-blue-200 bg-blue-50 p-5 text-center">
                        <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-100 text-blue-700">
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
                                    d="M9 12.75 11.25 15 15 9.75m3-3.75A2.25 2.25 0 0 1 20.25 8.25v9A2.25 2.25 0 0 1 18 19.5H6A2.25 2.25 0 0 1 3.75 17.25v-9A2.25 2.25 0 0 1 6 6h2.25m7.5 0H18M9 6V4.875A1.875 1.875 0 0 1 10.875 3h2.25A1.875 1.875 0 0 1 15 4.875V6M9 6h6"
                                />
                            </svg>
                        </div>

                        <p class="mt-4 font-bold text-blue-950">
                            Consent record complete
                        </p>

                        <p class="mt-2 text-sm leading-6 text-blue-800">
                            This consent has been completed and locked. It can no longer be changed.
                        </p>
                    </div>

                    <p class="mt-6 text-center text-sm text-gray-500">
                        You may now safely close this page.
                    </p>
                @endif
            </div>
        </div>
    </div>
</x-layouts.public-consent>
