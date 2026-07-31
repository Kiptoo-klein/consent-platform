<x-layouts.public-consent
    title="Signature"
    :organization="$consentSession->organization"
    :organization-name="$consentSession->organization?->name ?? config('app.name')"
>
    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-300 bg-red-50 px-5 py-4 text-red-800">
            <p class="font-semibold">
                Please correct the following:
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($consentSession->isCancelled())
        <div class="rounded-xl border border-gray-300 bg-white p-8 text-center shadow-sm">
            <h2 class="text-2xl font-bold text-gray-900">
                Consent Record Unavailable
            </h2>

            <p class="mt-3 text-gray-600">
                This consent record has been cancelled and can no longer be completed.
            </p>
        </div>
    @elseif ($consentSession->isExpired())
        <div class="rounded-xl border border-amber-300 bg-white p-8 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-2xl text-amber-700">
                !
            </div>

            <h2 class="mt-5 text-2xl font-bold text-gray-900">
                Signing Deadline Expired
            </h2>

            <p class="mt-3 text-gray-600">
                This consent record expired
                {{ $consentSession->expires_at
                    ? 'on '.$consentSession->expires_at->format('M d, Y H:i')
                    : 'before it was completed' }}
                and can no longer be signed.
            </p>
        </div>
    @elseif ($consentSession->isCompleted())
        <div class="rounded-xl border border-green-300 bg-white p-8 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-2xl text-green-700">
                ✓
            </div>

            <h2 class="mt-5 text-2xl font-bold text-gray-900">
                Consent Already Submitted
            </h2>

            <p class="mt-3 text-gray-600">
                This consent record has already been completed.
            </p>

            <a
                href="{{ route('public-consent.completed', $consentSession->access_token) }}"
                class="mt-6 inline-flex justify-center rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-700"
            >
                View Confirmation
            </a>
        </div>
    @else
        <form
            id="signature-form"
            method="POST"
            action="{{ route('public-consent.complete', $consentSession->access_token) }}"
            class="overflow-hidden rounded-xl bg-white shadow-sm"
        >
            @csrf

            <input
                type="hidden"
                id="signature_data"
                name="signature_data"
                value="{{ old('signature_data') }}"
            >

            <div class="border-b border-gray-200 bg-gray-50 px-6 py-5">
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">
                    Final Step
                </p>

                <h1 class="mt-2 text-2xl font-bold text-gray-900">
                    Add Your Signature
                </h1>

                <p class="mt-2 text-gray-600">
                    Review the details and sign inside the box below.
                </p>
            </div>

            <div class="space-y-6 p-6">
                <div class="grid gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Signer
                        </p>

                        <p class="mt-1 font-medium text-gray-900">
                            {{ $consentSession->signer_name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Consent
                        </p>

                        <p class="mt-1 font-medium text-gray-900">
                            {{ $consentSession->consentTemplate?->title ?? 'Consent Form' }}
                        </p>
                    </div>
                </div>

                <div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <label
                                for="signature-canvas"
                                class="block text-sm font-semibold text-gray-900"
                            >
                                Draw your signature
                            </label>

                            <p class="mt-1 text-sm text-gray-500">
                                Use your mouse, touchpad, finger or stylus.
                            </p>
                        </div>

                        <button
                            type="button"
                            id="clear-signature"
                            class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Clear Signature
                        </button>
                    </div>

                    <div
                        id="signature-container"
                        class="mt-4 overflow-hidden rounded-xl border-2 border-dashed border-gray-300 bg-white"
                    >
                        <canvas
                            id="signature-canvas"
                            class="block h-64 w-full touch-none cursor-crosshair"
                            aria-label="Signature drawing area"
                        ></canvas>
                    </div>

                    <div class="mt-3 flex items-center justify-between gap-4 text-xs text-gray-500">
                        <span>
                            Sign above the line
                        </span>

                        <span id="signature-status">
                            No signature drawn
                        </span>
                    </div>

                    <div class="mt-3 border-t border-gray-300"></div>

                    @error('signature_data')
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                    By submitting this signature, you confirm that you reviewed the consent information and that this electronic signature represents your agreement.
                </div>

                <label
                    id="confirmation-container"
                    class="flex items-start gap-3 rounded-lg border border-gray-200 p-4 transition"
                >
                    <input
                        type="checkbox"
                        id="signature-confirmation"
                        name="confirmation"
                        value="1"
                        @checked(old('confirmation'))
                        aria-describedby="confirmation-error"
                        aria-invalid="{{ $errors->has('confirmation') ? 'true' : 'false' }}"
                        class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    >

                    <span class="text-sm text-gray-700">
                        I confirm that I am
                        <strong>{{ $consentSession->signer_name }}</strong>
                        and that the signature above is mine.
                    </span>
                </label>

                <p
                    id="confirmation-error"
                    class="-mt-4 text-sm text-red-600 {{ $errors->has('confirmation') ? '' : 'hidden' }}"
                    role="alert"
                    aria-live="polite"
                >
                    {{ $errors->first('confirmation')
                        ?: 'Please confirm that the signature is yours before submitting.' }}
                </p>

                <div class="grid gap-3 sm:grid-cols-2">
                    <a
                        href="{{ route('public-consent.show', $consentSession->access_token) }}"
                        class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-5 py-3 font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Back to Consent
                    </a>

                    <button
                        type="submit"
                        id="submit-signature"
                        disabled
                        class="inline-flex cursor-not-allowed justify-center rounded-lg bg-indigo-300 px-5 py-3 font-semibold text-white transition"
                    >
                        Submit Consent
                    </button>
                </div>

                <p class="text-center text-xs text-gray-400">
                    After submission, this consent record will be locked and cannot be changed.
                </p>
            </div>
        </form>

        <form
            method="POST"
            action="{{ route('public-consent.cancel', $consentSession->access_token) }}"
            class="mt-4"
        >
            @csrf

            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-lg border border-red-300 bg-white px-5 py-3 font-semibold text-red-700 transition hover:bg-red-50"
            >
                Cancel and Return Home
            </button>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const canvas = document.getElementById('signature-canvas');
                const container = document.getElementById('signature-container');
                const clearButton = document.getElementById('clear-signature');
                const submitButton = document.getElementById('submit-signature');
                const signatureInput = document.getElementById('signature_data');
                const signatureStatus = document.getElementById('signature-status');
                const form = document.getElementById('signature-form');
                const confirmationCheckbox =
                    document.getElementById('signature-confirmation');
                const confirmationContainer =
                    document.getElementById('confirmation-container');
                const confirmationError =
                    document.getElementById('confirmation-error');

                if (
                    !canvas ||
                    !container ||
                    !clearButton ||
                    !submitButton ||
                    !signatureInput ||
                    !signatureStatus ||
                    !form ||
                    !confirmationCheckbox ||
                    !confirmationContainer ||
                    !confirmationError
                ) {
                    return;
                }

                const context = canvas.getContext('2d');

                let isDrawing = false;
                let hasSignature = false;
                let lastPoint = null;

                function configureContext() {
                    context.lineWidth = 2.5;
                    context.lineCap = 'round';
                    context.lineJoin = 'round';
                    context.strokeStyle = '#111827';
                }

                function resizeCanvas() {
                    const previousImage = hasSignature
                        ? canvas.toDataURL('image/png')
                        : null;

                    const rect = container.getBoundingClientRect();
                    const pixelRatio = window.devicePixelRatio || 1;

                    canvas.width = Math.max(
                        1,
                        Math.floor(rect.width * pixelRatio)
                    );

                    canvas.height = Math.max(
                        1,
                        Math.floor(256 * pixelRatio)
                    );

                    canvas.style.width = rect.width + 'px';
                    canvas.style.height = '256px';

                    context.setTransform(
                        pixelRatio,
                        0,
                        0,
                        pixelRatio,
                        0,
                        0
                    );

                    configureContext();

                    if (previousImage) {
                        const image = new Image();

                        image.onload = function () {
                            context.drawImage(
                                image,
                                0,
                                0,
                                rect.width,
                                256
                            );

                            updateSignatureData();
                        };

                        image.src = previousImage;
                    }
                }

                function getPoint(event) {
                    const rect = canvas.getBoundingClientRect();

                    return {
                        x: event.clientX - rect.left,
                        y: event.clientY - rect.top,
                    };
                }

                function startDrawing(event) {
                    event.preventDefault();

                    isDrawing = true;
                    lastPoint = getPoint(event);

                    canvas.setPointerCapture(
                        event.pointerId
                    );
                }

                function draw(event) {
                    if (!isDrawing || !lastPoint) {
                        return;
                    }

                    event.preventDefault();

                    const currentPoint = getPoint(event);

                    context.beginPath();
                    context.moveTo(
                        lastPoint.x,
                        lastPoint.y
                    );

                    context.lineTo(
                        currentPoint.x,
                        currentPoint.y
                    );

                    context.stroke();

                    lastPoint = currentPoint;
                    hasSignature = true;

                    updateControls();
                }

                function stopDrawing(event) {
                    if (!isDrawing) {
                        return;
                    }

                    event.preventDefault();

                    isDrawing = false;
                    lastPoint = null;

                    if (
                        canvas.hasPointerCapture(event.pointerId)
                    ) {
                        canvas.releasePointerCapture(
                            event.pointerId
                        );
                    }

                    updateSignatureData();
                }

                function updateSignatureData() {
                    if (!hasSignature) {
                        signatureInput.value = '';
                        return;
                    }

                    signatureInput.value =
                        canvas.toDataURL('image/png');
                }

                function updateControls() {
                    if (hasSignature) {
                        signatureStatus.textContent =
                            'Signature captured';

                        signatureStatus.classList.remove(
                            'text-gray-500'
                        );

                        signatureStatus.classList.add(
                            'font-medium',
                            'text-green-700'
                        );

                        submitButton.disabled = false;

                        submitButton.classList.remove(
                            'cursor-not-allowed',
                            'bg-indigo-300'
                        );

                        submitButton.classList.add(
                            'cursor-pointer',
                            'bg-indigo-600',
                            'hover:bg-indigo-700'
                        );
                    } else {
                        signatureStatus.textContent =
                            'No signature drawn';

                        signatureStatus.classList.remove(
                            'font-medium',
                            'text-green-700'
                        );

                        signatureStatus.classList.add(
                            'text-gray-500'
                        );

                        submitButton.disabled = true;

                        submitButton.classList.remove(
                            'cursor-pointer',
                            'bg-indigo-600',
                            'hover:bg-indigo-700'
                        );

                        submitButton.classList.add(
                            'cursor-not-allowed',
                            'bg-indigo-300'
                        );
                    }
                }

                function showConfirmationError() {
                    confirmationError.textContent =
                        'Please confirm that the signature is yours before submitting.';

                    confirmationError.classList.remove('hidden');

                    confirmationContainer.classList.remove(
                        'border-gray-200'
                    );

                    confirmationContainer.classList.add(
                        'border-red-400',
                        'bg-red-50'
                    );

                    confirmationCheckbox.setAttribute(
                        'aria-invalid',
                        'true'
                    );
                }

                function clearConfirmationError() {
                    confirmationError.classList.add('hidden');

                    confirmationContainer.classList.remove(
                        'border-red-400',
                        'bg-red-50'
                    );

                    confirmationContainer.classList.add(
                        'border-gray-200'
                    );

                    confirmationCheckbox.setAttribute(
                        'aria-invalid',
                        'false'
                    );
                }

                function restoreSignatureData() {
                    const storedSignature =
                        signatureInput.value.trim();

                    if (
                        !storedSignature.startsWith(
                            'data:image/png;base64,'
                        )
                    ) {
                        return;
                    }

                    const image = new Image();

                    image.onload = function () {
                        const rect =
                            container.getBoundingClientRect();

                        context.clearRect(
                            0,
                            0,
                            canvas.width,
                            canvas.height
                        );

                        context.drawImage(
                            image,
                            0,
                            0,
                            rect.width,
                            256
                        );

                        hasSignature = true;
                        updateSignatureData();
                        updateControls();
                    };

                    image.src = storedSignature;
                }

                function clearSignature() {
                    context.clearRect(
                        0,
                        0,
                        canvas.width,
                        canvas.height
                    );

                    hasSignature = false;
                    signatureInput.value = '';

                    updateControls();
                }

                canvas.addEventListener(
                    'pointerdown',
                    startDrawing
                );

                canvas.addEventListener(
                    'pointermove',
                    draw
                );

                canvas.addEventListener(
                    'pointerup',
                    stopDrawing
                );

                canvas.addEventListener(
                    'pointercancel',
                    stopDrawing
                );

                canvas.addEventListener(
                    'pointerleave',
                    function (event) {
                        if (isDrawing) {
                            stopDrawing(event);
                        }
                    }
                );

                clearButton.addEventListener(
                    'click',
                    clearSignature
                );

                confirmationCheckbox.addEventListener(
                    'change',
                    function () {
                        if (confirmationCheckbox.checked) {
                            clearConfirmationError();
                        }
                    }
                );

                form.addEventListener(
                    'submit',
                    function (event) {
                        if (!hasSignature) {
                            event.preventDefault();

                            alert(
                                'Please draw your signature before submitting.'
                            );

                            return;
                        }

                        updateSignatureData();

                        if (!confirmationCheckbox.checked) {
                            event.preventDefault();

                            showConfirmationError();

                            confirmationCheckbox.focus();

                            confirmationContainer.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center',
                            });

                            return;
                        }

                        clearConfirmationError();

                        submitButton.disabled = true;
                        submitButton.textContent =
                            'Submitting...';

                        submitButton.classList.add(
                            'cursor-not-allowed',
                            'opacity-75'
                        );
                    }
                );

                window.addEventListener(
                    'resize',
                    function () {
                        resizeCanvas();
                    }
                );

                resizeCanvas();
                updateControls();
                restoreSignatureData();
            });
        </script>
    @endif
</x-layouts.public-consent>


    {{-- PUBLIC_STATION_SIGNATURE_TIMEOUT_INCLUDE --}}
    @if ($consentSession->signing_station_id)
        @include('public-signing-stations.partials.inactivity-timeout', [
            'cancelAction' => route(
                'public-consent.cancel',
                $consentSession->access_token
            ),
            'timeoutFormId' => 'public-station-signature-timeout-form',
        ])
    @endif
