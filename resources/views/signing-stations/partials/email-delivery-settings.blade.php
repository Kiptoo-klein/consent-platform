{{-- KIOSK_EMAIL_IDENTITY_FIELDS --}}
@php
    $stationForEmailSettings = $signingStation ?? null;

    $defaultKioskEmailDescription = (string) config(
        'kiosk-email.default_description',
        'This email contains the official signed copy of the consent you completed. Please keep the attached PDF for your records. No further action is required unless the organization contacts you.'
    );

    $savedKioskEmailDescription = trim((string) (
        $stationForEmailSettings?->email_description ?? ''
    ));

    $kioskEmailDescriptionValue = old(
        'email_description',
        $savedKioskEmailDescription !== ''
            ? $stationForEmailSettings?->email_description
            : $defaultKioskEmailDescription
    );
@endphp

<section class="rounded-xl border border-indigo-200 bg-indigo-50/60 p-5 dark:border-indigo-900 dark:bg-indigo-950/30">
    <div>
        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
            Signed PDF email identity
        </h3>

        <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">
            The platform email account sends the PDF. These settings control the display name, reply address and explanation shown to the signer.
        </p>
    </div>

    <div class="mt-5 grid gap-5 sm:grid-cols-2">
        <div>
            <label
                for="sender_name"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
                Sender display name
            </label>

            <input
                id="sender_name"
                name="sender_name"
                type="text"
                maxlength="120"
                value="{{ old('sender_name', $stationForEmailSettings?->sender_name) }}"
                placeholder="Example Medical Centre"
                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
            >

            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Displayed beside the platform's authenticated email address.
            </p>

            @error('sender_name')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label
                for="reply_to_email"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
                Reply-to email
            </label>

            <input
                id="reply_to_email"
                name="reply_to_email"
                type="email"
                maxlength="255"
                value="{{ old('reply_to_email', $stationForEmailSettings?->reply_to_email) }}"
                placeholder="consent@example.org"
                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
            >

            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                Signer replies will be delivered to this address.
            </p>

            @error('reply_to_email')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                    {{ $message }}
                </p>
            @enderror
        </div>
    </div>

    <div class="mt-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <label
                for="email_description"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
            >
                Email description
            </label>

            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-indigo-700 shadow-sm dark:bg-gray-900 dark:text-indigo-300">
                Default message provided
            </span>
        </div>

        <textarea
            id="email_description"
            name="email_description"
            rows="5"
            maxlength="1000"
            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
        >{{ $kioskEmailDescriptionValue }}</textarea>

        <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
            The organization may keep this message unchanged or edit it. When no custom message is saved, the default is used automatically. Avoid promotional language, unnecessary links and urgency.
        </p>

        @error('email_description')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ $message }}
            </p>
        @enderror
    </div>
</section>
