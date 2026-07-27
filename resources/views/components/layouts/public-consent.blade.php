@props([
    'title' => 'Consent',
    'organizationName' => null,
    'organization' => null,
])

@php
    $displayName =
        $organization?->name
        ?? $organizationName
        ?? config('app.name');

    $isValidHexColor = static function ($value): bool {
        return is_string($value)
            && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1;
    };

    $primaryColor = $isValidHexColor($organization?->primary_color)
        ? $organization->primary_color
        : '#4f46e5';

    $secondaryColor = $isValidHexColor($organization?->secondary_color)
        ? $organization->secondary_color
        : '#3730a3';

    $accentColor = $isValidHexColor($organization?->accent_color)
        ? $organization->accent_color
        : '#818cf8';

    $logoUrl = null;

    if ($organization?->logo) {
        $logoUrl = Illuminate\Support\Facades\Storage::disk('public')
            ->url($organization->logo);
    }

    $websiteUrl = null;

    if ($organization?->website) {
        $websiteUrl = str_starts_with(
            $organization->website,
            'http://'
        ) || str_starts_with(
            $organization->website,
            'https://'
        )
            ? $organization->website
            : 'https://'.$organization->website;
    }

    $footerText = $organization?->footer_text
        ?: 'This secure page is intended only for the named signer.';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="robots"
        content="noindex, nofollow"
    >

    <title>
        {{ $title }} | {{ $displayName }}
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    <style>
        :root {
            --brand-primary: {{ $primaryColor }};
            --brand-secondary: {{ $secondaryColor }};
            --brand-accent: {{ $accentColor }};
        }

        .brand-text {
            color: var(--brand-primary);
        }

        .brand-background {
            background-color: var(--brand-primary);
        }

        .brand-border {
            border-color: var(--brand-primary);
        }

        /*
         * Apply organization branding to the existing public-consent
         * Tailwind classes without changing the form logic.
         */
        .text-indigo-600 {
            color: var(--brand-primary) !important;
        }

        .text-indigo-700 {
            color: var(--brand-secondary) !important;
        }

        .bg-indigo-50 {
            background-color: color-mix(
                in srgb,
                var(--brand-primary) 8%,
                white
            ) !important;
        }

        .bg-indigo-100 {
            background-color: color-mix(
                in srgb,
                var(--brand-primary) 16%,
                white
            ) !important;
        }

        .bg-indigo-600 {
            background-color: var(--brand-primary) !important;
        }

        .bg-indigo-700 {
            background-color: var(--brand-secondary) !important;
        }

        .hover\:bg-indigo-700:hover {
            background-color: var(--brand-secondary) !important;
        }

        .border-indigo-300 {
            border-color: var(--brand-accent) !important;
        }

        .focus\:border-indigo-500:focus {
            border-color: var(--brand-primary) !important;
        }

        .focus\:ring-indigo-500:focus {
            --tw-ring-color: var(--brand-primary) !important;
        }

        .focus\:ring-indigo-600:focus {
            --tw-ring-color: var(--brand-primary) !important;
        }

        input[type="checkbox"]:checked,
        input[type="radio"]:checked {
            background-color: var(--brand-primary) !important;
            border-color: var(--brand-primary) !important;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-100 text-gray-900">
    <header
        class="border-b bg-white"
        style="border-color: {{ $accentColor }};"
    >
        <div class="mx-auto max-w-4xl px-4 py-5 sm:px-6">
            <div class="flex items-center gap-4">
                @if ($logoUrl)
                    <div class="shrink-0">
                        <img
                            src="{{ $logoUrl }}"
                            alt="{{ $displayName }} logo"
                            class="h-14 w-auto max-w-40 object-contain"
                        >
                    </div>
                @endif

                <div class="min-w-0">
                    <p
                        class="text-sm font-semibold"
                        style="color: {{ $primaryColor }};"
                    >
                        Secure Consent
                    </p>

                    <h1 class="mt-1 truncate text-xl font-bold text-gray-900">
                        {{ $displayName }}
                    </h1>
                </div>
            </div>
        </div>

        <div
            class="h-1 w-full"
            style="background: linear-gradient(
                90deg,
                {{ $primaryColor }},
                {{ $accentColor }},
                {{ $secondaryColor }}
            );"
        ></div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
        {{ $slot }}
    </main>

    <footer class="border-t border-gray-200 bg-white">
        <div class="mx-auto max-w-4xl px-4 py-6 text-center sm:px-6">
            <p class="text-sm text-gray-500">
                {{ $footerText }}
            </p>

            @if (
                $organization?->support_email
                || $organization?->phone
                || $websiteUrl
            )
                <div class="mt-3 flex flex-wrap justify-center gap-x-4 gap-y-2 text-xs text-gray-500">
                    @if ($organization?->support_email)
                        <a
                            href="mailto:{{ $organization->support_email }}"
                            class="font-medium hover:underline"
                            style="color: {{ $primaryColor }};"
                        >
                            {{ $organization->support_email }}
                        </a>
                    @endif

                    @if ($organization?->phone)
                        <a
                            href="tel:{{ preg_replace('/[^0-9+]/', '', $organization->phone) }}"
                            class="font-medium hover:underline"
                            style="color: {{ $primaryColor }};"
                        >
                            {{ $organization->phone }}
                        </a>
                    @endif

                    @if ($websiteUrl)
                        <a
                            href="{{ $websiteUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="font-medium hover:underline"
                            style="color: {{ $primaryColor }};"
                        >
                            Visit website
                        </a>
                    @endif
                </div>
            @endif

            @if ($organization?->address)
                <p class="mt-3 text-xs text-gray-400">
                    {{ $organization->address }}
                </p>
            @endif

            <p class="mt-3 text-xs text-gray-400">
                Secure consent record powered by {{ config('app.name') }}
            </p>
        </div>
    </footer>
</body>
</html>
