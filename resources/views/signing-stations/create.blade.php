<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                    Create Signing Station
                </h2>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Create a kiosk for collecting consent on a shared device.
                </p>
            </div>

            <a
                href="{{ route('signing-stations.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
            >
                Cancel
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form
                method="POST"
                action="{{ route('signing-stations.store') }}"
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                @csrf

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
                            value="{{ old('name') }}"
                            placeholder="Reception Tablet"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        >

                        @error('name')
                            <p class="mt-2 text-sm text-red-600">
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
                                        old('consent_template_id') == $template->id
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
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <input
                                type="checkbox"
                                name="require_email"
                                value="1"
                                @checked(old('require_email'))
                                class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            >

                            <span>
                                <span class="block text-sm font-semibold text-gray-800 dark:text-gray-100">
                                    Require email address
                                </span>

                                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                    The signer must provide a valid email address.
                                </span>
                            </span>
                        </label>

                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <input
                                type="checkbox"
                                name="require_reference"
                                value="1"
                                @checked(old('require_reference'))
                                class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            >

                            <span>
                                <span class="block text-sm font-semibold text-gray-800 dark:text-gray-100">
                                    Require reference
                                </span>

                                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                                    Require a signer reference number.
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
                                required
                                value="{{ old('auto_reset_seconds', 10) }}"
                                class="block w-32 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            >

                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                seconds
                            </span>
                        </div>

                        @error('auto_reset_seconds')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800/50">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
                    >
                        Create Signing Station
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
