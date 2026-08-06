<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Consent Evidence
                    </h2>

                    @if ($consentSession->isExpired())
                        <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800">
                            Expired
                        </span>
                    @else
                        <x-consent-status-badge
                            :status="$consentSession->status"
                        />
                    @endif
                </div>

                <p class="mt-1 text-sm text-gray-500">
                    Record #{{ $consentSession->id }}
                    ·
                    {{ $consentSession->consentTemplate?->title ?? 'Unavailable template' }}
                </p>
            </div>

            <a
                href="{{ route('consent-sessions.index') }}"
                class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
            >
                Back to Consent Records
            </a>
        </div>
    </x-slot>

    @php
        $signature = $consentSession->signature;

        $sourceLabel = $consentSession->cameFromSigningStation()
            ? ($consentSession->signingStation?->name ?? 'Signing station')
            : 'Direct signing link';

        $signedAt =
            $signature?->signed_at
            ?? $consentSession->completed_at;

        $recordIdentifier =
            'CONSENT-'
            .str_pad(
                (string) $consentSession->id,
                8,
                '0',
                STR_PAD_LEFT
            );


        /*
         * A share link belongs only to a direct individual-consent record.
         * Use the database field directly so older helper implementations or
         * unloaded relationships cannot incorrectly hide the sharing panel.
         */
        $isDirectIndividualConsent =
            blank($consentSession->signing_station_id);

        /*
         * Always render the sharing section for a direct individual consent.
         * A missing token is displayed as a visible warning rather than
         * silently hiding the entire panel.
         */
        $showIndividualSharePanel = $isDirectIndividualConsent;

        $canShareSigningLink =
            $isDirectIndividualConsent
            && filled($consentSession->access_token)
            && ! $consentSession->isCompleted()
            && ! $consentSession->isCancelled()
            && ! $consentSession->isExpired();

        $signingUrl = filled($consentSession->access_token)
            ? route(
                'public-consent.show',
                [
                    'accessToken' =>
                        $consentSession->access_token,
                ]
            )
            : null;

        $shareSubject =
            'Consent request: '
            .($consentSession->consentTemplate?->title
                ?? 'Consent document');

        $shareMessage =
            'Please review and sign your consent document using this secure link:';

        $sentInitialEmail = $consentNotifications
            ->first(
                fn ($notification) =>
                    in_array(
                        $notification->type,
                        [
                            \App\Models\ConsentNotification::TYPE_INITIAL,
                            \App\Models\ConsentNotification::TYPE_RESEND,
                        ],
                        true
                    )
                    && $notification->isSent()
            );

        $canEmailSigner =
            $canShareSigningLink
            && filled($consentSession->signer_email);
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('email_success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('email_success') }}
                </div>
            @endif

            @if (session('email_warning'))
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ session('email_warning') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                    <p class="font-semibold">
                        The action could not be completed.
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

            @if ($consentSession->isExpired())
                <div class="rounded-xl border border-amber-300 bg-amber-50 px-5 py-4 text-amber-900">
                    <p class="font-semibold">
                        Signing deadline expired
                    </p>

                    <p class="mt-1 text-sm">
                        This record can no longer be reviewed, changed, signed, or cancelled.
                        It expired
                        {{ $consentSession->expired_at?->copy()?->timezone(config('app.display_timezone'))?->format('M d, Y H:i')
                            ?? $consentSession->expires_at?->copy()?->timezone(config('app.display_timezone'))?->format('M d, Y H:i')
                            ?? 'after its signing deadline' }}.
                    </p>
                </div>
            @endif

            @if ($showIndividualSharePanel)
                <section
                    id="individual-consent-share-panel"
                    class="overflow-hidden rounded-2xl border border-indigo-200 bg-indigo-50 shadow-sm"
                    data-signing-url="{{ $signingUrl }}"
                    data-share-title="{{ $shareSubject }}"
                    data-share-text="{{ $shareMessage }}"
                >
                    <div class="border-b border-indigo-200 px-6 py-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">
                                    Individual Consent
                                </p>

                                <h2 class="mt-1 text-lg font-bold text-indigo-950">
                                    Share the secure signing link
                                </h2>

                                <p class="mt-1 text-sm leading-6 text-indigo-800">
                                    Send this link to
                                    <span class="font-semibold">
                                        {{ $consentSession->signer_name }}
                                    </span>
                                    so they can review and sign the consent.
                                </p>

                                @if (! $signingUrl)
                                    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                                        This individual consent has no access token, so its secure signing link cannot be generated yet.
                                    </div>
                                @elseif (! $canShareSigningLink)
                                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                        This record is no longer open for signing. The link is shown for diagnosis, but the signer may see a completed, cancelled, or expired notice.
                                    </div>
                                @endif
                            </div>

                            @if ($consentSession->expires_at)
                                <span class="inline-flex w-fit rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
                                    Expires
                                    {{ $consentSession->expires_at->copy()->timezone(config('app.display_timezone'))->format('d/m/y H:i') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-4 p-6">
                        @if ($signingUrl)
                        <div>
                            <label
                                for="individual-consent-signing-url"
                                class="block text-sm font-medium text-indigo-950"
                            >
                                Secure signing link
                            </label>

                            <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                                <input
                                    id="individual-consent-signing-url"
                                    type="text"
                                    readonly
                                    value="{{ $signingUrl }}"
                                    class="block min-w-0 flex-1 rounded-lg border-indigo-200 bg-white font-mono text-sm text-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >

                                <button
                                    type="button"
                                    id="copy-individual-consent-link"
                                    class="inline-flex cursor-pointer items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
                                >
                                    Copy link
                                </button>

                                <button
                                    type="button"
                                    id="share-individual-consent-link"
                                    class="hidden cursor-pointer items-center justify-center rounded-lg border border-indigo-300 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100"
                                >
                                    Share
                                </button>
                            </div>

                            <p
                                id="individual-consent-share-status"
                                class="mt-2 text-sm font-medium text-indigo-800"
                                aria-live="polite"
                            ></p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <a
                                href="mailto:{{ $consentSession->signer_email ?: '' }}?subject={{ rawurlencode($shareSubject) }}&body={{ rawurlencode($shareMessage."\n\n".$signingUrl) }}"
                                class="inline-flex items-center justify-center rounded-lg border border-indigo-300 bg-white px-4 py-2 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100"
                            >
                                Share via email
                            </a>

                            <a
                                href="https://wa.me/?text={{ rawurlencode($shareSubject."\n".$shareMessage."\n".$signingUrl) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center justify-center rounded-lg border border-indigo-300 bg-white px-4 py-2 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100"
                            >
                                Share on WhatsApp
                            </a>

                            <a
                                href="{{ $signingUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center justify-center rounded-lg border border-indigo-300 bg-white px-4 py-2 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100"
                            >
                                Open signing page
                            </a>
                        </div>

                        <p class="text-xs leading-5 text-indigo-700">
                            Anyone with this secure link can access this individual consent.
                            Share it only with the intended signer.
                        </p>
                        @else
                            <p class="text-sm text-red-800">
                                Create or repair the record access token before sharing this consent.
                            </p>
                        @endif
                    </div>
                </section>
            @endif

            {{-- Email delivery and reminder history --}}
            @if ($consentSession->signing_station_id === null)
                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">
                                    Email Delivery
                                </p>

                                <h2 class="mt-1 text-lg font-bold text-gray-950">
                                    Signing request and reminders
                                </h2>

                                <p class="mt-1 text-sm leading-6 text-gray-600">
                                    Delivery attempts are recorded here. Automatic reminders stop when the record is completed, cancelled, or expired.
                                </p>
                            </div>

                            @if (filled($consentSession->signer_email))
                                <span class="inline-flex w-fit rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-200">
                                    {{ $consentSession->signer_email }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-5 p-6">
                        @if ($canEmailSigner)
                            <div class="flex flex-wrap gap-3">
                                <form
                                    method="POST"
                                    action="{{ route('consent-sessions.email.send', $consentSession) }}"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="inline-flex cursor-pointer items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
                                    >
                                        {{ $sentInitialEmail ? 'Send email again' : 'Send signing email' }}
                                    </button>
                                </form>

                                <form
                                    method="POST"
                                    action="{{ route('consent-sessions.email.reminder', $consentSession) }}"
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-indigo-300 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50"
                                    >
                                        Send reminder
                                    </button>
                                </form>
                            </div>

                            <p class="text-xs leading-5 text-gray-500">
                                A short cooldown prevents accidental repeated emails. Scheduled reminders are sent three days and one day before the deadline by default.
                            </p>
                        @elseif (! filled($consentSession->signer_email))
                            <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600">
                                No signer email was provided, so email delivery is unavailable for this record. WhatsApp and copy-link sharing remain available.
                            </div>
                        @else
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                                Email delivery is closed because this record is completed, cancelled, or expired.
                            </div>
                        @endif

                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-700">
                                Delivery history
                            </h3>

                            <div class="mt-3 overflow-hidden rounded-xl border border-gray-200">
                                @forelse ($consentNotifications as $notification)
                                    @php
                                        $statusClasses = match ($notification->status) {
                                            \App\Models\ConsentNotification::STATUS_SENT =>
                                                'bg-emerald-100 text-emerald-800',
                                            \App\Models\ConsentNotification::STATUS_FAILED =>
                                                'bg-red-100 text-red-800',
                                            default =>
                                                'bg-amber-100 text-amber-800',
                                        };

                                        $typeLabel = match ($notification->type) {
                                            \App\Models\ConsentNotification::TYPE_INITIAL =>
                                                'Signing request',
                                            \App\Models\ConsentNotification::TYPE_RESEND =>
                                                'Signing request resent',
                                            \App\Models\ConsentNotification::TYPE_AUTOMATIC_REMINDER =>
                                                'Automatic reminder',
                                            default =>
                                                'Manual reminder',
                                        };
                                    @endphp

                                    <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-semibold text-gray-900">
                                                    {{ $typeLabel }}
                                                </p>

                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClasses }}">
                                                    {{ ucfirst($notification->status) }}
                                                </span>
                                            </div>

                                            <p class="mt-1 truncate text-sm text-gray-600">
                                                {{ $notification->recipient_email }}
                                            </p>

                                            @if ($notification->days_before_deadline !== null)
                                                <p class="mt-1 text-xs text-gray-500">
                                                    {{ $notification->days_before_deadline }} day reminder
                                                </p>
                                            @endif

                                            @if ($notification->isFailed() && $notification->error_message)
                                                <p class="mt-2 text-xs text-red-700">
                                                    {{ \Illuminate\Support\Str::limit($notification->error_message, 180) }}
                                                </p>
                                            @endif
                                        </div>

                                        <div class="shrink-0 text-left text-xs text-gray-500 sm:text-right">
                                            <p>
                                                {{ $notification->sent_at?->copy()?->timezone(config('app.display_timezone'))?->format('d/m/y H:i')
                                                    ?? $notification->failed_at?->copy()?->timezone(config('app.display_timezone'))?->format('d/m/y H:i')
                                                    ?? $notification->created_at?->copy()?->timezone(config('app.display_timezone'))?->format('d/m/y H:i') }}
                                            </p>

                                            <p class="mt-1">
                                                {{ str($notification->trigger)->replace('_', ' ')->title() }}
                                            </p>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-4 py-8 text-center">
                                        <p class="text-sm font-medium text-gray-700">
                                            No email has been attempted yet.
                                        </p>

                                        <p class="mt-1 text-sm text-gray-500">
                                            The first successful or failed delivery will appear here.
                                        </p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            @include('consent-sessions.partials.signed-pdf-delivery-panel')

            {{-- Evidence summary --}}
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">
                                Evidence of Consent
                            </p>

                            <h1 class="mt-1 text-2xl font-bold text-gray-900">
                                {{ $consentSession->consentTemplate?->title ?? 'Consent Record' }}
                            </h1>

                            <p class="mt-2 text-sm text-gray-500">
                                This page presents the document version, signer information,
                                submitted responses and signature evidence connected to this record.
                            </p>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-left sm:text-right">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                Evidence ID
                            </p>

                            <p class="mt-1 font-mono text-sm font-semibold text-gray-900">
                                {{ $recordIdentifier }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-px bg-gray-200 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="bg-white p-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Status
                        </p>

                        <div class="mt-2">
                            @if ($consentSession->isExpired())
                                <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800">
                                    Expired
                                </span>
                            @else
                                <x-consent-status-badge
                                    :status="$consentSession->status"
                                />
                            @endif
                        </div>
                    </div>

                    <div class="bg-white p-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Version
                        </p>

                        <p class="mt-2 font-semibold text-gray-900">
                            {{ $publishedVersion
                                ? 'Version '.$publishedVersion->version_number
                                : 'Unavailable' }}
                        </p>
                    </div>

                    <div class="bg-white p-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Signing Source
                        </p>

                        <p class="mt-2 font-semibold text-gray-900">
                            {{ $sourceLabel }}
                        </p>
                    </div>

                    <div class="bg-white p-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Completed
                        </p>

                        <p class="mt-2 font-semibold text-gray-900">
                            {{ $consentSession->completed_at
                                ? $consentSession->completed_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y H:i')
                                : 'Not completed' }}
                        </p>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-3">

                {{-- Main evidence column --}}
                <div class="space-y-6 lg:col-span-2">

                    {{-- Signer identity --}}
                    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-6 py-5">
                            <h2 class="text-lg font-bold text-gray-900">
                                Signer Information
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                Identity information supplied for this consent record.
                            </p>
                        </div>

                        <dl class="grid gap-px bg-gray-200 sm:grid-cols-2">
                            <div class="bg-white p-5">
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Full Name
                                </dt>

                                <dd class="mt-2 font-semibold text-gray-900">
                                    {{ $consentSession->signer_name }}
                                </dd>
                            </div>

                            <div class="bg-white p-5">
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Email Address
                                </dt>

                                <dd class="mt-2 break-words font-semibold text-gray-900">
                                    {{ $consentSession->signer_email ?: 'Not provided' }}
                                </dd>
                            </div>

                            <div class="bg-white p-5 sm:col-span-2">
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Reference Number
                                </dt>

                                <dd class="mt-2 font-semibold text-gray-900">
                                    {{ $consentSession->signer_reference ?: 'Not provided' }}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    {{-- Exact consent document --}}
                    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-6 py-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h2 class="text-lg font-bold text-gray-900">
                                        Consent Document
                                    </h2>

                                    <p class="mt-1 text-sm text-gray-500">
                                        The exact published version connected to this record.
                                    </p>
                                </div>

                                @if ($publishedVersion)
                                    <span class="inline-flex w-fit rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                                        Version {{ $publishedVersion->version_number }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="p-6">
                            @if (
                                filled(
                                    data_get(
                                        $publishedVersion,
                                        'template_schema.consent_html'
                                    )
                                )
                                || filled($consentText)
                            )
                                <x-consent-template-content
                                    :html="data_get(
                                        $publishedVersion,
                                        'template_schema.consent_html'
                                    )"
                                    :text="$consentText"
                                    :organization-id="$consentSession->organization_id"
                                    class="text-gray-700"
                                />
                            @else
                                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center">
                                    <p class="font-medium text-gray-700">
                                        Consent content unavailable
                                    </p>

                                    <p class="mt-2 text-sm text-gray-500">
                                        No consent text was found on the version linked to this record.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </section>

                    {{-- Submitted responses --}}
                    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-6 py-5">
                            <h2 class="text-lg font-bold text-gray-900">
                                Submitted Responses
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                Answers submitted alongside the signer’s identity and signature.
                            </p>
                        </div>

                        <div class="p-6">
                            @if (count($responseEvidence) > 0)
                                <dl class="grid gap-4 sm:grid-cols-2">
                                    @foreach ($responseEvidence as $response)
                                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                            <dt class="text-sm font-medium text-gray-500">
                                                {{ $response['label'] }}
                                            </dt>

                                            <dd class="mt-2 break-words font-medium text-gray-900">
                                                @if (is_array($response['value']))
                                                    {{ count($response['value']) > 0
                                                        ? implode(', ', array_map('strval', $response['value']))
                                                        : 'No response' }}
                                                @elseif (is_bool($response['value']))
                                                    {{ $response['value'] ? 'Yes' : 'No' }}
                                                @elseif ($response['type'] === 'checkbox')
                                                    {{ in_array(
                                                        (string) $response['value'],
                                                        ['1', 'true', 'yes', 'on'],
                                                        true
                                                    ) ? 'Yes' : 'No' }}
                                                @elseif (
                                                    $response['value'] === null
                                                    || $response['value'] === ''
                                                )
                                                    No response
                                                @else
                                                    {{ $response['value'] }}
                                                @endif
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @else
                                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center">
                                    <p class="font-medium text-gray-700">
                                        No additional responses
                                    </p>

                                    <p class="mt-2 text-sm text-gray-500">
                                        This record does not contain answers to additional fields.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </section>

                    {{-- Signature --}}
                    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-6 py-5">
                            <h2 class="text-lg font-bold text-gray-900">
                                Signature Evidence
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                The signature and technical evidence captured at completion.
                            </p>
                        </div>

                        <div class="p-6">
                            @if ($signature && $signature->isSigned())
                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-6">
                                    <div class="flex min-h-48 items-center justify-center rounded-lg border border-gray-200 bg-white p-4">
                                        @if ($signature->signature_type === 'drawn')
                                            <img
                                                src="{{ $signature->signature_data }}"
                                                alt="Signature of {{ $signature->signer_name }}"
                                                class="max-h-44 max-w-full object-contain"
                                            >
                                        @else
                                            <p class="font-serif text-3xl italic text-gray-900">
                                                {{ $signature->signature_data }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                                Signed By
                                            </p>

                                            <p class="mt-1 font-semibold text-gray-900">
                                                {{ $signature->signer_name }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                                Signed At
                                            </p>

                                            <p class="mt-1 font-semibold text-gray-900">
                                                {{ $signedAt?->copy()?->timezone(config('app.display_timezone'))?->format('M d, Y H:i:s') ?? 'Unavailable' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center">
                                    <p class="font-medium text-gray-700">
                                        No signature captured
                                    </p>

                                    <p class="mt-2 text-sm text-gray-500">
                                        A signature will appear here after the record is completed.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </section>

                </div>

                {{-- Sidebar --}}
                <aside class="space-y-6">

                    {{-- Actions --}}
                    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-6 py-5">
                            <h2 class="text-lg font-bold text-gray-900">
                                Actions
                            </h2>
                        </div>

                        <div class="space-y-3 p-6">
    @if (
        ! $consentSession->isCompleted()
        && ! $consentSession->isCancelled()
        && ! $consentSession->isExpired()
    )
        <a
            href="{{ route(
                'public-consent.show',
                $consentSession->access_token
            ) }}"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700"
        >
            Open Signing Page
        </a>

        <form
            method="POST"
            action="{{ route(
                'consent-sessions.cancel',
                $consentSession
            ) }}"
            x-data
            data-consent-record-cancel-confirmation
        >
            @csrf
            @method('PATCH')

            <button
                type="button"
                class="inline-flex w-full justify-center rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 transition hover:bg-red-100"
                x-on:click="$dispatch(
                    'open-modal',
                    'cancel-consent-record-{{ $consentSession->id }}'
                )"
            >
                Cancel Record
            </button>

            <x-action-confirmation-modal
                name="cancel-consent-record-{{ $consentSession->id }}"
                title="Cancel this consent record?"
                message="The signing request will be cancelled and can no longer be completed. This action cannot be undone."
                confirm-text="Cancel consent record"
                variant="danger"
            />
        </form>
    @elseif ($consentSession->isCompleted())
        @if (
            filled($consentSession->pdf_path)
            && filled($consentSession->pdf_generated_at)
        )
            <a
                href="{{ route(
                    'consent-sessions.pdf',
                    $consentSession
                ) }}"
                class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700"
            >
                Download PDF
            </a>
        @else
            <div
                class="inline-flex w-full cursor-not-allowed justify-center rounded-lg border border-gray-300 bg-gray-100 px-4 py-3 text-sm font-semibold text-gray-500"
            >
                PDF is being generated…
            </div>
        @endif

        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            This consent record is completed and locked.
        </div>
    @elseif ($consentSession->isExpired())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            The signing deadline has passed. This record is locked as expired.
        </div>
    @else
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
            This consent record was cancelled.
        </div>
    @endif

    <a
        href="{{ route(
            'consent-sessions.audit',
            $consentSession
        ) }}"
        class="inline-flex w-full justify-center rounded-lg border border-indigo-300 bg-white px-4 py-3 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50"
    >
        View Audit Trail
    </a>
</div>
                    </section>

                    {{-- Record information --}}
                    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-6 py-5">
                            <h2 class="text-lg font-bold text-gray-900">
                                Record Information
                            </h2>
                        </div>

                        <dl class="divide-y divide-gray-200">
                            <div class="px-6 py-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Evidence ID
                                </dt>

                                <dd class="mt-1 break-all font-mono text-sm font-semibold text-gray-900">
                                    {{ $recordIdentifier }}
                                </dd>
                            </div>

                            <div class="px-6 py-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Database Record
                                </dt>

                                <dd class="mt-1 text-sm font-semibold text-gray-900">
                                    #{{ $consentSession->id }}
                                </dd>
                            </div>

                            <div class="px-6 py-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Organization
                                </dt>

                                <dd class="mt-1 text-sm font-semibold text-gray-900">
                                    {{ $consentSession->organization?->name ?? 'Unavailable' }}
                                </dd>
                            </div>

                            <div class="px-6 py-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Created By
                                </dt>

                                <dd class="mt-1 text-sm font-semibold text-gray-900">
                                    {{ $consentSession->creator?->name
                                        ?? (
                                            $consentSession->cameFromSigningStation()
                                                ? 'Signing station'
                                                : 'System'
                                        ) }}
                                </dd>
                            </div>

                            <div class="px-6 py-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Created
                                </dt>

                                <dd class="mt-1 text-sm font-semibold text-gray-900">
                                    {{ $consentSession->created_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y H:i:s') }}
                                </dd>
                            </div>

                            <div class="px-6 py-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Signing Deadline
                                </dt>

                                <dd class="mt-1 text-sm font-semibold text-gray-900">
                                    {{ $consentSession->expires_at
                                        ? $consentSession->expires_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y H:i:s')
                                        : 'No expiry' }}
                                </dd>
                            </div>

                            @if ($consentSession->expired_at)
                                <div class="px-6 py-4">
                                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Expired
                                    </dt>

                                    <dd class="mt-1 text-sm font-semibold text-amber-800">
                                        {{ $consentSession->expired_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y H:i:s') }}
                                    </dd>
                                </div>
                            @endif

                            <div class="px-6 py-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Signing Source
                                </dt>

                                <dd class="mt-1 text-sm font-semibold text-gray-900">
                                    {{ $sourceLabel }}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    {{-- Technical evidence --}}
                    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-6 py-5">
                            <h2 class="text-lg font-bold text-gray-900">
                                Technical Evidence
                            </h2>
                        </div>

                        <dl class="divide-y divide-gray-200">
                            <div class="px-6 py-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    IP Address
                                </dt>

                                <dd class="mt-1 break-all font-mono text-sm text-gray-900">
                                    {{ $signature?->ip_address ?? 'Not captured' }}
                                </dd>
                            </div>

                            <div class="px-6 py-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Signature Type
                                </dt>

                                <dd class="mt-1 text-sm font-semibold capitalize text-gray-900">
                                    {{ $signature?->signature_type ?? 'Not captured' }}
                                </dd>
                            </div>

                            <div class="px-6 py-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Signed At
                                </dt>

                                <dd class="mt-1 text-sm font-semibold text-gray-900">
                                    {{ $signedAt?->copy()?->timezone(config('app.display_timezone'))?->format('M d, Y H:i:s') ?? 'Not signed' }}
                                </dd>
                            </div>

                            <div class="px-6 py-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Browser / Device
                                </dt>

                                <dd class="mt-2 break-words text-xs leading-5 text-gray-600">
                                    {{ $signature?->user_agent ?? 'Not captured' }}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    {{-- Timeline --}}
                    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-6 py-5">
                            <h2 class="text-lg font-bold text-gray-900">
                                Timeline
                            </h2>
                        </div>

                        <div class="p-6">
                            <ol class="space-y-6">
                                <li class="relative pl-8">
                                    <span class="absolute left-0 top-1.5 h-3 w-3 rounded-full bg-green-500"></span>

                                    <div class="absolute left-[5px] top-5 h-12 w-px bg-gray-200"></div>

                                    <p class="font-medium text-gray-900">
                                        Record Created
                                    </p>

                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ $consentSession->created_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y H:i:s') }}
                                    </p>
                                </li>

                                <li class="relative pl-8">
                                    <span
                                        class="absolute left-0 top-1.5 h-3 w-3 rounded-full {{ $consentSession->started_at ? 'bg-blue-500' : 'bg-gray-300' }}"
                                    ></span>

                                    @if ($consentSession->completed_at || $consentSession->cancelled_at)
                                        <div class="absolute left-[5px] top-5 h-12 w-px bg-gray-200"></div>
                                    @endif

                                    <p class="font-medium text-gray-900">
                                        Signing Started
                                    </p>

                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ $consentSession->started_at
                                            ? $consentSession->started_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y H:i:s')
                                            : 'Not started' }}
                                    </p>
                                </li>

                                @if ($consentSession->completed_at)
                                    <li class="relative pl-8">
                                        <span class="absolute left-0 top-1.5 h-3 w-3 rounded-full bg-green-500"></span>

                                        <p class="font-medium text-gray-900">
                                            Consent Completed
                                        </p>

                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ $consentSession->completed_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y H:i:s') }}
                                        </p>
                                    </li>
                                @endif

                                @if ($consentSession->cancelled_at)
                                    <li class="relative pl-8">
                                        <span class="absolute left-0 top-1.5 h-3 w-3 rounded-full bg-red-500"></span>

                                        <p class="font-medium text-red-700">
                                            Record Cancelled
                                        </p>

                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ $consentSession->cancelled_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y H:i:s') }}
                                        </p>
                                    </li>
                                @endif
                            </ol>
                        </div>
                    </section>

                    {{-- Version protection --}}
                    <section class="overflow-hidden rounded-2xl border border-amber-200 bg-amber-50 shadow-sm">
                        <div class="p-6">
                            <h2 class="font-bold text-amber-950">
                                Version Protected
                            </h2>

                            <p class="mt-3 text-sm leading-6 text-amber-800">
                                This record remains permanently linked to
                                version
                                {{ $publishedVersion?->version_number ?? '—' }}
                                of the consent document.
                            </p>

                            <p class="mt-3 text-sm leading-6 text-amber-800">
                                Publishing or editing newer template versions will not alter this evidence.
                            </p>
                        </div>
                    </section>

                </aside>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const panel = document.getElementById(
                'individual-consent-share-panel'
            );

            if (!panel) {
                return;
            }

            const signingUrl = panel.dataset.signingUrl;
            const shareTitle = panel.dataset.shareTitle;
            const shareText = panel.dataset.shareText;
            const urlInput = document.getElementById(
                'individual-consent-signing-url'
            );
            const copyButton = document.getElementById(
                'copy-individual-consent-link'
            );
            const shareButton = document.getElementById(
                'share-individual-consent-link'
            );
            const status = document.getElementById(
                'individual-consent-share-status'
            );

            const setStatus = (message) => {
                if (status) {
                    status.textContent = message;
                }
            };

            const copyWithFallback = async () => {
                try {
                    if (
                        navigator.clipboard
                        && window.isSecureContext
                    ) {
                        await navigator.clipboard.writeText(
                            signingUrl
                        );
                    } else {
                        urlInput.focus();
                        urlInput.select();

                        const copied = document.execCommand(
                            'copy'
                        );

                        if (!copied) {
                            throw new Error('Copy command failed.');
                        }
                    }

                    setStatus('Signing link copied.');
                } catch (error) {
                    urlInput.focus();
                    urlInput.select();
                    setStatus(
                        'Copy was blocked. The link is selected so you can copy it manually.'
                    );
                }
            };

            copyButton?.addEventListener(
                'click',
                copyWithFallback
            );

            if (
                shareButton
                && typeof navigator.share === 'function'
            ) {
                shareButton.classList.remove('hidden');
                shareButton.classList.add('inline-flex');

                shareButton.addEventListener(
                    'click',
                    async () => {
                        try {
                            await navigator.share({
                                title: shareTitle,
                                text: shareText,
                                url: signingUrl,
                            });

                            setStatus('Share options opened.');
                        } catch (error) {
                            if (error?.name !== 'AbortError') {
                                await copyWithFallback();
                            }
                        }
                    }
                );
            }
        });
    </script>

</x-app-layout>
