<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                    Edit Signing Station
                </h2>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Update the station settings and consent template.
                </p>
            </div>

            <a
                href="{{ route('signing-stations.show', $signingStation) }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
            >
                Cancel
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/40">
                    <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">
                        Please correct the following errors:
                    </h3>

                    <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700 dark:text-red-300">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('signing-stations.update', $signingStation) }}"
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
                x-data
                data-signing-station-edit-form
            >
                @csrf
                @method('PUT')

                <div class="space-y-6 p-6">
                    <div>
                        <label
                            for="name"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Station name
                        </label>

                        <input
                            id="name"
                            name="name"
                            type="text"
                            required
                            autofocus
                            value="{{ old('name', $signingStation->name) }}"
                            placeholder="Reception Tablet"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        >

                        @error('name')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label
                            for="consent_template_id"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Consent template
                        </label>

                        <select
                            id="consent_template_id"
                            name="consent_template_id"
                            required
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        >
                            <option value="">
                                Select a published signing-station template
                            </option>

                            @foreach ($consentTemplates as $template)
                                <option
                                    value="{{ $template->id }}"
                                    @selected(
                                        old(
                                            'consent_template_id',
                                            $signingStation->consent_template_id
                                        ) == $template->id
                                    )
                                >
                                    {{ $template->title }}
                                </option>
                            @endforeach
                        </select>

                        @if ($consentTemplates->isEmpty())
                            <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">
                                No published templates are enabled for public signing stations.
                                Update a consent template and select
                                <span class="font-semibold">Public signing station</span>.
                            </p>
                        @endif

                        @error('consent_template_id')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 transition hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">
                            <input
                                type="checkbox"
                                name="require_email"
                                value="1"
                                @checked(
                                    old(
                                        'require_email',
                                        $signingStation->require_email
                                    )
                                )
                                class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            >

                            <span>
                                <span class="block text-sm font-semibold text-gray-800 dark:text-gray-100">
                                    Require email address
                                </span>

                                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                    Signers must provide a valid email address before starting.
                                </span>
                            </span>
                        </label>
                    </div>

                    @include('signing-stations.partials.email-delivery-settings')

                    <div>
                        <label
                            for="auto_reset_seconds"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Return to station after completion
                        </label>

                        <div class="mt-2 flex items-center gap-3">
                            <input
                                id="auto_reset_seconds"
                                name="auto_reset_seconds"
                                type="number"
                                min="3"
                                max="300"
                                step="1"
                                required
                                value="{{ old(
                                    'auto_reset_seconds',
                                    $signingStation->auto_reset_seconds ?? 3
                                ) }}"
                                class="block w-32 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            >

                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                seconds
                            </span>
                        </div>

                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            The completed consent page will automatically return to this station.
                        </p>

                        @error('auto_reset_seconds')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="mx-6 mb-6 overflow-hidden rounded-xl border border-amber-300 bg-amber-50 text-sm text-amber-950 shadow-sm dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100">
                    <div class="border-b border-amber-300 bg-amber-100 px-5 py-4 dark:border-amber-700 dark:bg-amber-900/40">
                        <p class="font-bold">
                            Warning: changing this kiosk creates a new QR code
                        </p>

                        <p class="mt-1 leading-6">
                            Review the effects below before saving.
                        </p>
                    </div>

                    <div class="px-5 py-4">
                        <ul class="space-y-3">
                            <li class="flex gap-3">
                                <span aria-hidden="true">•</span>

                                <span>
                                    The current public kiosk link stops
                                    working immediately.
                                </span>
                            </li>

                            <li class="flex gap-3">
                                <span aria-hidden="true">•</span>

                                <span>
                                    Every downloaded or printed QR poster
                                    containing the old code becomes invalid.
                                </span>
                            </li>

                            <li class="flex gap-3">
                                <span aria-hidden="true">•</span>

                                <span>
                                    Current kiosk-device leases are released,
                                    so kiosk browsers may need to reopen the
                                    new link.
                                </span>
                            </li>

                            <li class="flex gap-3">
                                <span aria-hidden="true">•</span>

                                <span>
                                    A fresh 24-hour QR acceptance window
                                    begins when the changes are saved.
                                </span>
                            </li>

                            <li class="flex gap-3">
                                <span aria-hidden="true">•</span>

                                <span>
                                    You must download and distribute a new
                                    QR poster.
                                </span>
                            </li>

                            <li class="flex gap-3">
                                <span aria-hidden="true">•</span>

                                <span>
                                    Existing completed consent records are
                                    not deleted or changed.
                                </span>
                            </li>
                        </ul>

                        <p class="mt-4 border-t border-amber-300 pt-4 font-semibold dark:border-amber-700">
                            Saving without changing anything keeps the
                            current kiosk link, QR code and expiry time.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end dark:border-gray-700 dark:bg-gray-800/50">
                    <a
                        href="{{ route('signing-stations.show', $signingStation) }}"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Save Changes
                    </button>
                </div>

                <x-action-confirmation-modal
                    name="signing-station-qr-change"
                    title="Create a new signing-station QR code?"
                    message="Saving these changes will immediately invalidate the current public kiosk link, QR code and every printed poster. Current kiosk-device leases will be released, and a new QR code with a fresh 24-hour window will be created. Existing completed consent records will not be deleted."
                    confirm-text="Save changes and create new QR"
                    variant="warning"
                    confirm-event="signing-station-qr-change-confirmed"
                />

</form>
        </div>
    </div>

<script data-qr-change-confirmation>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector(
            '[data-signing-station-edit-form]'
        );

        if (! form) {
            return;
        }

        const snapshot = () => {
            return JSON.stringify(
                Array.from(
                    new FormData(form).entries()
                )
                    .filter(([name]) => {
                        return ! [
                            '_token',
                            '_method',
                        ].includes(name);
                    })
                    .map(([name, value]) => {
                        return [
                            name,
                            String(value),
                        ];
                    })
                    .sort((left, right) => {
                        const leftValue =
                            left[0] + '\u0000' + left[1];

                        const rightValue =
                            right[0] + '\u0000' + right[1];

                        return leftValue.localeCompare(
                            rightValue
                        );
                    })
            );
        };

        const original = snapshot();
        let confirmationGranted = false;

        form.addEventListener('submit', (event) => {
            if (confirmationGranted) {
                return;
            }

            /*
             * Unchanged values preserve the existing QR code
             * and therefore require no warning.
             */
            if (snapshot() === original) {
                return;
            }

            event.preventDefault();

            window.dispatchEvent(
                new CustomEvent(
                    'open-modal',
                    {
                        detail:
                            'signing-station-qr-change',
                    }
                )
            );
        });

        window.addEventListener(
            'signing-station-qr-change-confirmed',
            () => {
                confirmationGranted = true;

                form.requestSubmit();

                /*
                 * Restore protection when native validation
                 * prevents the request from being submitted.
                 */
                window.setTimeout(() => {
                    confirmationGranted = false;
                }, 0);
            }
        );
    });
</script>

</x-app-layout>
