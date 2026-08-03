<x-layouts.public-consent
    :title="$consentSession->consentTemplate?->title ?? 'Consent'"
    :organization="$consentSession->organization"
    :organization-name="$consentSession->organization?->name ?? config('app.name')"
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

        $existingResponses =
            old(
                'responses',
                $consentSession->responses ?? []
            );
    @endphp

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
                    ? 'on '.$consentSession->expires_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y H:i')
                    : 'before it was completed' }}
                and can no longer be reviewed or signed.
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
                This consent record has already been completed and cannot be changed.
            </p>
        </div>
    @else
        <div class="space-y-6">

            @if ($consentSession->expires_at)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                    <p class="font-semibold">
                        Complete this consent before
                        {{ $consentSession->expires_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y H:i') }}.
                    </p>

                    <p class="mt-1 text-amber-800">
                        The signing link will stop working after this deadline.
                    </p>
                </div>
            @endif

            <section class="overflow-hidden rounded-xl bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-5">
                    <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">
                        Consent Form
                    </p>

                    <h2 class="mt-2 text-2xl font-bold text-gray-900">
                        {{ $consentSession->consentTemplate?->title }}
                    </h2>

                    @if ($consentSession->consentTemplate?->description)
                        <p class="mt-2 text-gray-600">
                            {{ $consentSession->consentTemplate->description }}
                        </p>
                    @endif
                </div>

                <div class="p-6">
                    <div class="grid gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Signer
                            </p>

                            <p class="mt-1 font-medium text-gray-900">
                                {{ $consentSession->signer_name }}
                            </p>
                        </div>

                        @if ($consentSession->signer_reference)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    Reference
                                </p>

                                <p class="mt-1 font-medium text-gray-900">
                                    {{ $consentSession->signer_reference }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="mt-6">
                        <h3 class="text-lg font-bold text-gray-900">
                            Please Review
                        </h3>

                        @if ($consentText)
                            <div class="prose mt-4 max-w-none whitespace-pre-line text-gray-700">
                                {{ $consentText }}
                            </div>
                        @else
                            <div class="mt-4 rounded-lg border border-yellow-300 bg-yellow-50 p-4 text-yellow-800">
                                The published consent content could not be displayed.
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            <form
                method="POST"
                action="{{ route('public-consent.update', $consentSession->access_token) }}"
                class="overflow-hidden rounded-xl bg-white shadow-sm"
            >
                @csrf
                @method('PATCH')

                @if (count($additionalFields) > 0)
                <div class="border-b border-gray-200 px-6 py-5">
                    <h2 class="text-xl font-bold text-gray-900">
                        Additional Information
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Complete the fields below before continuing.
                    </p>
                </div>
                @endif

                <div class="space-y-6 p-6">
                    @foreach ($additionalFields as $index => $field)
                        @php
                            $fieldKey =
                                $field['name']
                                ?? $field['key']
                                ?? $field['id']
                                ?? 'field_'.$index;

                            $fieldType =
                                $field['type']
                                ?? 'text';

                            $fieldLabel =
                                $field['label']
                                ?? 'Field '.($index + 1);

                            $fieldValue =
                                $existingResponses[$fieldKey]
                                ?? null;

                            $isRequired =
                                $field['required']
                                ?? false;

                            $fieldOptions =
                                $field['options']
                                ?? [];
                        @endphp

                        <div>
                            <label
                                for="response_{{ $fieldKey }}"
                                class="block text-sm font-medium text-gray-800"
                            >
                                {{ $fieldLabel }}

                                @if ($isRequired)
                                    <span class="text-red-600">
                                        *
                                    </span>
                                @endif
                            </label>

                            @if ($fieldType === 'textarea')
                                <textarea
                                    id="response_{{ $fieldKey }}"
                                    name="responses[{{ $fieldKey }}]"
                                    rows="4"
                                    @required($isRequired)
                                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >{{ $fieldValue }}</textarea>

                            @elseif ($fieldType === 'select')
                                <select
                                    id="response_{{ $fieldKey }}"
                                    name="responses[{{ $fieldKey }}]"
                                    @required($isRequired)
                                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">
                                        Select an option
                                    </option>

                                    @foreach ($fieldOptions as $option)
                                        <option
                                            value="{{ $option }}"
                                            @selected((string) $fieldValue === (string) $option)
                                        >
                                            {{ $option }}
                                        </option>
                                    @endforeach
                                </select>

                            @elseif ($fieldType === 'radio')
                                <div class="mt-3 space-y-2">
                                    @foreach ($fieldOptions as $option)
                                        <label class="flex items-center gap-3">
                                            <input
                                                type="radio"
                                                name="responses[{{ $fieldKey }}]"
                                                value="{{ $option }}"
                                                @checked((string) $fieldValue === (string) $option)
                                                @required($isRequired)
                                                class="border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            >

                                            <span class="text-gray-700">
                                                {{ $option }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>

                            @elseif ($fieldType === 'checkbox')
                                <input
                                    type="hidden"
                                    name="responses[{{ $fieldKey }}]"
                                    value="0"
                                >

                                <label class="mt-3 flex items-start gap-3">
                                    <input
                                        id="response_{{ $fieldKey }}"
                                        type="checkbox"
                                        name="responses[{{ $fieldKey }}]"
                                        value="1"
                                        @checked((bool) $fieldValue)
                                        @required($isRequired)
                                        class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    >

                                    <span class="text-gray-700">
                                        {{ $field['description'] ?? $fieldLabel }}
                                    </span>
                                </label>

                            @else
                                <input
                                    id="response_{{ $fieldKey }}"
                                    name="responses[{{ $fieldKey }}]"
                                    type="{{ in_array($fieldType, ['email', 'number', 'date'], true) ? $fieldType : 'text' }}"
                                    value="{{ $fieldValue }}"
                                    @required($isRequired)
                                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            @endif

                            @error("responses.{$fieldKey}")
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    @endforeach

                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                        By continuing, you confirm that you have reviewed the consent information above. Your consent will not be completed until the signature step is submitted.
                    </div>

                    <button
                        type="submit"
                        class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Continue to Signature
                    </button>
                </div>
            </form>
        </div>
    @endif
</x-layouts.public-consent>


    {{-- PUBLIC_STATION_CONSENT_SHOW_TIMEOUT_INCLUDE --}}
    @if ($consentSession->signing_station_id && ! $consentSession->isCompleted())
        @include('public-signing-stations.partials.inactivity-timeout', [
            'cancelAction' => route(
                'public-consent.cancel',
                $consentSession->access_token
            ),
            'timeoutFormId' => 'public-station-consent-show-timeout-form',
        ])
    @endif
