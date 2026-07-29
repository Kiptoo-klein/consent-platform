<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    Edit Organization
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Update organization details, contact information and branding.
                </p>
            </div>

            <a
                href="{{ route(
                    'platform.organizations.show',
                    $organization
                ) }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                Back to Organization
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4">
                    <p class="text-sm font-semibold text-red-800">
                        Please correct the following:
                    </p>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route(
                    'platform.organizations.update',
                    $organization
                ) }}"
                class="space-y-6"
            >
                @csrf
                @method('PUT')

                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Organization Information
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Basic identification and contact details.
                        </p>
                    </div>

                    <div class="mt-6 grid gap-6 sm:grid-cols-2">
                        <div>
                            <label
                                for="name"
                                class="block text-sm font-semibold text-gray-700"
                            >
                                Organization Name
                            </label>

                            <input
                                id="name"
                                name="name"
                                type="text"
                                required
                                maxlength="255"
                                value="{{ old(
                                    'name',
                                    data_get($organization, 'name')
                                ) }}"
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                            >

                            @error('name')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="slug"
                                class="block text-sm font-semibold text-gray-700"
                            >
                                Slug
                            </label>

                            <input
                                id="slug"
                                name="slug"
                                type="text"
                                required
                                maxlength="255"
                                value="{{ old(
                                    'slug',
                                    data_get($organization, 'slug')
                                ) }}"
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                            >

                            @error('slug')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="email"
                                class="block text-sm font-semibold text-gray-700"
                            >
                                Email
                            </label>

                            <input
                                id="email"
                                name="email"
                                type="email"
                                maxlength="255"
                                value="{{ old(
                                    'email',
                                    data_get($organization, 'email')
                                ) }}"
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                            >

                            @error('email')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="phone"
                                class="block text-sm font-semibold text-gray-700"
                            >
                                Phone
                            </label>

                            <input
                                id="phone"
                                name="phone"
                                type="text"
                                maxlength="50"
                                value="{{ old(
                                    'phone',
                                    data_get($organization, 'phone')
                                ) }}"
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                            >

                            @error('phone')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="website"
                                class="block text-sm font-semibold text-gray-700"
                            >
                                Website
                            </label>

                            <input
                                id="website"
                                name="website"
                                type="url"
                                maxlength="255"
                                placeholder="https://example.com"
                                value="{{ old(
                                    'website',
                                    data_get($organization, 'website')
                                ) }}"
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                            >

                            @error('website')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="support_email"
                                class="block text-sm font-semibold text-gray-700"
                            >
                                Support Email
                            </label>

                            <input
                                id="support_email"
                                name="support_email"
                                type="email"
                                maxlength="255"
                                value="{{ old(
                                    'support_email',
                                    data_get($organization, 'support_email')
                                ) }}"
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                            >

                            @error('support_email')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label
                                for="address"
                                class="block text-sm font-semibold text-gray-700"
                            >
                                Address
                            </label>

                            <textarea
                                id="address"
                                name="address"
                                rows="4"
                                maxlength="2000"
                                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                            >{{ old(
                                'address',
                                data_get($organization, 'address')
                            ) }}</textarea>

                            @error('address')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Branding
                        </h2>

                        <p class="mt-1 text-sm text-gray-500">
                            Enter colors using six-digit hexadecimal values.
                        </p>
                    </div>

                    <div class="mt-6 grid gap-6 sm:grid-cols-3">
                        @foreach ([
                            'primary_color' => [
                                'Primary Color',
                                '#0F766E',
                            ],
                            'secondary_color' => [
                                'Secondary Color',
                                '#115E59',
                            ],
                            'accent_color' => [
                                'Accent Color',
                                '#D4A72C',
                            ],
                        ] as $field => [$label, $fallback])
                            <div>
                                <label
                                    for="{{ $field }}"
                                    class="block text-sm font-semibold text-gray-700"
                                >
                                    {{ $label }}
                                </label>

                                <input
                                    id="{{ $field }}"
                                    name="{{ $field }}"
                                    type="text"
                                    maxlength="7"
                                    pattern="^#[0-9A-Fa-f]{6}$"
                                    placeholder="{{ $fallback }}"
                                    value="{{ old(
                                        $field,
                                        data_get(
                                            $organization,
                                            $field,
                                            $fallback
                                        )
                                    ) }}"
                                    class="mt-2 block w-full rounded-lg border-gray-300 font-mono shadow-sm focus:border-teal-700 focus:ring-teal-700"
                                >

                                @error($field)
                                    <p class="mt-2 text-sm text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        <label
                            for="footer_text"
                            class="block text-sm font-semibold text-gray-700"
                        >
                            Footer Text
                        </label>

                        <textarea
                            id="footer_text"
                            name="footer_text"
                            rows="4"
                            maxlength="2000"
                            class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
                        >{{ old(
                            'footer_text',
                            data_get($organization, 'footer_text')
                        ) }}</textarea>

                        @error('footer_text')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a
                        href="{{ route(
                            'platform.organizations.show',
                            $organization
                        ) }}"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg border border-teal-700 bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-800"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
