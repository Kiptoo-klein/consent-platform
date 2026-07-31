@props([
    'organization' => null,
    'station' => null,
    'title' => null,
])

@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $organizationName = $organization?->name ?? config('app.name');

    $pageTitle = $title
        ?? $station?->name
        ?? 'Signing Station';

    $validHexColor = static function (?string $color, string $fallback): string {
        if (! is_string($color)) {
            return $fallback;
        }

        $color = trim($color);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $color)
            ? $color
            : $fallback;
    };

    $primaryColor = $validHexColor(
        $organization?->primary_color,
        '#4338ca'
    );

    $secondaryColor = $validHexColor(
        $organization?->secondary_color,
        '#0f172a'
    );

    $accentColor = $validHexColor(
        $organization?->accent_color,
        '#06b6d4'
    );

    $logoUrl = null;

    if ($organization?->logo) {
        $logoUrl = Storage::disk('public')->url($organization->logo);
    }

    $websiteUrl = null;

    if ($organization?->website) {
        $websiteUrl = Str::startsWith(
            $organization->website,
            ['http://', 'https://']
        )
            ? $organization->website
            : 'https://' . $organization->website;
    }

    $contactItems = collect([
        [
            'label' => 'Support',
            'value' => $organization?->support_email,
            'href' => $organization?->support_email
                ? 'mailto:' . $organization->support_email
                : null,
        ],
        [
            'label' => 'Phone',
            'value' => $organization?->phone,
            'href' => $organization?->phone
                ? 'tel:' . preg_replace('/\s+/', '', $organization->phone)
                : null,
        ],
        [
            'label' => 'Website',
            'value' => $organization?->website,
            'href' => $websiteUrl,
        ],
    ])->filter(fn (array $item) => filled($item['value']));
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
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <meta
        name="theme-color"
        content="{{ $secondaryColor }}"
    >

    <title>
        {{ $pageTitle }} | {{ $organizationName }}
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --station-primary: {{ $primaryColor }};
            --station-secondary: {{ $secondaryColor }};
            --station-accent: {{ $accentColor }};
        }

        .station-page {
            background:
                radial-gradient(
                    circle at top left,
                    color-mix(
                        in srgb,
                        var(--station-primary) 28%,
                        transparent
                    ),
                    transparent 35%
                ),
                radial-gradient(
                    circle at right center,
                    color-mix(
                        in srgb,
                        var(--station-accent) 18%,
                        transparent
                    ),
                    transparent 32%
                ),
                linear-gradient(
                    145deg,
                    var(--station-secondary),
                    #020617
                );
        }

        .station-brand-panel {
            background:
                radial-gradient(
                    circle at top right,
                    color-mix(
                        in srgb,
                        var(--station-accent) 23%,
                        transparent
                    ),
                    transparent 38%
                ),
                linear-gradient(
                    145deg,
                    var(--station-primary),
                    var(--station-secondary)
                );
        }

        .station-primary-background {
            background-color: var(--station-primary);
        }

        .station-secondary-background {
            background-color: var(--station-secondary);
        }

        .station-accent-background {
            background-color: var(--station-accent);
        }

        .station-primary-text {
            color: var(--station-primary);
        }

        .station-accent-text {
            color: var(--station-accent);
        }

        .station-primary-border {
            border-color: var(--station-primary);
        }

        .station-primary-ring:focus {
            border-color: var(--station-primary) !important;
            box-shadow:
                0 0 0 4px
                color-mix(
                    in srgb,
                    var(--station-primary) 18%,
                    transparent
                ) !important;
        }

        .station-submit-button {
            background-color: var(--station-secondary);
        }

        .station-submit-button:hover {
            background-color: var(--station-primary);
        }

        /*
         * These overrides allow existing signing-station content to inherit
         * organization branding while we keep Tailwind utility classes intact.
         */
        .station-scope .bg-indigo-700,
        .station-scope .hover\:bg-indigo-700:hover {
            background-color: var(--station-primary) !important;
        }

        .station-scope .bg-indigo-800 {
            background-color:
                color-mix(
                    in srgb,
                    var(--station-primary) 78%,
                    var(--station-secondary)
                ) !important;
        }

        .station-scope .from-indigo-700 {
            --tw-gradient-from:
                var(--station-primary)
                var(--tw-gradient-from-position) !important;

            --tw-gradient-to:
                color-mix(
                    in srgb,
                    var(--station-primary) 0%,
                    transparent
                )
                var(--tw-gradient-to-position) !important;
        }

        .station-scope .via-indigo-800 {
            --tw-gradient-to:
                color-mix(
                    in srgb,
                    var(--station-primary) 0%,
                    transparent
                )
                var(--tw-gradient-to-position) !important;

            --tw-gradient-stops:
                var(--tw-gradient-from),
                color-mix(
                    in srgb,
                    var(--station-primary) 72%,
                    var(--station-secondary)
                )
                var(--tw-gradient-via-position),
                var(--tw-gradient-to) !important;
        }

        .station-scope .text-indigo-100 {
            color:
                color-mix(
                    in srgb,
                    var(--station-primary) 15%,
                    white
                ) !important;
        }

        .station-scope .text-indigo-200 {
            color:
                color-mix(
                    in srgb,
                    var(--station-primary) 25%,
                    white
                ) !important;
        }

        .station-scope .text-indigo-600 {
            color: var(--station-primary) !important;
        }

        .station-scope .focus\:border-indigo-600:focus {
            border-color: var(--station-primary) !important;
        }

        .station-scope .focus\:ring-indigo-100:focus,
        .station-scope .focus\:ring-indigo-200:focus,
        .station-scope .focus\:ring-indigo-400:focus {
            --tw-ring-color:
                color-mix(
                    in srgb,
                    var(--station-primary) 25%,
                    transparent
                ) !important;
        }

        .station-scope .bg-cyan-400\/10 {
            background-color:
                color-mix(
                    in srgb,
                    var(--station-accent) 10%,
                    transparent
                ) !important;
        }

        .station-scope .bg-indigo-500\/20 {
            background-color:
                color-mix(
                    in srgb,
                    var(--station-primary) 20%,
                    transparent
                ) !important;
        }

        @media (max-width: 640px) {
            .station-contact-divider {
                display: none;
            }
        }
    </style>
</head>

<body class="min-h-screen text-slate-900 antialiased">
    <div class="station-page relative min-h-screen overflow-hidden">
        <div
            class="pointer-events-none absolute inset-0 overflow-hidden"
            aria-hidden="true"
        >
            <div
                class="absolute -left-24 -top-24 h-80 w-80 rounded-full blur-3xl"
                style="
                    background-color:
                        color-mix(
                            in srgb,
                            var(--station-primary) 22%,
                            transparent
                        );
                "
            ></div>

            <div
                class="absolute right-0 top-1/3 h-96 w-96 rounded-full blur-3xl"
                style="
                    background-color:
                        color-mix(
                            in srgb,
                            var(--station-accent) 13%,
                            transparent
                        );
                "
            ></div>

            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.06),_transparent_42%)]"
            ></div>
        </div>

        <div class="relative flex min-h-screen flex-col">
            <header class="border-b border-white/10 bg-black/20 backdrop-blur-xl">
                <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        @if ($logoUrl)
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-white/15 bg-white p-1.5 shadow-lg shadow-black/20">
                                <img
                                    src="{{ $logoUrl }}"
                                    alt="{{ $organizationName }} logo"
                                    class="h-full w-full object-contain"
                                >
                            </div>
                        @else
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-lg font-bold text-white shadow-lg shadow-black/20"
                                style="background-color: var(--station-primary);"
                            >
                                {{ Str::upper(Str::substr($organizationName, 0, 1)) }}
                            </div>
                        @endif

                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-300">
                                {{ $organizationName }}
                            </p>

                            <h1 class="truncate text-base font-bold text-white sm:text-lg">
                                {{ $station?->name ?? $pageTitle }}
                            </h1>
                        </div>
                    </div>

                    <button
                        id="fullscreen-button"
                        type="button"
                        onclick="toggleFullscreen()"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-sm font-semibold text-white transition duration-200 hover:border-white/20 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/40 focus:ring-offset-2 focus:ring-offset-slate-950 sm:px-4"
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
                                d="M3.75 9V5.25A1.5 1.5 0 0 1 5.25 3.75H9m6 0h3.75a1.5 1.5 0 0 1 1.5 1.5V9m0 6v3.75a1.5 1.5 0 0 1-1.5 1.5H15m-6 0H5.25a1.5 1.5 0 0 1-1.5-1.5V15"
                            />
                        </svg>

                        <span class="hidden sm:inline">
                            Full Screen
                        </span>
                    </button>
                </div>
            </header>

            <main class="station-scope flex flex-1 items-center justify-center px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
                {{ $slot }}
            </main>

            @if (
                $contactItems->isNotEmpty()
                || filled($organization?->address)
                || filled($organization?->footer_text)
            )
                <footer class="border-t border-white/10 bg-black/20 px-4 py-5 text-slate-300 backdrop-blur sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-7xl text-center">
                        @if ($contactItems->isNotEmpty())
                            <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-2 text-xs sm:text-sm">
                                @foreach ($contactItems as $item)
                                    @if ($item['href'])
                                        <a
                                            href="{{ $item['href'] }}"
                                            @if ($item['label'] === 'Website')
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            @endif
                                            class="transition hover:text-white"
                                        >
                                            <span class="font-semibold text-white">
                                                {{ $item['label'] }}:
                                            </span>

                                            {{ $item['value'] }}
                                        </a>
                                    @else
                                        <span>
                                            <span class="font-semibold text-white">
                                                {{ $item['label'] }}:
                                            </span>

                                            {{ $item['value'] }}
                                        </span>
                                    @endif

                                    @unless ($loop->last)
                                        <span
                                            class="station-contact-divider text-white/25"
                                            aria-hidden="true"
                                        >
                                            •
                                        </span>
                                    @endunless
                                @endforeach
                            </div>
                        @endif

                        @if (filled($organization?->address))
                            <p class="mt-2 text-xs leading-5 text-slate-400">
                                {{ $organization->address }}
                            </p>
                        @endif

                        <p class="mt-2 text-xs leading-5 text-slate-400">
                            {{ $organization?->footer_text ?: 'Secure digital consent powered by ' . config('app.name') . '.' }}
                        </p>
                    </div>
                </footer>
            @endif
        </div>
    </div>

    <script>
        function toggleFullscreen() {
            if (! document.fullscreenElement) {
                document.documentElement.requestFullscreen()
                    .catch(() => {
                        // Fullscreen may be unavailable or blocked.
                    });

                return;
            }

            document.exitFullscreen();
        }

        document.addEventListener('fullscreenchange', function () {
            const button = document.getElementById('fullscreen-button');

            if (! button) {
                return;
            }

            const buttonText = button.querySelector('span');

            if (! buttonText) {
                return;
            }

            buttonText.textContent = document.fullscreenElement
                ? 'Exit Full Screen'
                : 'Full Screen';
        });
    </script>

    @include(
        'public-signing-stations.partials.device-lease-heartbeat'
    )

    @stack('scripts')
</body>
</html>
