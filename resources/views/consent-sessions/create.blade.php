<x-app-layout>
    @php
        $isSelfTest = $selfTest ?? false;
    @endphp
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ $isSelfTest
                        ? 'Send Test Consent to Myself'
                        : 'Create Individual Consent' }}
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    {{ $consentTemplate->title }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800">
                    <p class="mb-2 font-semibold">
                        Please correct the following:
                    </p>

                    <ul class="list-disc space-y-1 pl-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-hidden rounded-xl bg-white shadow">

                <div class="border-b border-gray-200 bg-gray-50 px-6 py-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wide text-gray-500">
                                Published Template
                            </p>

                            <h1 class="mt-2 text-2xl font-bold text-gray-900">
                                {{ $consentTemplate->title }}
                            </h1>

                            @if ($consentTemplate->description)
                                <p class="mt-2 text-gray-600">
                                    {{ $consentTemplate->description }}
                                </p>
                            @endif
                        </div>

                        <span class="inline-flex whitespace-nowrap rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                            Version {{ $publishedVersion->version_number }}
                        </span>
                    </div>
                </div>

                <x-signed-consent-capacity
                    :capacity="$signedConsentCapacity"
                />

                <form
                    method="POST"
                    action="{{ route('consent-sessions.store', $consentTemplate) }}"
                    class="space-y-6 p-6"
                >
                    @csrf

                    @if ($isSelfTest)
                        <input
                            type="hidden"
                            name="self_test"
                            value="1"
                        >

                        <div
                            data-evaluation-self-test
                            class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm leading-6 text-indigo-900"
                        >
                            <p class="font-semibold">
                                Test the real signing experience
                            </p>

                            <p class="mt-1">
                                Creating this record will immediately send the
                                secure signing link to your account email and
                                use 1 of your 5 Free Evaluation invitation
                                emails.
                            </p>

                            @if ($selfTestEmailCapacity !== null)
                                <p class="mt-2 font-medium">
                                    {{ $selfTestEmailCapacity['remaining'] }}
                                    invitation
                                    {{ $selfTestEmailCapacity['remaining'] === 1 ? 'email' : 'emails' }}
                                    remaining.
                                </p>
                            @endif
                        </div>
                    @endif

                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                        This record will remain permanently linked to
                        Version {{ $publishedVersion->version_number }},
                        even if a newer version is published later.
                    </div>

                    <div>
                        <label
                            for="signer_name"
                            class="block text-sm font-medium text-gray-800"
                        >
                            Signer Name
                            <span class="text-red-600">*</span>
                        </label>

                        <input
                            id="signer_name"
                            name="signer_name"
                            type="text"
                            value="{{ old(
                                'signer_name',
                                $isSelfTest
                                    ? auth()->user()->name
                                    : ''
                            ) }}"
                            @readonly($isSelfTest)
                            required
                            autofocus
                            autocomplete="name"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Enter the signer's full name"
                        >

                        @error('signer_name')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="signer_email"
                            class="block text-sm font-medium text-gray-800"
                        >
                            Signer Email
                        </label>

                        <input
                            id="signer_email"
                            name="signer_email"
                            type="email"
                            value="{{ old(
                                'signer_email',
                                $isSelfTest
                                    ? auth()->user()->email
                                    : ''
                            ) }}"
                            @readonly($isSelfTest)
                            autocomplete="email"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="name@example.com"
                        >

                        <p class="mt-2 text-xs text-gray-500">
                            @if ($isSelfTest)
                                This is your account email. The secure signing
                                link will be sent here automatically.
                            @else
                                Optional. This can later be used to send a secure signing link.
                            @endif
                        </p>

                        @error('signer_email')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="signer_reference"
                            class="block text-sm font-medium text-gray-800"
                        >
                            Reference Number
                        </label>

                        <input
                            id="signer_reference"
                            name="signer_reference"
                            type="text"
                            value="{{ old('signer_reference') }}"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Optional internal reference"
                        >

                        <p class="mt-2 text-xs text-gray-500">
                            Use an account number, booking number, file number, or another internal reference.
                        </p>

                        @error('signer_reference')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>


                    <div
                        id="signing-deadline-panel"
                        class="rounded-xl border border-amber-200 bg-amber-50 p-5"
                    >
                        <div>
                            <p class="block text-sm font-semibold text-gray-900">
                                Signing Deadline
                                <span class="font-normal text-gray-500">
                                    (optional)
                                </span>
                            </p>

                            <p class="mt-1 text-xs leading-5 text-gray-600">
                                Select the date from the calendar. The time starts at midnight
                                and can be changed.
                            </p>
                        </div>

                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label
                                    for="expires_date"
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    Date
                                </label>

                                <div class="mt-2 flex w-full min-w-0 rounded-lg border border-gray-300 bg-white px-3 py-2 shadow-sm focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                                    <input
                                        id="expires_date"
                                        name="expires_date"
                                        type="date"
                                        min="{{ now()->copy()->timezone(config('app.display_timezone'))->format('Y-m-d') }}"
                                        value="{{ old('expires_date') }}"
                                        class="block w-full min-w-0 border-0 bg-transparent p-0 text-gray-900 focus:ring-0"
                                    >
                                </div>

                                <p
                                    id="expires-date-preview"
                                    class="mt-2 text-xs font-medium text-amber-800"
                                    aria-live="polite"
                                >
                                    No deadline date selected.
                                </p>

                                @error('expires_date')
                                    <p class="mt-2 text-sm text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label
                                    for="expires_time"
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    Time
                                </label>

                                <div class="mt-2 flex w-full min-w-0 rounded-lg border border-gray-300 bg-white px-3 py-2 shadow-sm focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
                                    <input
                                        id="expires_time"
                                        name="expires_time"
                                        type="time"
                                        value="{{ old('expires_time', '00:00') }}"
                                        @disabled(blank(old('expires_date')))
                                        class="block w-full min-w-0 border-0 bg-transparent p-0 text-gray-900 focus:ring-0 disabled:cursor-not-allowed disabled:text-gray-400"
                                    >
                                </div>

                                <p class="mt-2 text-xs text-gray-600">
                                    Default:
                                    <span class="font-semibold">00:00 (midnight)</span>.
                                    Change it when another time is required.
                                </p>

                                @error('expires_time')
                                    <p class="mt-2 text-sm text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                        <p class="mt-3 text-xs leading-5 text-gray-600">
                            The selected date is shown above as
                            <span class="font-semibold">dd/mm/yy</span>.
                            Midnight means the start of the selected date.
                            If you select today, choose a future time.
                            Leave the date blank when the signing link should not expire.
                            Times use the application timezone:
                            <span class="font-semibold">
                                {{ config('app.display_timezone') }}
                            </span>.
                        </p>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-6 sm:flex-row sm:justify-end">
                        <a
                            href="{{ $isSelfTest
                                ? route('consent-sessions.select-template', ['self_test' => 1])
                                : route('consent-templates.published', $consentTemplate) }}"
                            class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-5 py-3 text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            @disabled(
                                $signedConsentCapacity['reached']
                                || (
                                    $isSelfTest
                                    && $selfTestEmailCapacity !== null
                                    && $selfTestEmailCapacity['reached']
                                )
                            )
                            class="inline-flex cursor-pointer justify-center rounded-lg bg-green-600 px-5 py-3 font-medium text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-gray-400"
                        >
                            {{ $isSelfTest
                                ? 'Send Test Consent to Myself'
                                : 'Create Individual Consent' }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dateInput = document.getElementById('expires_date');
            const timeInput = document.getElementById('expires_time');
            const preview = document.getElementById('expires-date-preview');

            if (!dateInput || !timeInput || !preview) {
                return;
            }

            const updateDeadlineControls = () => {
                const hasDate = dateInput.value !== '';

                timeInput.disabled = !hasDate;

                if (!hasDate) {
                    timeInput.value = '00:00';
                    preview.textContent = 'No deadline date selected.';
                    return;
                }

                if (timeInput.value === '') {
                    timeInput.value = '00:00';
                }

                const parts = dateInput.value.split('-');

                if (parts.length === 3) {
                    const year = parts[0].slice(-2);
                    preview.textContent =
                        `Selected date: ${parts[2]}/${parts[1]}/${year}`;
                }
            };

            dateInput.addEventListener(
                'change',
                updateDeadlineControls
            );

            updateDeadlineControls();
        });
    </script>

</x-app-layout>
