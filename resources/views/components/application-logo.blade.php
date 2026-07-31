@php
    $brandingService =
        app(
            \App\Services\PlatformBrandingSettingsService::class
        );

    $branding =
        $platformBrand
        ?? $brandingService->viewData();

    $logoUrl =
        $branding['logo_url']
        ?? null;

    $platformName =
        $branding['platform_name']
        ?? 'eConsent';

    $shortName =
        $branding['short_name']
        ?? 'eC';

    $markColor =
        $branding['primary_color']
        ?? '#312E81';
@endphp

@if ($logoUrl)
    <img
        data-platform-logo
        src="{{ $logoUrl }}"
        alt="{{ $platformName }} logo"
        {{ $attributes->merge([
            'class' => 'object-contain',
        ]) }}
    >
@else
    <svg
        data-platform-logo-fallback
        viewBox="0 0 100 100"
        xmlns="http://www.w3.org/2000/svg"
        role="img"
        aria-label="{{ $platformName }} logo"
        {{ $attributes }}
    >
        <circle
            cx="50"
            cy="50"
            r="47"
            fill="{{ $markColor }}"
        />

        <text
            x="50"
            y="59"
            fill="#FFFFFF"
            font-family="Arial, Helvetica, sans-serif"
            font-size="{{ mb_strlen($shortName) > 3 ? 26 : 34 }}"
            font-weight="700"
            text-anchor="middle"
        >
            {{ $shortName }}
        </text>
    </svg>
@endif
