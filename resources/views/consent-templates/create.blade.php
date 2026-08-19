<x-app-layout>
    @php
        $isBulkCreation =
            ($returnTo ?? null) === 'bulk';
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    New Consent
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    {{ $isBulkCreation
                        ? 'Create and publish a consent, then continue directly to recipients.'
                        : 'Create one reusable consent and decide how to use it after publishing.' }}
                </p>
            </div>

            <a
                href="{{ $isBulkCreation
                        ? route('consent-campaigns.select-template')
                        : route('consent-templates.manage') }}"
                class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Back to Templates
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-8">

                <div class="mb-8">
                    <p class="text-sm font-bold uppercase tracking-[0.16em] text-indigo-700">
                        Consent template
                    </p>

                    <h1 class="mt-2 text-3xl font-bold text-gray-950">
                        Create the consent form from scratch
                    </h1>
                </div>

                @if ($isBulkCreation)
                    <div class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50 p-5">
                        <p class="text-sm font-bold uppercase tracking-[0.16em] text-indigo-700">
                            New bulk consent template
                        </p>

                        <h2 class="mt-2 text-xl font-bold text-indigo-950">
                            Create this template from scratch
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-indigo-900">
                            When you continue, version 1 will be published automatically and you will move directly to the recipient form.
                        </p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-lg border border-red-300 bg-red-50 p-4">
                        <h2 class="font-semibold text-red-800 mb-2">
                            Please correct the following:
                        </h2>

                        <ul class="list-disc pl-5 text-sm text-red-700 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('consent-templates.store') }}"
                    x-data="{
                        fields: [],

                        addField() {
                            this.fields.push({
                                id: crypto.randomUUID
                                    ? crypto.randomUUID()
                                    : Date.now() + '-' + Math.random(),
                                label: '',
                                required: false
                            });
                        },

                        removeField(index) {
                            this.fields.splice(index, 1);
                        }
                    }"
                >
                    @csrf

                    @if ($isBulkCreation)
                        <input
                            type="hidden"
                            name="return_to"
                            value="bulk"
                        >
                    @endif

                    <!-- JSON submitted to the application -->
                    <input
                        type="hidden"
                        name="additional_fields_json"
                        :value="JSON.stringify(fields)"
                    >

                    <!-- Template Title -->
                    <div class="mb-6">
                        <label
                            for="title"
                            class="block font-semibold mb-2"
                        >
                            Template Title
                        </label>

                        <input
                            id="title"
                            type="text"
                            name="title"
                            value="{{ old('title') }}"
                            class="w-full border-gray-300 rounded-lg p-3 focus:border-blue-500 focus:ring-blue-500"
                            required
                        >

                        @error('title')
                            <p class="text-sm text-red-600 mt-2">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="mb-6">
                        <label
                            for="description"
                            class="block font-semibold mb-2"
                        >
                            Description
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            class="w-full border-gray-300 rounded-lg p-3 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Briefly describe this consent template"
                        >{{ old('description') }}</textarea>

                        @error('description')
                            <p class="text-sm text-red-600 mt-2">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <x-consent-template-docx-import />

                    <!-- Rich Consent Document -->
                    <div class="mb-10">
                        <x-consent-template-rich-editor
                            :value="old('content', '')"
                        />

                        @error('content')
                            <p class="text-sm text-red-600 mt-2">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <hr class="my-8">

                    <!-- Additional Fields -->
                    <div class="mb-10">

                        <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center mb-6">

                            <div>
                                <h2 class="text-2xl font-semibold">
                                    Additional Fields
                                </h2>

                                <p class="text-sm text-gray-500 mt-1">
                                    Add any extra information your organization needs from the signer.
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="addField()"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg"
                            >
                                + Add Additional Field
                            </button>
                        </div>

                        <template
                            x-for="(field, index) in fields"
                            :key="field.id"
                        >
                            <div class="border border-gray-200 rounded-lg bg-gray-50 p-5 mb-4">

                                <div class="flex justify-between items-center mb-4">
                                    <h3
                                        class="font-semibold"
                                        x-text="'Additional Field ' + (index + 1)"
                                    ></h3>

                                    <button
                                        type="button"
                                        @click="removeField(index)"
                                        class="text-sm text-red-600 hover:text-red-800 hover:underline"
                                    >
                                        Remove Field
                                    </button>
                                </div>

                                <label
                                    class="block font-semibold mb-2"
                                    :for="'field-label-' + field.id"
                                >
                                    Field Label
                                </label>

                                <input
                                    type="text"
                                    x-model="field.label"
                                    :id="'field-label-' + field.id"
                                    class="w-full border-gray-300 rounded-lg p-3 mb-4 focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="For example: National ID Number"
                                    required
                                >

                                <label class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        x-model="field.required"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    >

                                    <span>
                                        Required
                                    </span>
                                </label>
                            </div>
                        </template>

                        <div
                            x-show="fields.length === 0"
                            class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center text-gray-500"
                        >
                            No additional fields added yet.
                        </div>

                    </div>

                    <hr class="my-8">

                    <!-- Standard Signer Information -->
                    <div class="mb-10">

                        <h2 class="text-2xl font-semibold mb-2">
                            Standard Signer Information
                        </h2>

                        <p class="text-sm text-gray-500 mb-5">
                            These fields are included automatically and cannot be removed.
                        </p>

                        <div class="space-y-3">

                            <div class="border border-gray-200 bg-gray-100 rounded-lg p-4">
                                <span class="font-semibold">
                                    🔒 Signer Name
                                </span>
                            </div>

                            <div class="border border-gray-200 bg-gray-100 rounded-lg p-4">

                                <span class="font-semibold">
                                    🔒 Email (Optional)
                                </span>

                                <p class="text-sm text-gray-500 mt-2">
                                    When an email is provided, the signer automatically receives a signed PDF copy.
                                </p>
                            </div>

                            <div class="border border-gray-200 bg-gray-100 rounded-lg p-4">
                                <span class="font-semibold">
                                    🔒 Signature
                                </span>
                            </div>

                        </div>
                    </div>

                    <hr class="my-8">

                    <!-- Automatically Added -->
                    <div class="mb-10">

                        <h2 class="text-2xl font-semibold mb-2">
                            Automatically Added
                        </h2>

                        <p class="text-sm text-gray-500 mb-4">
                            The platform records this information when the consent is signed.
                        </p>

                        <ul class="space-y-2 text-gray-700">
                            <li>✔ Secure Timestamp</li>
                            <li>✔ Document ID</li>
                            <li>✔ Consent Version</li>
                        </ul>
                    </div>

                    <div class="flex items-center gap-4">
                        <button
                            type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg"
                        >
                            {{ $isBulkCreation
                                ? 'Create Template and Continue'
                                : 'Save Draft' }}
                        </button>

                        <a
                            href="{{ $isBulkCreation
                                ? route('consent-campaigns.select-template')
                                : route('consent-templates.index') }}"
                            class="text-gray-600 hover:text-gray-900"
                        >
                            Cancel
                        </a>
                    </div>

                </form>

            </div>
        </div>
    </div>
</x-app-layout>
