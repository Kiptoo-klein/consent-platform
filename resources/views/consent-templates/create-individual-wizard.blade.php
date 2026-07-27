<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    New Individual Consent
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Build the form, add the signer and open the sharing screen in one workflow.
                </p>
            </div>

            <a
                href="{{ route('consent-templates.new') }}"
                class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Change Consent Type
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-300 bg-red-50 p-5 text-red-800">
                    <p class="font-semibold">Please correct the following:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('consent-templates.individual.store') }}"
                class="space-y-7"
                x-data="{
                    fields: JSON.parse(@js(old('additional_fields_json', '[]'))),
                    newField(label = '') {
                        return {
                            id: crypto.randomUUID
                                ? crypto.randomUUID()
                                : Date.now() + '-' + Math.random(),
                            label,
                            required: false
                        };
                    },
                    addField(label = '') {
                        this.fields.push(this.newField(label));
                    },
                    removeField(index) {
                        this.fields.splice(index, 1);
                    }
                }"
            >
                @csrf

                <input
                    type="hidden"
                    name="additional_fields_json"
                    :value="JSON.stringify(fields)"
                >

                <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 bg-blue-50 px-6 py-5">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-700">
                            Step 1
                        </p>
                        <h1 class="mt-2 text-2xl font-bold text-gray-950">
                            Build the consent template
                        </h1>
                        <p class="mt-1 text-sm text-gray-600">
                            The template will be published automatically and can be reused for future individual consent records.
                        </p>
                    </div>

                    <div class="space-y-6 p-6">
                        <div>
                            <label for="title" class="block text-sm font-semibold text-gray-900">
                                Template Title
                            </label>
                            <input
                                id="title"
                                type="text"
                                name="title"
                                value="{{ old('title') }}"
                                required
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-semibold text-gray-900">
                                Description
                                <span class="font-normal text-gray-500">(optional)</span>
                            </label>
                            <textarea
                                id="description"
                                name="description"
                                rows="3"
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Briefly describe this consent"
                            >{{ old('description') }}</textarea>
                        </div>

                        <div>
                            <label for="content" class="block text-sm font-semibold text-gray-900">
                                Consent Text
                            </label>
                            <textarea
                                id="content"
                                name="content"
                                rows="11"
                                required
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Write the consent text..."
                            >{{ old('content') }}</textarea>
                        </div>

                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200">
                    <div class="border-b border-gray-200 bg-emerald-50 px-6 py-5">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700">
                            Step 2
                        </p>
                        <h2 class="mt-2 text-2xl font-bold text-gray-950">
                            Add the first signer
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            These details belong to this consent record, not the reusable template.
                        </p>
                    </div>

                    <div class="space-y-7 p-6">
                        <div>
                            <label for="signer_name" class="block text-sm font-semibold text-gray-900">
                                Signer Name
                            </label>
                            <input
                                id="signer_name"
                                type="text"
                                name="signer_name"
                                value="{{ old('signer_name') }}"
                                required
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                        </div>

                        <div>
                            <label for="signer_email" class="block text-sm font-semibold text-gray-900">
                                Signer Email
                                <span class="font-normal text-gray-500">(optional)</span>
                            </label>
                            <input
                                id="signer_email"
                                type="email"
                                name="signer_email"
                                value="{{ old('signer_email') }}"
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                            <p class="mt-2 text-xs text-gray-500">
                                When provided, the initial signing email is sent automatically.
                            </p>
                        </div>

                        <div class="border-t border-gray-200 pt-7">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-950">
                                        Additional Fields
                                        <span class="text-sm font-normal text-gray-500">(optional)</span>
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Add any extra questions the signer should complete.
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">

                                    <button
                                        type="button"
                                        @click="addField()"
                                        class="inline-flex justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                                    >
                                        + Add Field
                                    </button>
                                </div>
                            </div>

                            <div class="mt-5 space-y-4">
                                <template x-for="(field, index) in fields" :key="field.id">
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                                        <div class="flex items-center justify-between gap-4">
                                            <p class="font-semibold" x-text="'Additional Field ' + (index + 1)"></p>
                                            <button
                                                type="button"
                                                @click="removeField(index)"
                                                class="text-sm font-medium text-red-600 hover:underline"
                                            >
                                                Remove
                                            </button>
                                        </div>

                                        <input
                                            type="text"
                                            x-model="field.label"
                                            class="mt-4 block w-full rounded-lg border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            placeholder="For example: National ID Number"
                                            required
                                        >

                                        <label class="mt-4 flex items-center gap-2 text-sm text-gray-700">
                                            <input
                                                type="checkbox"
                                                x-model="field.required"
                                                class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                            >
                                            Required field
                                        </label>
                                    </div>
                                </template>

                                <div
                                    x-show="fields.length === 0"
                                    class="rounded-xl border-2 border-dashed border-gray-300 p-7 text-center text-sm text-gray-500"
                                >
                                    No additional fields added.
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    id="signing-deadline-panel"
                    class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-amber-200"
                >
                    <div class="border-b border-amber-200 bg-amber-50 px-6 py-5">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-amber-700">
                            Step 3
                        </p>
                        <h2 class="mt-2 text-2xl font-bold text-gray-950">
                            Signing Deadline
                            <span class="text-base font-normal text-gray-500">(optional)</span>
                        </h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Automatic reminders use this deadline. Leave it blank when the signing link should not expire.
                        </p>
                    </div>

                    <div class="grid gap-5 p-6 sm:grid-cols-2">
                        <div>
                            <label for="expires_date" class="block text-sm font-semibold text-gray-900">
                                Deadline Date
                            </label>
                            <input
                                id="expires_date"
                                name="expires_date"
                                type="date"
                                min="{{ now()->format('Y-m-d') }}"
                                value="{{ old('expires_date') }}"
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                            >
                            <p id="expires-date-preview" class="mt-2 text-xs font-medium text-amber-800">
                                No deadline date selected.
                            </p>
                        </div>

                        <div>
                            <label for="expires_time" class="block text-sm font-semibold text-gray-900">
                                Deadline Time
                            </label>
                            <input
                                id="expires_time"
                                name="expires_time"
                                type="time"
                                value="{{ filled(old('expires_date')) ? old('expires_time', '00:00') : '' }}"
                                @disabled(blank(old('expires_date')))
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 disabled:cursor-not-allowed disabled:bg-gray-100"
                            >
                            <p class="mt-2 text-xs text-gray-500">
                                After selecting a date, the default time is 00:00 in {{ config('app.timezone') }}. A time without a date is invalid.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-indigo-200 bg-indigo-50 p-6">
                    <h2 class="text-lg font-bold text-indigo-950">
                        What happens next?
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-indigo-900">
                        The template is published, the individual consent record is created, and you are taken directly to the sharing screen containing Copy Link, Share via email, WhatsApp and Open Signing Page.
                    </p>
                </section>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a
                        href="{{ route('consent-templates.index') }}"
                        class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-5 py-3 font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="inline-flex justify-center rounded-lg bg-green-600 px-6 py-3 font-semibold text-white hover:bg-green-700"
                    >
                        Create, Publish & Open Sharing
                    </button>
                </div>
            </form>
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
                    timeInput.value = '';
                    preview.textContent = 'No deadline date selected.';
                    return;
                }

                if (timeInput.value === '') {
                    timeInput.value = '00:00';
                }

                const parts = dateInput.value.split('-');
                if (parts.length === 3) {
                    preview.textContent =
                        `Selected date: ${parts[2]}/${parts[1]}/${parts[0].slice(-2)}`;
                }
            };

            dateInput.addEventListener('change', updateDeadlineControls);
            updateDeadlineControls();
        });
    </script>
</x-app-layout>
