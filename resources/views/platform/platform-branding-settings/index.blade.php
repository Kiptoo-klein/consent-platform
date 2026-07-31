<x-app-layout>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-teal-100 text-teal-800">
                    <svg
                        class="h-6 w-6"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 3.75a8.25 8.25 0 1 0 8.25 8.25c0-.69-.56-1.25-1.25-1.25h-1.62a1.88 1.88 0 0 0-1.88 1.88v.24a1.88 1.88 0 0 1-1.88 1.88H12.5a1.88 1.88 0 0 1-1.88-1.88v-.24a1.88 1.88 0 0 0-1.87-1.88H7.12A1.88 1.88 0 0 1 5.25 8.87V8.5"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M8.25 6.75h.01M12 5.25h.01M15.75 6.75h.01"
                        />
                    </svg>
                </div>

                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                        Platform Branding
                    </h1>

                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Manage the central identity used across the platform,
                        authentication pages, emails and invoices.
                    </p>
                </div>
            </div>

            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-800">
                <svg
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M16.5 10.5V7.875a4.5 4.5 0 0 0-9 0V10.5m-.75 0h10.5A1.5 1.5 0 0 1 18.75 12v7.5H5.25V12a1.5 1.5 0 0 1 1.5-1.5Z"
                    />
                </svg>

                Super Admin only
            </span>
        </div>
    </x-slot>

    <div
        x-data="{
            platformName: @js(
                old(
                    'platform_name',
                    $settings['platform_name']
                )
            ),

            shortName: @js(
                old(
                    'short_name',
                    $settings['short_name']
                )
            ),

            tagline: @js(
                old(
                    'tagline',
                    $settings['tagline']
                )
            ),

            description: @js(
                old(
                    'description',
                    $settings['description']
                )
            ),

            primaryColor: @js(
                old(
                    'primary_color',
                    $settings['primary_color']
                )
            ),

            accentColor: @js(
                old(
                    'accent_color',
                    $settings['accent_color']
                )
            ),

            pdfColor: @js(
                old(
                    'pdf_primary_color',
                    $settings['pdf_primary_color']
                )
            ),

            logoPreview: @js(
                $branding['logo_url']
            ),

            faviconPreview: @js(
                $branding['favicon_url']
            ),

            removeLogo: false,
            removeFavicon: false,

            previewFile(event, property, removalProperty) {
                const file =
                    event.target.files?.[0];

                if (! file) {
                    return;
                }

                this[removalProperty] =
                    false;

                const reader =
                    new FileReader();

                reader.onload = (loadEvent) => {
                    this[property] =
                        loadEvent.target.result;
                };

                reader.readAsDataURL(file);
            },
        }"
        class="space-y-6"
        data-platform-branding-settings
    >
        @if (session('success'))
            <div class="flex items-start gap-3 rounded-2xl border border-emerald-300 bg-emerald-50 px-5 py-4 text-emerald-950 shadow-sm">
                <svg
                    class="mt-0.5 h-5 w-5 shrink-0 text-emerald-700"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m4.5 12.75 6 6 9-13.5"
                    />
                </svg>

                <div>
                    <p class="font-bold">
                        Branding updated
                    </p>

                    <p class="mt-1 text-sm font-medium text-emerald-800">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border-2 border-red-300 bg-red-50 px-5 py-4 text-red-950">
                <p class="font-bold">
                    The selected branding section could not be saved.
                </p>

                <ul class="mt-2 list-inside list-disc space-y-1 text-sm font-medium">
                    @foreach ($errors->all() as $message)
                        <li>
                            {{ $message }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5 text-blue-950 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-700 text-white">
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M11.25 6.75h1.5v1.5h-1.5v-1.5Zm0 4.5h1.5v6h-1.5v-6Z"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"
                        />
                    </svg>
                </div>

                <div>
                    <h2 class="font-bold">
                        Independent settings sections
                    </h2>

                    <p class="mt-1 text-sm font-medium leading-6 text-blue-900">
                        Each card saves independently. Uploading a logo will
                        not change the platform name, colors, contact details,
                        or footer.
                    </p>
                </div>
            </div>
        </section>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-6">
                <form
                    method="POST"
                    action="{{ route(
                        'platform.branding-settings.update'
                    ) }}"
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    data-branding-form="identity"
                >
                    @csrf
                    @method('PATCH')

                    <input
                        type="hidden"
                        name="section"
                        value="identity"
                    >

                    <div class="flex flex-col gap-4 border-b border-gray-200 bg-gray-50 px-6 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/60">
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-100 text-sm font-extrabold text-teal-800">
                                01
                            </div>

                            <div>
                                <h2 class="text-lg font-bold text-gray-950 dark:text-white">
                                    Platform identity
                                </h2>

                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Name and messaging used throughout the
                                    central application.
                                </p>
                            </div>
                        </div>

                        @if (
                            session('success_section')
                                === 'identity'
                        )
                            <span class="inline-flex w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                                Saved
                            </span>
                        @endif
                    </div>

                    <div class="grid gap-6 p-6 md:grid-cols-2">
                        <div>
                            <label
                                for="platform_name"
                                class="block text-sm font-bold text-gray-900 dark:text-gray-100"
                            >
                                Platform name
                            </label>

                            <p class="mt-1 text-xs text-gray-500">
                                Navigation, browser titles and invoices.
                            </p>

                            <input
                                id="platform_name"
                                name="platform_name"
                                type="text"
                                maxlength="120"
                                required
                                x-model="platformName"
                                class="mt-3 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                            >
                        </div>

                        <div>
                            <label
                                for="short_name"
                                class="block text-sm font-bold text-gray-900 dark:text-gray-100"
                            >
                                Short name or initials
                            </label>

                            <p class="mt-1 text-xs text-gray-500">
                                Used by the fallback logo.
                            </p>

                            <input
                                id="short_name"
                                name="short_name"
                                type="text"
                                maxlength="10"
                                required
                                x-model="shortName"
                                class="mt-3 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="tagline"
                                class="block text-sm font-bold text-gray-900 dark:text-gray-100"
                            >
                                Tagline
                            </label>

                            <input
                                id="tagline"
                                name="tagline"
                                type="text"
                                maxlength="255"
                                x-model="tagline"
                                class="mt-3 block w-full rounded-xl border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="description"
                                class="block text-sm font-bold text-gray-900 dark:text-gray-100"
                            >
                                Platform description
                            </label>

                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                maxlength="2000"
                                x-model="description"
                                class="mt-3 block w-full resize-y rounded-xl border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                            ></textarea>

                            <div class="mt-2 flex justify-between text-xs text-gray-500">
                                <span>
                                    Used in public metadata and descriptions.
                                </span>

                                <span
                                    x-text="`${description?.length ?? 0}/2000`"
                                ></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-800/60">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-teal-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-teal-800"
                        >
                            Save Identity
                        </button>
                    </div>
                </form>

                <form
                    method="POST"
                    action="{{ route(
                        'platform.branding-settings.update'
                    ) }}"
                    enctype="multipart/form-data"
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    data-branding-form="assets"
                >
                    @csrf
                    @method('PATCH')

                    <div class="flex flex-col gap-4 border-b border-gray-200 bg-gray-50 px-6 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/60">
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-sm font-extrabold text-indigo-800">
                                02
                            </div>

                            <div>
                                <h2 class="text-lg font-bold text-gray-950 dark:text-white">
                                    Logo and browser icon
                                </h2>

                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Upload central platform image assets.
                                </p>
                            </div>
                        </div>

                        @if (
                            session('success_section')
                                === 'assets'
                        )
                            <span class="inline-flex w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                                Saved
                            </span>
                        @endif
                    </div>

                    <div class="grid gap-6 p-6 lg:grid-cols-2">
                        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
                            <div class="flex min-h-44 items-center justify-center border-b border-gray-200 bg-white p-6">
                                <template
                                    x-if="
                                        logoPreview
                                        && ! removeLogo
                                    "
                                >
                                    <img
                                        x-bind:src="logoPreview"
                                        x-bind:alt="`${platformName} logo preview`"
                                        class="max-h-24 max-w-full object-contain"
                                    >
                                </template>

                                <div
                                    x-show="
                                        ! logoPreview
                                        || removeLogo
                                    "
                                    class="flex items-center gap-4"
                                >
                                    <div
                                        class="flex h-16 w-16 items-center justify-center rounded-2xl text-xl font-extrabold text-white shadow-sm"
                                        x-bind:style="'background-color:' + primaryColor"
                                    >
                                        <span
                                            x-text="shortName || 'eC'"
                                        ></span>
                                    </div>

                                    <div>
                                        <p
                                            class="text-xl font-extrabold text-gray-950"
                                            x-text="platformName || 'eConsent'"
                                        ></p>

                                        <p class="mt-1 text-sm text-gray-500">
                                            Fallback platform logo
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-5">
                                <label
                                    for="logo"
                                    class="block text-sm font-bold text-gray-900"
                                >
                                    Platform logo
                                </label>

                                <input
                                    id="logo"
                                    name="logo"
                                    type="file"
                                    accept="image/png,image/jpeg,image/webp"
                                    x-on:change="previewFile(
                                        $event,
                                        'logoPreview',
                                        'removeLogo'
                                    )"
                                    class="mt-3 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:font-bold file:text-indigo-800"
                                >

                                <p class="mt-3 text-xs leading-5 text-gray-500">
                                    PNG, JPEG or WebP, maximum 5 MB.
                                </p>

                                @if ($settings['logo_path'])
                                    <label class="mt-4 flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-bold text-red-800">
                                        <input
                                            name="remove_logo"
                                            type="checkbox"
                                            value="1"
                                            x-model="removeLogo"
                                            class="rounded border-red-300 text-red-600 focus:ring-red-500"
                                        >

                                        Remove current logo
                                    </label>
                                @endif
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
                            <div class="flex min-h-44 items-center justify-center border-b border-gray-200 bg-white p-6">
                                <img
                                    x-show="
                                        faviconPreview
                                        && ! removeFavicon
                                    "
                                    x-bind:src="faviconPreview"
                                    alt="Favicon preview"
                                    class="h-20 w-20 rounded-2xl object-contain shadow-sm"
                                >

                                <div
                                    x-show="
                                        ! faviconPreview
                                        || removeFavicon
                                    "
                                    class="flex h-20 w-20 items-center justify-center rounded-2xl text-xl font-extrabold text-white shadow-sm"
                                    x-bind:style="'background-color:' + primaryColor"
                                >
                                    <span
                                        x-text="shortName || 'eC'"
                                    ></span>
                                </div>
                            </div>

                            <div class="p-5">
                                <label
                                    for="favicon"
                                    class="block text-sm font-bold text-gray-900"
                                >
                                    Browser favicon
                                </label>

                                <input
                                    id="favicon"
                                    name="favicon"
                                    type="file"
                                    accept="image/png"
                                    x-on:change="previewFile(
                                        $event,
                                        'faviconPreview',
                                        'removeFavicon'
                                    )"
                                    class="mt-3 block w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:font-bold file:text-indigo-800"
                                >

                                <p class="mt-3 text-xs leading-5 text-gray-500">
                                    Square PNG, maximum 512 KB.
                                </p>

                                @if ($settings['favicon_path'])
                                    <label class="mt-4 flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-bold text-red-800">
                                        <input
                                            name="remove_favicon"
                                            type="checkbox"
                                            value="1"
                                            x-model="removeFavicon"
                                            class="rounded border-red-300 text-red-600 focus:ring-red-500"
                                        >

                                        Remove current favicon
                                    </label>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-800/60">
                        <div class="flex flex-wrap justify-end gap-3">
                            <button
                                type="submit"
                                name="section"
                                value="logo"
                                class="inline-flex items-center justify-center rounded-xl bg-indigo-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-800"
                            >
                                Save Logo
                            </button>

                            <button
                                type="submit"
                                name="section"
                                value="favicon"
                                class="inline-flex items-center justify-center rounded-xl border border-indigo-300 bg-white px-5 py-2.5 text-sm font-bold text-indigo-800 shadow-sm transition hover:bg-indigo-50"
                            >
                                Save Favicon
                            </button>
                        </div>
                    </div>
                </form>

                <form
                    method="POST"
                    action="{{ route(
                        'platform.branding-settings.update'
                    ) }}"
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    data-branding-form="colors"
                >
                    @csrf
                    @method('PATCH')

                    <input
                        type="hidden"
                        name="section"
                        value="colors"
                    >

                    <div class="flex flex-col gap-4 border-b border-gray-200 bg-gray-50 px-6 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/60">
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-sm font-extrabold text-violet-800">
                                03
                            </div>

                            <div>
                                <h2 class="text-lg font-bold text-gray-950 dark:text-white">
                                    Brand colors
                                </h2>

                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Interface, active-state and invoice colors.
                                </p>
                            </div>
                        </div>

                        @if (
                            session('success_section')
                                === 'colors'
                        )
                            <span class="inline-flex w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                                Saved
                            </span>
                        @endif
                    </div>

                    <div class="grid gap-5 p-6 md:grid-cols-3">
                        @foreach ([
                            [
                                'field' =>
                                    'primary_color',

                                'label' =>
                                    'Primary color',

                                'description' =>
                                    'Navigation and major actions',

                                'model' =>
                                    'primaryColor',
                            ],
                            [
                                'field' =>
                                    'accent_color',

                                'label' =>
                                    'Accent color',

                                'description' =>
                                    'Highlights and active states',

                                'model' =>
                                    'accentColor',
                            ],
                            [
                                'field' =>
                                    'pdf_primary_color',

                                'label' =>
                                    'Invoice PDF color',

                                'description' =>
                                    'Invoice headers and totals',

                                'model' =>
                                    'pdfColor',
                            ],
                        ] as $color)
                            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                                <label
                                    class="relative block h-28 w-full cursor-pointer overflow-hidden"
                                    x-bind:style="'background-color:' + {{ $color['model'] }}"
                                >
                                    <input
                                        type="color"
                                        x-model="{{ $color['model'] }}"
                                        aria-label="{{ $color['label'] }} picker"
                                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                    >

                                    <span class="absolute bottom-3 right-3 inline-flex items-center rounded-lg bg-black/25 px-2.5 py-1 text-xs font-bold text-white backdrop-blur">
                                        Choose color
                                    </span>
                                </label>

                                <div class="p-4">
                                    <label
                                        for="{{ $color['field'] }}"
                                        class="block text-sm font-extrabold text-gray-950"
                                    >
                                        {{ $color['label'] }}
                                    </label>

                                    <p class="mt-1 min-h-8 text-xs leading-5 text-gray-500">
                                        {{ $color['description'] }}
                                    </p>

                                    <input
                                        id="{{ $color['field'] }}"
                                        name="{{ $color['field'] }}"
                                        type="text"
                                        maxlength="7"
                                        required
                                        autocomplete="off"
                                        x-model="{{ $color['model'] }}"
                                        x-on:change="
                                            {{ $color['model'] }}
                                                = {{ $color['model'] }}.toUpperCase()
                                        "
                                        class="mt-3 block w-full rounded-xl border-gray-300 bg-gray-50 font-mono font-bold uppercase shadow-sm focus:border-violet-600 focus:ring-violet-600"
                                    >
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-800/60">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-violet-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-violet-800"
                        >
                            Save Colors
                        </button>
                    </div>
                </form>

                <form
                    method="POST"
                    action="{{ route(
                        'platform.branding-settings.update'
                    ) }}"
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
                    data-branding-form="contact"
                >
                    @csrf
                    @method('PATCH')

                    <input
                        type="hidden"
                        name="section"
                        value="contact"
                    >

                    <div class="flex flex-col gap-4 border-b border-gray-200 bg-gray-50 px-6 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/60">
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-sm font-extrabold text-amber-800">
                                04
                            </div>

                            <div>
                                <h2 class="text-lg font-bold text-gray-950 dark:text-white">
                                    Contact and footer
                                </h2>

                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Official contact information and platform
                                    footer messaging.
                                </p>
                            </div>
                        </div>

                        @if (
                            session('success_section')
                                === 'contact'
                        )
                            <span class="inline-flex w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                                Saved
                            </span>
                        @endif
                    </div>

                    <div class="grid gap-6 p-6 md:grid-cols-2">
                        <div>
                            <label
                                for="support_email"
                                class="block text-sm font-bold text-gray-900"
                            >
                                Support email
                            </label>

                            <input
                                id="support_email"
                                name="support_email"
                                type="email"
                                value="{{ old(
                                    'support_email',
                                    $settings['support_email']
                                ) }}"
                                placeholder="support@example.com"
                                class="mt-3 block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-600 focus:ring-amber-600"
                            >
                        </div>

                        <div>
                            <label
                                for="website_url"
                                class="block text-sm font-bold text-gray-900"
                            >
                                Website URL
                            </label>

                            <input
                                id="website_url"
                                name="website_url"
                                type="url"
                                value="{{ old(
                                    'website_url',
                                    $settings['website_url']
                                ) }}"
                                placeholder="https://example.com"
                                class="mt-3 block w-full rounded-xl border-gray-300 shadow-sm focus:border-amber-600 focus:ring-amber-600"
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="footer_text"
                                class="block text-sm font-bold text-gray-900"
                            >
                                Platform footer text
                            </label>

                            <textarea
                                id="footer_text"
                                name="footer_text"
                                rows="3"
                                maxlength="1000"
                                class="mt-3 block w-full resize-y rounded-xl border-gray-300 shadow-sm focus:border-amber-600 focus:ring-amber-600"
                            >{{ old(
                                'footer_text',
                                $settings['footer_text']
                            ) }}</textarea>
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-800/60">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-700"
                        >
                            Save Contact and Footer
                        </button>
                    </div>
                </form>
            </div>

            <aside
                class="space-y-6 xl:sticky xl:top-8 xl:self-start"
                data-branding-preview-panel
            >
                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="font-bold text-gray-950">
                                    Application preview
                                </h2>

                                <p class="mt-1 text-xs text-gray-500">
                                    Central navigation appearance
                                </p>
                            </div>

                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">
                                Live
                            </span>
                        </div>
                    </div>

                    <div class="p-5">
                        <div class="overflow-hidden rounded-2xl border border-gray-800 bg-gray-950">
                            <div
                                class="h-1.5"
                                x-bind:style="'background-color:' + accentColor"
                            ></div>

                            <div class="p-5">
                                <div class="flex items-center gap-3">
                                    <template
                                        x-if="
                                            logoPreview
                                            && ! removeLogo
                                        "
                                    >
                                        <img
                                            x-bind:src="logoPreview"
                                            x-bind:alt="`${platformName} logo`"
                                            class="h-11 w-11 rounded-xl bg-white object-contain p-1"
                                        >
                                    </template>

                                    <template
                                        x-if="
                                            ! logoPreview
                                            || removeLogo
                                        "
                                    >
                                        <div
                                            class="flex h-11 w-11 items-center justify-center rounded-xl font-extrabold text-white"
                                            x-bind:style="'background-color:' + primaryColor"
                                        >
                                            <span
                                                x-text="shortName || 'eC'"
                                            ></span>
                                        </div>
                                    </template>

                                    <div class="min-w-0">
                                        <p
                                            class="truncate font-bold text-white"
                                            x-text="platformName || 'eConsent'"
                                        ></p>

                                        <p
                                            class="mt-0.5 truncate text-xs text-gray-400"
                                            x-text="tagline || 'Secure digital consent management.'"
                                        ></p>
                                    </div>
                                </div>

                                <div class="mt-5 space-y-2">
                                    <div class="rounded-xl bg-white/5 px-3 py-2.5 text-xs font-semibold text-gray-300">
                                        Platform Dashboard
                                    </div>

                                    <div
                                        class="rounded-xl px-3 py-2.5 text-xs font-bold text-white"
                                        x-bind:style="'background-color:' + primaryColor"
                                    >
                                        Platform Branding
                                    </div>

                                    <div class="rounded-xl bg-white/5 px-3 py-2.5 text-xs font-semibold text-gray-300">
                                        Platform Staff
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                        <h2 class="font-bold text-gray-950">
                            Invoice preview
                        </h2>

                        <p class="mt-1 text-xs text-gray-500">
                            PDF header and total styling
                        </p>
                    </div>

                    <div class="p-5">
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-500">
                                        Subscription invoice
                                    </p>

                                    <p
                                        class="mt-1 font-extrabold text-gray-950"
                                        x-text="platformName || 'eConsent'"
                                    ></p>
                                </div>

                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-xl text-xs font-extrabold text-white"
                                    x-bind:style="'background-color:' + pdfColor"
                                >
                                    <span
                                        x-text="shortName || 'eC'"
                                    ></span>
                                </div>
                            </div>

                            <div
                                class="mt-4 h-1 rounded-full"
                                x-bind:style="'background-color:' + pdfColor"
                            ></div>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div class="rounded-xl border border-gray-200 bg-white p-3">
                                    <p class="text-[9px] font-extrabold uppercase tracking-wide text-gray-400">
                                        Billed to
                                    </p>

                                    <p class="mt-1 text-xs font-bold text-gray-800">
                                        Organization
                                    </p>
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-white p-3">
                                    <p class="text-[9px] font-extrabold uppercase tracking-wide text-gray-400">
                                        Amount due
                                    </p>

                                    <p
                                        class="mt-1 text-xs font-extrabold"
                                        x-bind:style="'color:' + pdfColor"
                                    >
                                        KES 65,000.00
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5 text-blue-950 shadow-sm">
                    <h2 class="font-bold">
                        Settings scope
                    </h2>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4 border-b border-blue-200 pb-3">
                            <dt class="text-blue-800">
                                Platform identity
                            </dt>

                            <dd class="font-bold">
                                Central only
                            </dd>
                        </div>

                        <div class="flex items-center justify-between gap-4 border-b border-blue-200 pb-3">
                            <dt class="text-blue-800">
                                Organization branding
                            </dt>

                            <dd class="font-bold">
                                Unchanged
                            </dd>
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-blue-800">
                                Access level
                            </dt>

                            <dd class="font-bold">
                                Super Admin
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950 shadow-sm">
                    <h2 class="font-bold">
                        Publishing notice
                    </h2>

                    <p class="mt-2 text-sm font-medium leading-6 text-amber-900">
                        Each section is applied immediately after its own save
                        button is used. Other sections remain unchanged.
                    </p>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
