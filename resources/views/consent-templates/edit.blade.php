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
                >
                    @csrf
                    @method('PUT')

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

                    <x-consent-template-question-builder
                        :fields="$savedFields"
                    />

                    <hr class="my-8">

                    <x-consent-template-signing-information />

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
