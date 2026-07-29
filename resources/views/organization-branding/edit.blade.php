<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Organization Branding
                </h1>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Manage how your organization appears across consent forms and signing stations.
                </p>
            </div>

            <a
                href="{{ route('dashboard') }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 transition hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"
                    />
                </svg>

                Back to dashboard
            </a>
        </div>
    </x-slot>

    @php
        $currentLogoUrl = $organization->logo
            ? asset('storage/'.$organization->logo)
            : null;

        $primaryColor = old(
            'primary_color',
            $organization->primary_color ?: '#4F46E5'
        );

        $secondaryColor = old(
            'secondary_color',
            $organization->secondary_color ?: '#6366F1'
        );

        $accentColor = old(
            'accent_color',
            $organization->accent_color ?: '#10B981'
        );

        $pdfPrimaryColor = old(
            'pdf_primary_color',
            $organization->pdf_primary_color ?: '#17324D'
        );

        $pdfAccentColor = old(
            'pdf_accent_color',
            $organization->pdf_accent_color ?: '#0F766E'
        );
    @endphp

    <div
        x-data="{
            logoPreview: @js($currentLogoUrl),
            removeLogo: false,
            primaryColor: @js($primaryColor),
            secondaryColor: @js($secondaryColor),
            accentColor: @js($accentColor),
            pdfPrimaryColor: @js($pdfPrimaryColor),
            pdfAccentColor: @js($pdfAccentColor),

            previewLogo(event) {
                const file = event.target.files[0];

                if (! file) {
                    return;
                }

                this.removeLogo = false;

                const reader = new FileReader();

                reader.onload = (result) => {
                    this.logoPreview = result.target.result;
                };

                reader.readAsDataURL(file);
            },

            clearLogo() {
                this.logoPreview = null;
                this.removeLogo = true;

                const input = this.$refs.logoInput;

                if (input) {
                    input.value = '';
                }
            }
        }"
        class="py-8 sm:py-10"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200">
                    <div class="flex items-start gap-3">
                        <svg
                            class="mt-0.5 h-5 w-5 shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="m4.5 12.75 6 6 9-13.5"
                            />
                        </svg>

                        <p class="text-sm font-semibold">
                            {{ session('status') }}
                        </p>
                    </div>
                </div>
            @endif

            @if ($errors->has('branding'))
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-900 dark:border-red-900 dark:bg-red-950/50 dark:text-red-200">
                    <p class="text-sm font-semibold">
                        {{ $errors->first('branding') }}
                    </p>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('organization-branding.update') }}"
                enctype="multipart/form-data"
            >
                @csrf
                @method('PUT')

                <input
                    type="hidden"
                    name="remove_logo"
                    :value="removeLogo ? 1 : 0"
                >

                <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_380px]">
                    <div class="space-y-8">
                        <!-- Organization identity -->
                        <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800 sm:px-8">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                                    Organization identity
                                </h2>

                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Update the organization name, logo and contact information.
                                </p>
                            </div>

                            <div class="space-y-7 p-6 sm:p-8">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        Organization logo
                                    </label>

                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        Upload a PNG, JPG or WebP image. Maximum size: 2 MB.
                                    </p>

                                    <div class="mt-4 flex flex-col gap-5 sm:flex-row sm:items-center">
                                        <div class="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                                            <template x-if="logoPreview">
                                                <img
                                                    :src="logoPreview"
                                                    alt="Organization logo preview"
                                                    class="h-full w-full object-cover"
                                                >
                                            </template>

                                            <template x-if="! logoPreview">
                                                <div class="flex flex-col items-center text-gray-400">
                                                    <svg
                                                        class="h-9 w-9"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                        aria-hidden="true"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="1.6"
                                                            d="M2.25 15.75 7.5 10.5l4.5 4.5 3-3 6.75 6.75M3.75 4.5h16.5A1.5 1.5 0 0 1 21.75 6v12a1.5 1.5 0 0 1-1.5 1.5H3.75A1.5 1.5 0 0 1 2.25 18V6a1.5 1.5 0 0 1 1.5-1.5Zm11.25 3.75h.008v.008H15V8.25Z"
                                                        />
                                                    </svg>

                                                    <span class="mt-2 text-xs font-semibold">
                                                        No logo
                                                    </span>
                                                </div>
                                            </template>
                                        </div>

                                        <div class="flex flex-wrap gap-3">
                                            <label class="inline-flex cursor-pointer items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-700 focus-within:ring-4 focus-within:ring-indigo-200 dark:focus-within:ring-indigo-900">
                                                Choose logo

                                                <input
                                                    x-ref="logoInput"
                                                    type="file"
                                                    name="logo"
                                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                                    class="sr-only"
                                                    @change="previewLogo($event)"
                                                >
                                            </label>

                                            <button
                                                type="button"
                                                x-show="logoPreview"
                                                x-cloak
                                                @click="clearLogo"
                                                class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-bold text-red-700 transition hover:bg-red-50 dark:border-red-900 dark:bg-gray-900 dark:text-red-300 dark:hover:bg-red-950/40"
                                            >
                                                Remove logo
                                            </button>
                                        </div>
                                    </div>

                                    @error('logo')
                                        <p class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400">
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div class="grid gap-6 md:grid-cols-2">
                                    <div class="md:col-span-2">
                                        <label
                                            for="name"
                                            class="block text-sm font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            Organization name
                                        </label>

                                        <input
                                            id="name"
                                            type="text"
                                            name="name"
                                            value="{{ old('name', $organization->name) }}"
                                            required
                                            autocomplete="organization"
                                            class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        >

                                        @error('name')
                                            <p class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            for="email"
                                            class="block text-sm font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            General email
                                        </label>

                                        <input
                                            id="email"
                                            type="email"
                                            name="email"
                                            value="{{ old('email', $organization->email) }}"
                                            autocomplete="email"
                                            class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        >

                                        @error('email')
                                            <p class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            for="support_email"
                                            class="block text-sm font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            Support email
                                        </label>

                                        <input
                                            id="support_email"
                                            type="email"
                                            name="support_email"
                                            value="{{ old('support_email', $organization->support_email) }}"
                                            autocomplete="email"
                                            class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        >

                                        @error('support_email')
                                            <p class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            for="phone"
                                            class="block text-sm font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            Phone number
                                        </label>

                                        <input
                                            id="phone"
                                            type="text"
                                            name="phone"
                                            value="{{ old('phone', $organization->phone) }}"
                                            autocomplete="tel"
                                            class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        >

                                        @error('phone')
                                            <p class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            for="website"
                                            class="block text-sm font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            Website
                                        </label>

                                        <input
                                            id="website"
                                            type="url"
                                            name="website"
                                            value="{{ old('website', $organization->website) }}"
                                            placeholder="https://example.com"
                                            autocomplete="url"
                                            class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        >

                                        @error('website')
                                            <p class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div class="md:col-span-2">
                                        <label
                                            for="address"
                                            class="block text-sm font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            Address
                                        </label>

                                        <textarea
                                            id="address"
                                            name="address"
                                            rows="4"
                                            class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        >{{ old('address', $organization->address) }}</textarea>

                                        @error('address')
                                            <p class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Brand colors -->
                        <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800 sm:px-8">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                                    Brand colors
                                </h2>

                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    These colors will be used on public consent pages and signing stations.
                                </p>
                            </div>

                            <div class="grid gap-6 p-6 sm:p-8 md:grid-cols-3">
                                <div>
                                    <label
                                        for="primary_color"
                                        class="block text-sm font-semibold text-gray-900 dark:text-gray-100"
                                    >
                                        Primary color
                                    </label>

                                    <div class="mt-2 flex items-center gap-3">
                                        <input
                                            id="primary_color_picker"
                                            type="color"
                                            x-model="primaryColor"
                                            class="branding-color-picker h-12 w-14 cursor-pointer rounded-xl border border-gray-300 bg-white"
                                            aria-label="Choose primary color"
                                        >

                                        <input
                                            id="primary_color"
                                            type="text"
                                            name="primary_color"
                                            x-model="primaryColor"
                                            maxlength="7"
                                            required
                                            class="block min-w-0 flex-1 rounded-xl border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        >
                                    </div>

                                    @error('primary_color')
                                        <p class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400">
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div>
                                    <label
                                        for="secondary_color"
                                        class="block text-sm font-semibold text-gray-900 dark:text-gray-100"
                                    >
                                        Secondary color
                                    </label>

                                    <div class="mt-2 flex items-center gap-3">
                                        <input
                                            id="secondary_color_picker"
                                            type="color"
                                            x-model="secondaryColor"
                                            class="branding-color-picker h-12 w-14 cursor-pointer rounded-xl border border-gray-300 bg-white"
                                            aria-label="Choose secondary color"
                                        >

                                        <input
                                            id="secondary_color"
                                            type="text"
                                            name="secondary_color"
                                            x-model="secondaryColor"
                                            maxlength="7"
                                            required
                                            class="block min-w-0 flex-1 rounded-xl border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        >
                                    </div>

                                    @error('secondary_color')
                                        <p class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400">
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                <div>
                                    <label
                                        for="accent_color"
                                        class="block text-sm font-semibold text-gray-900 dark:text-gray-100"
                                    >
                                        Accent color
                                    </label>

                                    <div class="mt-2 flex items-center gap-3">
                                        <input
                                            id="accent_color_picker"
                                            type="color"
                                            x-model="accentColor"
                                            class="branding-color-picker h-12 w-14 cursor-pointer rounded-xl border border-gray-300 bg-white"
                                            aria-label="Choose accent color"
                                        >

                                        <input
                                            id="accent_color"
                                            type="text"
                                            name="accent_color"
                                            x-model="accentColor"
                                            maxlength="7"
                                            required
                                            class="block min-w-0 flex-1 rounded-xl border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        >
                                    </div>

                                    @error('accent_color')
                                        <p class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400">
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>
                        </section>

                        <!-- PDF document colors -->
                        <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800 sm:px-8">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                                            PDF document colors
                                        </h2>

                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            Choose the colors used in newly generated consent record PDFs.
                                        </p>
                                    </div>

                                    <span class="inline-flex w-fit items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                        New PDFs only
                                    </span>
                                </div>
                            </div>

                            <div class="p-6 sm:p-8">
                                <div class="grid gap-6 md:grid-cols-2">
                                    <div>
                                        <label
                                            for="pdf_primary_color"
                                            class="block text-sm font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            PDF primary color
                                        </label>

                                        <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                            Used for headings, section numbers, table headers and the organization mark.
                                        </p>

                                        <div class="mt-3 flex items-center gap-3">
                                            <input
                                                id="pdf_primary_color_picker"
                                                type="color"
                                                x-model="pdfPrimaryColor"
                                                class="branding-color-picker h-12 w-14 cursor-pointer rounded-xl border border-gray-300 bg-white"
                                                aria-label="Choose PDF primary color"
                                            >

                                            <input
                                                id="pdf_primary_color"
                                                type="text"
                                                name="pdf_primary_color"
                                                x-model="pdfPrimaryColor"
                                                maxlength="7"
                                                required
                                                autocomplete="off"
                                                class="block min-w-0 flex-1 rounded-xl border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                            >
                                        </div>

                                        @error('pdf_primary_color')
                                            <p class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label
                                            for="pdf_accent_color"
                                            class="block text-sm font-semibold text-gray-900 dark:text-gray-100"
                                        >
                                            PDF accent color
                                        </label>

                                        <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                            Used for the top rule, document label, highlights and signature border.
                                        </p>

                                        <div class="mt-3 flex items-center gap-3">
                                            <input
                                                id="pdf_accent_color_picker"
                                                type="color"
                                                x-model="pdfAccentColor"
                                                class="branding-color-picker h-12 w-14 cursor-pointer rounded-xl border border-gray-300 bg-white"
                                                aria-label="Choose PDF accent color"
                                            >

                                            <input
                                                id="pdf_accent_color"
                                                type="text"
                                                name="pdf_accent_color"
                                                x-model="pdfAccentColor"
                                                maxlength="7"
                                                required
                                                autocomplete="off"
                                                class="block min-w-0 flex-1 rounded-xl border-gray-300 uppercase shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                            >
                                        </div>

                                        @error('pdf_accent_color')
                                            <p class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/70 dark:bg-amber-950/40 dark:text-amber-200">
                                    Existing signed PDFs are immutable and will keep their original colors. Your selections apply only to PDFs generated after you save these settings.
                                </div>
                            </div>
                        </section>

                        <!-- Footer -->
                        <section class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                            <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800 sm:px-8">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                                    Footer information
                                </h2>

                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Add a short legal, copyright or support message.
                                </p>
                            </div>

                            <div class="p-6 sm:p-8">
                                <label
                                    for="footer_text"
                                    class="block text-sm font-semibold text-gray-900 dark:text-gray-100"
                                >
                                    Footer text
                                </label>

                                <textarea
                                    id="footer_text"
                                    name="footer_text"
                                    rows="4"
                                    maxlength="500"
                                    placeholder="© {{ now()->year }} {{ $organization->name }}. All rights reserved."
                                    class="mt-2 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                >{{ old('footer_text', $organization->footer_text) }}</textarea>

                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Maximum 500 characters.
                                </p>

                                @error('footer_text')
                                    <p class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </section>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200 dark:focus:ring-indigo-900 sm:w-auto"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M16.5 3.75V6a.75.75 0 0 1-.75.75h-7.5A.75.75 0 0 1 7.5 6V3.75m9 0H18A2.25 2.25 0 0 1 20.25 6v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75h1.5m9 0h-9m3 10.5h3"
                                    />
                                </svg>

                                Save branding
                            </button>
                        </div>
                    </div>

                    <!-- Live preview -->
                    <aside class="xl:sticky xl:top-8 xl:self-start">
                        <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                            <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800">
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">
                                    Live preview
                                </p>

                                <h2 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    Public consent page
                                </h2>
                            </div>

                            <div class="bg-gray-100 p-5 dark:bg-gray-950">
                                <div class="overflow-hidden rounded-2xl bg-white shadow-sm dark:bg-gray-900">
                                    <div
                                        class="h-2"
                                        :style="{ backgroundColor: primaryColor }"
                                    ></div>

                                    <div class="p-6">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl"
                                                :style="{
                                                    backgroundColor: primaryColor + '18'
                                                }"
                                            >
                                                <template x-if="logoPreview">
                                                    <img
                                                        :src="logoPreview"
                                                        alt=""
                                                        class="h-full w-full object-cover"
                                                    >
                                                </template>

                                                <template x-if="! logoPreview">
                                                    <span
                                                        class="text-lg font-black"
                                                        :style="{ color: primaryColor }"
                                                    >
                                                        {{ strtoupper(substr($organization->name, 0, 1)) }}
                                                    </span>
                                                </template>
                                            </div>

                                            <div class="min-w-0">
                                                <p class="truncate font-bold text-gray-900 dark:text-white">
                                                    {{ old('name', $organization->name) }}
                                                </p>

                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    Secure digital consent
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-7">
                                            <div
                                                class="inline-flex rounded-full px-3 py-1 text-xs font-bold"
                                                :style="{
                                                    color: secondaryColor,
                                                    backgroundColor: secondaryColor + '18'
                                                }"
                                            >
                                                Consent form
                                            </div>

                                            <h3 class="mt-4 text-xl font-bold text-gray-900 dark:text-white">
                                                Patient Consent
                                            </h3>

                                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">
                                                Please review the information carefully before continuing.
                                            </p>
                                        </div>

                                        <div class="mt-6 space-y-3">
                                            <div class="h-11 rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800"></div>

                                            <div class="h-11 rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800"></div>
                                        </div>

                                        <button
                                            type="button"
                                            class="mt-6 w-full rounded-xl px-4 py-3 text-sm font-bold text-white"
                                            :style="{ backgroundColor: primaryColor }"
                                        >
                                            Continue
                                        </button>

                                        <div class="mt-5 flex items-center justify-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                            <span
                                                class="inline-block h-2.5 w-2.5 rounded-full"
                                                :style="{ backgroundColor: accentColor }"
                                            ></span>

                                            Secure and digitally recorded
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                            <div class="border-b border-gray-200 px-6 py-5 dark:border-gray-800">
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">
                                    Live preview
                                </p>

                                <h2 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                                    Consent record PDF
                                </h2>
                            </div>

                            <div class="bg-gray-100 dark:bg-gray-950">
                                <div class="overflow-hidden rounded-sm bg-white shadow-sm">
                                    <div
                                        class="h-2"
                                        :style="{ backgroundColor: pdfAccentColor }"
                                    ></div>

                                    <div class="p-5">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex min-w-0 items-center gap-3">
                                                <div
                                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded text-sm font-black text-white"
                                                    :style="{ backgroundColor: pdfPrimaryColor }"
                                                >
                                                    {{ strtoupper(substr($organization->name, 0, 1)) }}
                                                </div>

                                                <div class="min-w-0">
                                                    <p
                                                        class="truncate text-sm font-black"
                                                        :style="{ color: pdfPrimaryColor }"
                                                    >
                                                        {{ old('name', $organization->name) }}
                                                    </p>

                                                    <p class="mt-0.5 text-[10px] font-bold uppercase tracking-wider text-gray-400">
                                                        Issuing organization
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="text-right">
                                                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400">
                                                    Consent record
                                                </p>

                                                <p
                                                    class="mt-0.5 text-sm font-black"
                                                    :style="{ color: pdfPrimaryColor }"
                                                >
                                                    #1024
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-7 border-b border-gray-200 pb-5">
                                            <p
                                                class="text-[9px] font-black uppercase tracking-[0.16em]"
                                                :style="{ color: pdfAccentColor }"
                                            >
                                                Certificate of electronic consent
                                            </p>

                                            <p class="mt-2 text-xl font-medium leading-tight text-gray-900">
                                                Patient Consent
                                            </p>

                                            <p class="mt-2 text-[10px] leading-5 text-gray-500">
                                                A formal record of the consent presented and electronically signed.
                                            </p>
                                        </div>

                                        <div
                                            class="mt-5 rounded-sm border border-gray-200 border-l-4 bg-gray-50 p-3"
                                            :style="{ borderLeftColor: pdfAccentColor }"
                                        >
                                            <div class="grid grid-cols-3 gap-2">
                                                <div>
                                                    <p class="text-[8px] font-black uppercase tracking-wide text-gray-400">
                                                        Version
                                                    </p>
                                                    <p class="mt-1 text-[10px] font-bold text-gray-700">
                                                        Version 2
                                                    </p>
                                                </div>

                                                <div>
                                                    <p class="text-[8px] font-black uppercase tracking-wide text-gray-400">
                                                        Completed
                                                    </p>
                                                    <p class="mt-1 text-[10px] font-bold text-gray-700">
                                                        27 Jul 2026
                                                    </p>
                                                </div>

                                                <div>
                                                    <p class="text-[8px] font-black uppercase tracking-wide text-gray-400">
                                                        Status
                                                    </p>
                                                    <p class="mt-1 text-[10px] font-bold text-emerald-700">
                                                        Completed
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-5 flex items-center gap-2">
                                            <span
                                                class="flex h-5 w-5 items-center justify-center rounded-full text-[8px] font-black text-white"
                                                :style="{ backgroundColor: pdfPrimaryColor }"
                                            >
                                                1
                                            </span>

                                            <span
                                                class="text-xs font-black"
                                                :style="{ color: pdfPrimaryColor }"
                                            >
                                                Consent presented
                                            </span>
                                        </div>

                                        <div class="mt-3 space-y-2">
                                            <div class="h-2 rounded bg-gray-200"></div>
                                            <div class="h-2 w-11/12 rounded bg-gray-200"></div>
                                            <div class="h-2 w-4/5 rounded bg-gray-200"></div>
                                        </div>

                                        <div
                                            class="mt-6 border-t-2 pt-3"
                                            :style="{ borderTopColor: pdfAccentColor }"
                                        >
                                            <p class="text-[9px] font-bold text-gray-500">
                                                Electronic signature and document verification
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
