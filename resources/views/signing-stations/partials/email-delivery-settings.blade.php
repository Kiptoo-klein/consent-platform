{{-- KIOSK_EMAIL_IDENTITY_FIELDS --}}
@php
    $stationForEmailSettings = $signingStation ?? null;

    /*
    |--------------------------------------------------------------------------
    | Organization defaults with per-kiosk overrides
    |--------------------------------------------------------------------------
    |
    | A newly created kiosk starts with the organization's name and email.
    | Once a kiosk has saved values, those values take priority. old() still
    | takes priority after validation errors, so an administrator never loses
    | an edit made in the form.
    |
    */

    $organizationForEmailSettings =
        auth()->user()?->organization;

    $organizationSenderName = trim((string) (
        $organizationForEmailSettings?->name ?? ''
    ));

    $organizationReplyToEmail = collect([
        $organizationForEmailSettings?->email,
        data_get(
            $organizationForEmailSettings,
            'contact_email'
        ),
        $organizationForEmailSettings?->support_email,
    ])->first(
        fn ($email): bool =>
            is_string($email)
            && filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) !== false
    );

    $savedSenderName = trim((string) (
        $stationForEmailSettings?->sender_name ?? ''
    ));

    $savedReplyToEmail = trim((string) (
        $stationForEmailSettings?->reply_to_email ?? ''
    ));

    $kioskSenderNameValue = old(
        'sender_name',
        $savedSenderName !== ''
            ? $stationForEmailSettings?->sender_name
            : $organizationSenderName
    );

    $kioskReplyToEmailValue = old(
        'reply_to_email',
        $savedReplyToEmail !== ''
            ? $stationForEmailSettings?->reply_to_email
            : $organizationReplyToEmail
    );

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

<section class="kiosk-email-identity-panel rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <div>
        <h3 class="text-base font-bold text-red-700">
            Signed PDF email identity
        </h3>

        <p class="mt-1 text-sm leading-6 text-gray-600">
            The platform email account sends the PDF. These settings control the display name, reply address and explanation shown to the signer.
        </p>
    </div>

    <div class="mt-5 grid gap-5 sm:grid-cols-2">
        <div>
            <label
                for="sender_name"
                class="block text-sm font-medium text-gray-700"
            >
                Sender display name
            </label>

            <input
                id="sender_name"
                name="sender_name"
                type="text"
                maxlength="120"
                value="{{ $kioskSenderNameValue }}"
                placeholder="Example Medical Centre"
                class="mt-2 block w-full rounded-lg border border-gray-300 bg-white text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-teal-600 focus:ring-teal-600"
            >

            <p class="mt-2 text-xs text-gray-500">
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
                class="block text-sm font-medium text-gray-700"
            >
                Reply-to email
            </label>

            <input
                id="reply_to_email"
                name="reply_to_email"
                type="email"
                maxlength="255"
                value="{{ $kioskReplyToEmailValue }}"
                placeholder="consent@example.org"
                class="mt-2 block w-full rounded-lg border border-gray-300 bg-white text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-teal-600 focus:ring-teal-600"
            >

            <p class="mt-2 text-xs text-gray-500">
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
                class="block text-sm font-medium text-gray-700"
            >
                Email description
            </label>

            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                Default message provided
            </span>
        </div>

        <textarea
            id="email_description"
            name="email_description"
            rows="5"
            maxlength="1000"
            class="mt-2 block w-full rounded-lg border border-gray-300 bg-white text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-teal-600 focus:ring-teal-600"
        >{{ $kioskEmailDescriptionValue }}</textarea>

        <p class="mt-2 text-xs leading-5 text-gray-500">
            The organization may keep this message unchanged or edit it. When no custom message is saved, the default is used automatically. Avoid promotional language, unnecessary links and urgency.
        </p>

        @error('email_description')
            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                {{ $message }}
            </p>
        @enderror
    </div>
</section>
