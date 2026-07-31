@php
    $kioskHeartbeatUrl =
        request()
            ->attributes
            ->get(
                'kioskHeartbeatUrl'
            );

    $kioskHeartbeatSeconds =
        (int) request()
            ->attributes
            ->get(
                'kioskHeartbeatSeconds',
                45
            );
@endphp

@if (
    is_string($kioskHeartbeatUrl)
    && $kioskHeartbeatUrl !== ''
)
    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                const heartbeatUrl =
                    @json($kioskHeartbeatUrl);

                const heartbeatDelay =
                    Math.max(
                        15,
                        @json($kioskHeartbeatSeconds)
                    ) * 1000;

                let leaseLost = false;

                function showLeaseLost(message) {
                    if (leaseLost) {
                        return;
                    }

                    leaseLost = true;

                    const overlay =
                        document.createElement('div');

                    overlay.id =
                        'kiosk-device-lease-lost';

                    overlay.className =
                        'fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/85 px-4 backdrop-blur';

                    overlay.innerHTML = `
                        <div class="w-full max-w-lg rounded-3xl bg-white p-8 text-center shadow-2xl">
                            <p class="text-sm font-bold uppercase tracking-[0.18em] text-amber-600">
                                Kiosk access ended
                            </p>
                            <h2 class="mt-3 text-2xl font-bold text-slate-950">
                                This device no longer has a kiosk slot
                            </h2>
                            <p class="mt-4 text-sm leading-6 text-slate-600">
                                ${message}
                            </p>
                            <button
                                type="button"
                                class="mt-6 rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white"
                                onclick="window.location.reload()"
                            >
                                Try again
                            </button>
                        </div>
                    `;

                    document.body.appendChild(
                        overlay
                    );
                }

                async function sendHeartbeat() {
                    if (leaseLost) {
                        return;
                    }

                    const csrfToken =
                        document.querySelector(
                            'meta[name="csrf-token"]'
                        )?.getAttribute('content');

                    try {
                        const response =
                            await fetch(
                                heartbeatUrl,
                                {
                                    method:
                                        'POST',

                                    credentials:
                                        'same-origin',

                                    keepalive:
                                        true,

                                    headers: {
                                        'Accept':
                                            'application/json',

                                        'X-Requested-With':
                                            'XMLHttpRequest',

                                        ...(csrfToken
                                            ? {
                                                'X-CSRF-TOKEN':
                                                    csrfToken,
                                            }
                                            : {}),
                                    },
                                }
                            );

                        if (
                            response.status === 409
                            || response.status === 429
                        ) {
                            const data =
                                await response
                                    .json()
                                    .catch(
                                        () => ({})
                                    );

                            showLeaseLost(
                                data.message
                                || 'Another device is using the available kiosk capacity.'
                            );
                        }
                    } catch (error) {
                        /*
                         * A temporary network interruption must not
                         * immediately terminate an otherwise valid lease.
                         * The server-side expiry remains authoritative.
                         */
                    }
                }

                window.setInterval(
                    sendHeartbeat,
                    heartbeatDelay
                );

                document.addEventListener(
                    'visibilitychange',
                    function () {
                        if (
                            document.visibilityState
                            === 'visible'
                        ) {
                            sendHeartbeat();
                        }
                    }
                );
            }
        );
    </script>
@endif
