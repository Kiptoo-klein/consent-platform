@php
    $savedSchema = $consentTemplate->template_schema ?? [];

    $savedFields = $savedSchema['additional_fields'] ?? [];

    if (old('additional_fields_json') !== null) {
        $decodedOldFields = json_decode(
            old('additional_fields_json'),
            true
        );

        if (is_array($decodedOldFields)) {
            $savedFields = $decodedOldFields;
        }
    }

    $savedConsentText = old(
        'content',
        $savedSchema['consent_html']
            ?? $savedSchema['consent_text']
            ?? ''
    );

    $savedUsageTypes = old(
        'usage_types',
        $consentTemplate->usageSelections()
    );
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Consent Template
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-8">

                <div class="mb-8">
                    <h1 class="text-3xl font-bold">
                        Edit Draft
                    </h1>

                    <p class="text-gray-500 mt-2">
                        Update this template before it is published.
                    </p>
                </div>

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
                    action="{{ route('consent-templates.update', $consentTemplate) }}"
                    x-data="{
                        fields: @js($savedFields),

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
                    @method('PUT')

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
                            value="{{ old('title', $consentTemplate->title) }}"
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
                        >{{ old('description', $consentTemplate->description) }}</textarea>

                        @error('description')
                            <p class="text-sm text-red-600 mt-2">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    <!-- Template Usage -->
                    <div class="mb-8 rounded-xl border border-indigo-200 bg-indigo-50 p-5">
                        <div>
                            <h2 class="text-lg font-semibold text-indigo-950">
                                Where will this template be used?
                            </h2>

                            <p class="mt-1 text-sm text-indigo-800">
                                Select one or both workflows. Existing templates remain available in both workflows unless changed.
                            </p>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-indigo-200 bg-white p-4 transition hover:border-indigo-400">
                                <input
                                    type="checkbox"
                                    name="usage_types[]"
                                    value="{{ \App\Models\ConsentTemplate::USAGE_INDIVIDUAL }}"
                                    @checked(
                                        in_array(
                                            \App\Models\ConsentTemplate::USAGE_INDIVIDUAL,
                                            $savedUsageTypes,
                                            true
                                        )
                                    )
                                    class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                >

                                <span>
                                    <span class="block font-semibold text-gray-900">
                                        Individual consent
                                    </span>

                                    <span class="mt-1 block text-sm leading-5 text-gray-600">
                                        Organization users create a secure consent link for one named signer.
                                    </span>
                                </span>
                            </label>

                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-indigo-200 bg-white p-4 transition hover:border-indigo-400">
                                <input
                                    type="checkbox"
                                    name="usage_types[]"
                                    value="{{ \App\Models\ConsentTemplate::USAGE_SIGNING_STATION }}"
                                    @checked(
                                        in_array(
                                            \App\Models\ConsentTemplate::USAGE_SIGNING_STATION,
                                            $savedUsageTypes,
                                            true
                                        )
                                    )
                                    class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                >

                                <span>
                                    <span class="block font-semibold text-gray-900">
                                        Public signing station
                                    </span>

                                    <span class="mt-1 block text-sm leading-5 text-gray-600">
                                        The template can be selected when creating a kiosk or public signing station.
                                    </span>
                                </span>
                            </label>
                        </div>

                        @error('usage_types')
                            <p class="mt-3 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                        @error('usage_types.*')
                            <p class="mt-3 text-sm font-medium text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <x-consent-template-docx-import />

                    <!-- Rich Consent Document -->
                    <div class="mb-10">
                        <x-consent-template-rich-editor
                            :value="$savedConsentText"
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
                                    Add any extra information needed from the signer.
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
                                    When provided, the signer automatically receives a signed PDF copy.
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
                            Save Changes
                        </button>

                        <a
                            href="{{ route('consent-templates.manage') }}"
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
