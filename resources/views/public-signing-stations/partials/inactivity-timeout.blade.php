{{-- PUBLIC_STATION_INACTIVITY_TIMEOUT --}}
@php
    $timeoutSeconds = max(
        30,
        (int) config('public-signing-stations.inactivity_timeout_seconds', 120)
    );

    $timeoutFormId = $timeoutFormId ?? 'public-station-timeout-form';
@endphp

@if (! empty($cancelAction))
    <form
        id="{{ $timeoutFormId }}"
        method="POST"
        action="{{ $cancelAction }}"
        class="hidden"
        aria-hidden="true"
    >
        @csrf
        <input type="hidden" name="timed_out" value="1">
    </form>
@endif

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const timeoutMilliseconds = @json($timeoutSeconds * 1000);
        const timeoutForm = document.getElementById(@json($timeoutFormId));
        const redirectUrl = @json($redirectUrl ?? null);

        let timeoutHandle = null;
        let flowFinished = false;

        const stopTimer = () => {
            if (timeoutHandle !== null) {
                window.clearTimeout(timeoutHandle);
                timeoutHandle = null;
            }
        };

        const expireKioskFlow = () => {
            if (flowFinished) {
                return;
            }

            flowFinished = true;
            stopTimer();

            if (timeoutForm) {
                timeoutForm.submit();
                return;
            }

            if (redirectUrl) {
                window.location.replace(redirectUrl);
            }
        };

        const resetTimer = () => {
            if (flowFinished) {
                return;
            }

            stopTimer();
            timeoutHandle = window.setTimeout(
                expireKioskFlow,
                timeoutMilliseconds
            );
        };

        [
            'pointerdown',
            'keydown',
            'input',
            'change',
            'touchstart',
            'scroll',
        ].forEach((eventName) => {
            document.addEventListener(eventName, resetTimer, {
                passive: true,
            });
        });

        document.querySelectorAll('form').forEach((form) => {
            if (form === timeoutForm) {
                return;
            }

            form.addEventListener('submit', () => {
                flowFinished = true;
                stopTimer();
            });
        });

        resetTimer();
    });
</script>
