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
                >
                    @csrf

                    @if ($isBulkCreation)
                        <input
                            type="hidden"
                            name="return_to"
                            value="bulk"
                        >
                    @endif

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

                    <x-consent-template-question-builder />

                    <hr class="my-8">

                    <x-consent-template-signing-information />

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
