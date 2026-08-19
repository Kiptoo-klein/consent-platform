<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Send to Multiple People
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    {{ $consentTemplate->title }}
                </p>
            </div>
        </div>
    </x-slot>

    @php
        $recipientRows = old(
            'recipients',
            [
                [
                    'name' => '',
                    'email' => '',
                    'reference' => '',
                ],
            ]
        );

        if (! is_array($recipientRows) || $recipientRows === []) {
            $recipientRows = [
                [
                    'name' => '',
                    'email' => '',
                    'reference' => '',
                ],
            ];
        }
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            <x-signed-consent-capacity
                :capacity="$signedConsentCapacity"
                :bulk="true"
            />

            @if ($errors->any())
                <div class="rounded-xl border border-red-300 bg-red-50 px-5 py-4 text-red-800">
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

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 bg-gray-50 px-6 py-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-700">
                                Bulk consent campaign
                            </p>

                            <h1 class="mt-2 text-2xl font-bold text-gray-950">
                                {{ $consentTemplate->title }}
                            </h1>

                            <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600">
                                Every recipient receives a different secure link and an independent consent record, signature, status, and audit trail.
                            </p>
                        </div>

                        <span class="inline-flex rounded-full bg-indigo-100 px-3 py-1.5 text-sm font-bold text-indigo-800">
                            Maximum {{ $maximumRecipients }}
                        </span>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{ route('consent-campaigns.store', $consentTemplate) }}"
                    enctype="multipart/form-data"
                    class="space-y-7 p-6"
                    id="bulk-consent-form"
                >
                    @csrf

                    <div class="grid gap-6 lg:grid-cols-2">
                        <div>
                            <label
                                for="campaign_name"
                                class="block text-sm font-semibold text-gray-800"
                            >
                                Campaign name
                                <span class="text-red-600">*</span>
                            </label>

                            <input
                                id="campaign_name"
                                name="campaign_name"
                                type="text"
                                maxlength="255"
                                required
                                autofocus
                                value="{{ old('campaign_name') }}"
                                placeholder="Example: Staff NDA – August 2026"
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >

                            <p class="mt-2 text-xs leading-5 text-gray-500">
                                Use a name that makes this group easy to identify later.
                            </p>
                        </div>

                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                            <p class="font-semibold text-blue-950">
                                Published version {{ $publishedVersion->version_number }}
                            </p>

                            <p class="mt-1 text-sm leading-6 text-blue-900">
                                All records in this campaign remain linked to this exact published version, even if the template is changed later.
                            </p>
                        </div>
                    </div>

                    <section class="rounded-xl border border-gray-200">
                        <div class="flex flex-col gap-4 border-b border-gray-200 bg-gray-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="font-bold text-gray-950">
                                    Recipients
                                </h2>

                                <p class="mt-1 text-sm text-gray-600">
                                    Add any number of people from 1 to {{ $maximumRecipients }}. Existing entries stay visible on this page as you add more.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <span
                                    id="recipient-count"
                                    class="inline-flex rounded-full bg-gray-200 px-3 py-1 text-xs font-bold text-gray-700"
                                >
                                    1 of {{ $maximumRecipients }}
                                </span>

                                <button
                                    type="button"
                                    id="add-recipient"
                                    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                                >
                                    Add Recipient
                                </button>
                            </div>
                        </div>

                        <div
                            id="recipient-list"
                            class="divide-y divide-gray-200"
                            data-bulk-recipient-list
                            data-maximum-recipients="{{ $maximumRecipients }}"
                            data-recipient-template-id="recipient-row-template"
                            data-add-recipient-button-id="add-recipient"
                            data-recipient-count-id="recipient-count"
                        >
                            @foreach ($recipientRows as $index => $recipient)
                                <div
                                    class="recipient-row grid gap-4 px-5 py-5 lg:grid-cols-[3rem_1fr_1fr_0.8fr_auto]"
                                    data-recipient-row
                                >
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-800">
                                        <span data-recipient-number>
                                            {{ $index + 1 }}
                                        </span>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Full name
                                        </label>

                                        <input
                                            type="text"
                                            name="recipients[{{ $index }}][name]"
                                            value="{{ is_array($recipient) ? ($recipient['name'] ?? '') : '' }}"
                                            maxlength="255"
                                            required
                                            placeholder="Recipient's full name"
                                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            data-recipient-field="name"
                                        >
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Email
                                        </label>

                                        <input
                                            type="email"
                                            name="recipients[{{ $index }}][email]"
                                            value="{{ is_array($recipient) ? ($recipient['email'] ?? '') : '' }}"
                                            maxlength="255"
                                            required
                                            placeholder="name@example.com"
                                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            data-recipient-field="email"
                                        >
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            Reference
                                        </label>

                                        <input
                                            type="text"
                                            name="recipients[{{ $index }}][reference]"
                                            value="{{ is_array($recipient) ? ($recipient['reference'] ?? '') : '' }}"
                                            maxlength="255"
                                            placeholder="Optional"
                                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            data-recipient-field="reference"
                                        >
                                    </div>

                                    <div class="flex items-end">
                                        <button
                                            type="button"
                                            class="remove-recipient inline-flex h-10 items-center justify-center rounded-lg border border-red-300 bg-red-50 px-3 text-sm font-semibold text-red-700 hover:bg-red-100"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-gray-200 bg-gray-50 px-5 py-5">
                            <label
                                for="recipient_file"
                                class="block text-sm font-semibold text-gray-800"
                            >
                                Upload recipient CSV
                                <span class="font-normal text-gray-500">
                                    (optional)
                                </span>
                            </label>

                            <input
                                id="recipient_file"
                                name="recipient_file"
                                type="file"
                                accept=".csv,.txt,text/csv,text/plain"
                                class="mt-2 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700"
                            >

                            <p class="mt-2 text-xs leading-5 text-gray-500">
                                Use columns:
                                <span class="font-semibold">name, email, reference</span>.
                                The reference column is optional. Manual and uploaded recipients are combined, up to {{ $maximumRecipients }} people.
                            </p>
                        </div>
                    </section>

                    <section
                        id="signing-deadline-panel"
                        class="rounded-xl border border-amber-200 bg-amber-50 p-5"
                    >
                        <div>
                            <p class="text-sm font-semibold text-gray-900">
                                Shared signing deadline
                                <span class="font-normal text-gray-500">
                                    (optional)
                                </span>
                            </p>

                            <p class="mt-1 text-xs leading-5 text-gray-600">
                                Leave both fields blank for no deadline. Select a date first if you also want to set a time.
                            </p>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label
                                    for="expires_date"
                                    class="block text-sm font-medium text-gray-800"
                                >
                                    Date
                                </label>

                                <input
                                    id="expires_date"
                                    name="expires_date"
                                    type="date"
                                    value="{{ old('expires_date') }}"
                                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            </div>

                            <div>
                                <label
                                    for="expires_time"
                                    class="block text-sm font-medium text-gray-800"
                                >
                                    Time
                                </label>

                                <input
                                    id="expires_time"
                                    name="expires_time"
                                    type="time"
                                    value="{{ old('expires_time') }}"
                                    @disabled(blank(old('expires_date')))
                                    aria-describedby="expires-time-help"
                                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400"
                                >

                                <p
                                    id="expires-time-help"
                                    class="mt-2 text-xs leading-5 text-gray-500"
                                >
                                    Select a date first to enable the optional time.
                                </p>
                            </div>
                        </div>
                    </section>

                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
                        <h2 class="font-bold text-emerald-950">
                            What happens after creation
                        </h2>

                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm leading-6 text-emerald-900">
                            <li>
                                One independent consent record is created for each recipient.
                            </li>

                            <li>
                                Each person receives a different secure signing link by email.
                            </li>

                            <li>
                                Only completed signatures count toward the plan’s signed-consent limit.
                            </li>

                            <li>
                                Failed emails do not delete the created records and can be retried from each record.
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-6 sm:flex-row sm:justify-end">
                        <a
                            href="{{ route('consent-templates.published', $consentTemplate) }}"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            @disabled($signedConsentCapacity['reached'])
                            class="inline-flex items-center justify-center rounded-lg bg-green-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-gray-400"
                        >
                            Create Campaign and Send
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <template id="recipient-row-template">
        <div
            class="recipient-row grid gap-4 px-5 py-5 lg:grid-cols-[3rem_1fr_1fr_0.8fr_auto]"
            data-recipient-row
        >
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-800">
                <span data-recipient-number></span>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">
                    Full name
                </label>

                <input
                    type="text"
                    maxlength="255"
                    required
                    placeholder="Recipient's full name"
                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    data-recipient-field="name"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">
                    Email
                </label>

                <input
                    type="email"
                    maxlength="255"
                    required
                    placeholder="name@example.com"
                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    data-recipient-field="email"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">
                    Reference
                </label>

                <input
                    type="text"
                    maxlength="255"
                    placeholder="Optional"
                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    data-recipient-field="reference"
                >
            </div>

            <div class="flex items-end">
                <button
                    type="button"
                    class="remove-recipient inline-flex h-10 items-center justify-center rounded-lg border border-red-300 bg-red-50 px-3 text-sm font-semibold text-red-700 hover:bg-red-100"
                >
                    Remove
                </button>
            </div>
        </div>
    </template>


</x-app-layout>
