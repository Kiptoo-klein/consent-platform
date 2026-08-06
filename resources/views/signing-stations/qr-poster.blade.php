@php
    $colorPattern = '/^#[0-9A-Fa-f]{6}$/';

    $configuredPrimary =
        $organization?->pdf_primary_color
        ?: $organization?->primary_color;

    $configuredAccent =
        $organization?->pdf_accent_color
        ?: $organization?->accent_color;

    $primaryColor =
        is_string($configuredPrimary)
        && preg_match($colorPattern, $configuredPrimary)
            ? strtoupper($configuredPrimary)
            : '#312E81';

    $accentColor =
        is_string($configuredAccent)
        && preg_match($colorPattern, $configuredAccent)
            ? strtoupper($configuredAccent)
            : '#4F46E5';

    /*
     * Embed the organization logo directly because Dompdf may not
     * reliably load browser-facing storage URLs.
     */
    $organizationLogoDataUri = null;
    $organizationLogoPath = $organization?->logo;

    if (filled($organizationLogoPath)) {
        try {
            $brandingDiskName =
                (string) config(
                    'organization-branding.disk',
                    'public'
                );

            if ($brandingDiskName === '') {
                $brandingDiskName = 'public';
            }

            $brandingDisk =
                \Illuminate\Support\Facades\Storage::disk(
                    $brandingDiskName
                );

            if ($brandingDisk->exists($organizationLogoPath)) {
                $logoBytes =
                    $brandingDisk->get(
                        $organizationLogoPath
                    );

                $logoExtension =
                    strtolower(
                        pathinfo(
                            $organizationLogoPath,
                            PATHINFO_EXTENSION
                        )
                    );

                $logoMime = match ($logoExtension) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'webp' => 'image/webp',
                    default => null,
                };

                /*
                 * Convert WebP to PNG where possible because PNG
                 * renders more consistently in Dompdf.
                 */
                if (
                    $logoMime === 'image/webp'
                    && function_exists(
                        'imagecreatefromstring'
                    )
                ) {
                    $logoImage =
                        @imagecreatefromstring(
                            $logoBytes
                        );

                    if ($logoImage !== false) {
                        ob_start();

                        imagepng($logoImage);

                        $convertedLogoBytes =
                            ob_get_clean();

                        imagedestroy($logoImage);

                        if (
                            is_string($convertedLogoBytes)
                            && $convertedLogoBytes !== ''
                        ) {
                            $logoBytes =
                                $convertedLogoBytes;

                            $logoMime =
                                'image/png';
                        }
                    }
                }

                if (
                    is_string($logoBytes)
                    && $logoBytes !== ''
                    && is_string($logoMime)
                ) {
                    $organizationLogoDataUri =
                        'data:'
                        .$logoMime
                        .';base64,'
                        .base64_encode($logoBytes);
                }
            }
        } catch (\Throwable) {
            $organizationLogoDataUri = null;
        }
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        {{ $signingStation->name }} – Scan to Sign
    </title>

    <style>
        @page {
            margin: 11mm 13mm 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            text-align: center;
        }

        .poster {
            width: 100%;
        }

        .header {
            width: 100%;
            padding-bottom: 11px;
            border-bottom: 3px solid {{ $accentColor }};
            border-collapse: collapse;
        }

        .header td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 34%;
            text-align: left;
        }

        .identity-cell {
            width: 66%;
            text-align: right;
        }

        .logo {
            display: block;
            max-width: 170px;
            max-height: 54px;
            width: auto;
            height: auto;
        }

        .organization-name {
            margin: 0;
            color: {{ $primaryColor }};
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 1.3px;
            text-transform: uppercase;
        }

        .document-type {
            margin-top: 5px;
            color: #6b7280;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .hero {
            padding-top: 16px;
        }

        h1 {
            margin: 0;
            color: {{ $primaryColor }};
            font-size: 30px;
            line-height: 1.15;
        }

        .intro {
            width: 84%;
            margin: 9px auto 0;
            color: #4b5563;
            font-size: 13px;
            line-height: 1.55;
        }

        .consent-name {
            display: inline-block;
            margin-top: 12px;
            padding: 7px 16px;
            border-radius: 999px;
            background: #f3f4f6;
            color: #1f2937;
            font-size: 13px;
            font-weight: bold;
        }

        .validity {
            width: 78%;
            margin: 12px auto 0;
            padding: 9px 15px;
            border: 1px solid #6ee7b7;
            border-radius: 10px;
            background: #ecfdf5;
            color: #065f46;
            font-size: 11px;
            font-weight: bold;
        }

        .qr-wrapper {
            width: 292px;
            height: 292px;
            margin: 15px auto 10px;
            padding: 13px;
            border: 2px solid #d1d5db;
            border-radius: 18px;
            background: #ffffff;
        }

        .qr-wrapper img {
            width: 262px;
            height: 262px;
        }

        .scan-label {
            margin: 0;
            color: {{ $accentColor }};
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1.1px;
            text-transform: uppercase;
        }

        .steps-heading {
            margin: 13px 0 7px;
            color: {{ $primaryColor }};
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .steps {
            width: 100%;
            border-collapse: separate;
            border-spacing: 7px 0;
            table-layout: fixed;
        }

        .step {
            width: 33.333%;
            padding: 10px 8px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #f9fafb;
            vertical-align: top;
        }

        .step-number {
            display: inline-block;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background: {{ $accentColor }};
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            line-height: 25px;
        }

        .step-title {
            margin-top: 6px;
            color: #111827;
            font-size: 11px;
            font-weight: bold;
        }

        .step-description {
            margin-top: 4px;
            color: #6b7280;
            font-size: 8.5px;
            line-height: 1.4;
        }

        .trust-row {
            width: 84%;
            margin: 11px auto 0;
            border-collapse: collapse;
        }

        .trust-row td {
            width: 33.333%;
            color: #4b5563;
            font-size: 8.5px;
            font-weight: bold;
            text-align: center;
        }

        .trust-dot {
            color: {{ $accentColor }};
        }

        .fallback {
            width: 90%;
            margin: 10px auto 0;
            color: #6b7280;
            font-size: 8px;
            line-height: 1.4;
            word-break: break-all;
        }

        .footer {
            margin-top: 13px;
            padding: 10px 14px;
            border-top: 1px solid #d1d5db;
            background: #f9fafb;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-brand {
            width: 65%;
            text-align: left;
            vertical-align: middle;
        }

        .footer-site {
            width: 35%;
            text-align: right;
            vertical-align: middle;
        }

        .powered-by {
            color: {{ $primaryColor }};
            font-size: 11px;
            font-weight: bold;
        }

        .advert-copy {
            margin-top: 3px;
            color: #6b7280;
            font-size: 8px;
            line-height: 1.4;
        }

        .site-address {
            color: {{ $accentColor }};
            font-size: 11px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="poster">
        <table class="header" role="presentation">
            <tr>
                <td class="logo-cell">
                    @if ($organizationLogoDataUri)
                        <img
                            class="logo"
                            src="{{ $organizationLogoDataUri }}"
                            alt="{{ $organization?->name ?? 'Organization' }} logo"
                        >
                    @endif
                </td>

                <td class="identity-cell">
                    <p class="organization-name">
                        {{
                            $organization?->name
                            ?? config('app.name')
                        }}
                    </p>

                    <p class="document-type">
                        Secure digital consent
                    </p>
                </td>
            </tr>
        </table>

        <div class="hero">
            <h1>Scan to Review and Sign</h1>

            <p class="intro">
                Scan the QR code using your phone camera, review
                the consent document and submit your electronic
                signature securely.
            </p>

            <div class="consent-name">
                {{
                    $signingStation
                        ->consentTemplate
                        ?->title
                    ?? $signingStation->name
                }}
            </div>

            <div class="validity">
                QR signing available until
                {{
                    $qrExpiresAt->format(
                        'M d, Y H:i T'
                    )
                }}
            </div>

            <div class="qr-wrapper">
                <img
                    src="{{ $qrDataUri }}"
                    alt="Scan-to-sign QR code"
                >
            </div>

            <p class="scan-label">
                Point your phone camera at the code
            </p>
        </div>

        <p class="steps-heading">
            Complete your consent in three steps
        </p>

        <table class="steps" role="presentation">
            <tr>
                <td class="step">
                    <span class="step-number">1</span>

                    <div class="step-title">
                        Scan
                    </div>

                    <div class="step-description">
                        Open your phone camera and scan the QR code.
                    </div>
                </td>

                <td class="step">
                    <span class="step-number">2</span>

                    <div class="step-title">
                        Review
                    </div>

                    <div class="step-description">
                        Read the document and enter your details.
                    </div>
                </td>

                <td class="step">
                    <span class="step-number">3</span>

                    <div class="step-title">
                        Sign
                    </div>

                    <div class="step-description">
                        Add your signature and submit securely.
                    </div>
                </td>
            </tr>
        </table>

        <table class="trust-row" role="presentation">
            <tr>
                <td>
                    <span class="trust-dot">●</span>
                    Private
                </td>

                <td>
                    <span class="trust-dot">●</span>
                    Secure
                </td>

                <td>
                    <span class="trust-dot">●</span>
                    Individual record
                </td>
            </tr>
        </table>

        <p class="fallback">
            Cannot scan? Open:
            {{ $scanUrl }}
        </p>

        <div class="footer">
            <table class="footer-table" role="presentation">
                <tr>
                    <td class="footer-brand">
                        <div class="powered-by">
                            Powered by eConsent
                        </div>

                        <div class="advert-copy">
                            Create, manage and sign digital consent
                            forms securely.
                        </div>
                    </td>

                    <td class="footer-site">
                        <div class="site-address">
                            econsent.site
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
