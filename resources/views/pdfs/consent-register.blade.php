<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>{{ $title }} - Consent Register</title>

    @php
        $pdfColorPattern = '/^#[0-9A-Fa-f]{6}$/';

        $configuredPdfPrimaryColor =
            $organization->pdf_primary_color
            ?? null;

        $configuredPdfAccentColor =
            $organization->pdf_accent_color
            ?? null;

        $pdfPrimaryColor = is_string($configuredPdfPrimaryColor)
            && preg_match($pdfColorPattern, $configuredPdfPrimaryColor)
                ? strtoupper($configuredPdfPrimaryColor)
                : '#17324D';

        $pdfAccentColor = is_string($configuredPdfAccentColor)
            && preg_match($pdfColorPattern, $configuredPdfAccentColor)
                ? strtoupper($configuredPdfAccentColor)
                : '#0F766E';

        $organizationName =
            $organization->name
            ?? 'Organization';

        $organizationInitial = strtoupper(
            mb_substr($organizationName, 0, 1)
        );

        /*
        |--------------------------------------------------------------------------
        | Embedded organization logo
        |--------------------------------------------------------------------------
        |
        | Use the same PDF-safe organization logo behavior as individual
        | consent records. The storage object is read directly and embedded
        | so Dompdf does not depend on a browser-facing asset URL.
        |
        */

        $organizationLogoDataUri = null;
        $organizationLogoPath =
            $organization?->logo;

        if (filled($organizationLogoPath)) {
            try {
                $logoDisk =
                    \Illuminate\Support\Facades\Storage::disk(
                        (string) config(
                            'organization-branding.disk',
                            'public'
                        )
                    );

                if ($logoDisk->exists($organizationLogoPath)) {
                    $logoBytes = $logoDisk->get(
                        $organizationLogoPath
                    );

                    $logoExtension = strtolower(
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

                    if (
                        $logoMime === 'image/webp'
                        && function_exists('imagecreatefromstring')
                    ) {
                        $logoImage = @imagecreatefromstring(
                            $logoBytes
                        );

                        if ($logoImage !== false) {
                            ob_start();
                            imagepng($logoImage);
                            $convertedLogoBytes = ob_get_clean();
                            imagedestroy($logoImage);

                            if (
                                is_string($convertedLogoBytes)
                                && $convertedLogoBytes !== ''
                            ) {
                                $logoBytes = $convertedLogoBytes;
                                $logoMime = 'image/png';
                            }
                        }
                    }

                    if (
                        $logoMime !== null
                        && is_string($logoBytes)
                        && $logoBytes !== ''
                        && function_exists('imagecreatefromstring')
                        && function_exists('imagecreatetruecolor')
                    ) {
                        $sourceLogo = @imagecreatefromstring(
                            $logoBytes
                        );

                        if ($sourceLogo !== false) {
                            $sourceWidth = imagesx($sourceLogo);
                            $sourceHeight = imagesy($sourceLogo);
                            $cropSize = min(
                                $sourceWidth,
                                $sourceHeight
                            );

                            $sourceX = (int) floor(
                                ($sourceWidth - $cropSize) / 2
                            );

                            $sourceY = (int) floor(
                                ($sourceHeight - $cropSize) / 2
                            );

                            $squareLogo =
                                imagecreatetruecolor(
                                    160,
                                    160
                                );

                            imagealphablending(
                                $squareLogo,
                                false
                            );

                            imagesavealpha(
                                $squareLogo,
                                true
                            );

                            $transparent =
                                imagecolorallocatealpha(
                                    $squareLogo,
                                    255,
                                    255,
                                    255,
                                    127
                                );

                            imagefilledrectangle(
                                $squareLogo,
                                0,
                                0,
                                159,
                                159,
                                $transparent
                            );

                            imagecopyresampled(
                                $squareLogo,
                                $sourceLogo,
                                0,
                                0,
                                $sourceX,
                                $sourceY,
                                160,
                                160,
                                $cropSize,
                                $cropSize
                            );

                            ob_start();
                            imagepng($squareLogo);
                            $squareLogoBytes =
                                ob_get_clean();

                            imagedestroy($squareLogo);
                            imagedestroy($sourceLogo);

                            if (
                                is_string($squareLogoBytes)
                                && $squareLogoBytes !== ''
                            ) {
                                $logoBytes =
                                    $squareLogoBytes;

                                $logoMime =
                                    'image/png';
                            }
                        }
                    }

                    if (
                        $logoMime !== null
                        && is_string($logoBytes)
                        && $logoBytes !== ''
                    ) {
                        $organizationLogoDataUri =
                            'data:'.$logoMime.';base64,'
                            .base64_encode($logoBytes);
                    }
                }
            } catch (\Throwable $exception) {
                $organizationLogoDataUri = null;
            }
        }
    @endphp

    <style>
        @page {
            margin: 28px 30px 44px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #334155;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9px;
            line-height: 1.4;
        }

        .header {
            margin-bottom: 18px;
        }

        .header-table,
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td,
        .meta-table td {
            border: 0;
        }

        .header-main {
            width: 72%;
            padding: 0;
            vertical-align: top;
        }

        .header-side {
            width: 28%;
            padding: 0;
            text-align: right;
            vertical-align: top;
        }

        .brand-row {
            margin-bottom: 8px;
        }

        .brand-mark {
            display: inline-block;
            width: 38px;
            height: 38px;
            margin-right: 9px;
            border-radius: 4px;
            background: {{ $pdfPrimaryColor }};
            color: #ffffff;
            font-size: 18px;
            font-weight: bold;
            line-height: 38px;
            text-align: center;
            vertical-align: top;
        }

        .brand-logo-wrap {
            display: inline-block;
            overflow: hidden;
            width: 44px;
            height: 44px;
            margin-right: 9px;
            border-radius: 4px;
            vertical-align: top;
        }

        .brand-logo {
            display: block;
            width: 44px;
            height: 44px;
        }

        .brand-copy {
            display: inline-block;
            padding-top: 3px;
            vertical-align: top;
        }

        .organization-name {
            display: block;
            color: {{ $pdfPrimaryColor }};
            font-size: 14px;
            font-weight: bold;
            line-height: 1.2;
        }

        .organization-label {
            display: block;
            margin-top: 3px;
            color: #64748b;
            font-size: 7px;
            font-weight: bold;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            color: {{ $pdfPrimaryColor }};
            font-size: 21px;
            line-height: 1.15;
        }

        .subtitle {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 10px;
        }

        .register-badge {
            display: inline-block;
            border: 1px solid {{ $pdfAccentColor }};
            background: #f8fafc;
            padding: 6px 10px;
            color: {{ $pdfAccentColor }};
            font-size: 7px;
            font-weight: bold;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .header-rule {
            margin-top: 14px;
            border-top: 2px solid {{ $pdfAccentColor }};
        }

        .meta-strip {
            margin-top: 10px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .meta-table td {
            width: 33.333%;
            padding: 8px 10px;
            vertical-align: top;
        }

        .meta-table td + td {
            border-left: 1px solid #e2e8f0;
        }

        .meta-label {
            display: block;
            margin-bottom: 2px;
            color: #64748b;
            font-size: 6.5px;
            font-weight: bold;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }

        .meta-value {
            display: block;
            color: #334155;
            font-size: 8px;
        }

        .meta-value strong {
            color: #0f172a;
        }

        .register-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: {{ $tableFontSize }}px;
        }

        .register-table thead {
            display: table-header-group;
        }

        .register-table tr {
            page-break-inside: avoid;
        }

        .register-table th,
        .register-table td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            vertical-align: top;
            overflow-wrap: anywhere;
            word-wrap: break-word;
        }

        .register-table th {
            background: {{ $pdfPrimaryColor }};
            color: #ffffff;
            font-weight: bold;
            text-align: left;
        }

        .register-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .register-table tbody tr:nth-child(odd) td {
            background: #ffffff;
        }

        .empty {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 34px 12px;
            color: #64748b;
            text-align: center;
        }

        .platform-notice {
            margin-top: 24px;
            border-top: 2px solid {{ $pdfAccentColor }};
            background: #f8fafc;
            padding: 12px 16px;
            color: #374151;
            font-size: 8px;
            line-height: 1.5;
            text-align: center;
        }

        .platform-notice strong {
            color: {{ $pdfPrimaryColor }};
        }

        .platform-notice a {
            color: {{ $pdfAccentColor }};
            font-weight: bold;
            text-decoration: none;
        }

        .platform-notice-separator {
            margin: 0 5px;
        }

        .platform-notice-copy {
            margin-top: 3px;
            color: #6b7280;
            font-size: 7px;
        }

        .footer-note {
            position: fixed;
            right: 120px;
            bottom: -27px;
            left: 0;
            color: #64748b;
            font-size: 7px;
        }
    </style>
</head>

<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-main">
                    <div class="brand-row">
                        @if ($organizationLogoDataUri)
                            <span class="brand-logo-wrap">
                                <img
                                    src="{{ $organizationLogoDataUri }}"
                                    alt="{{ $organizationName }} logo"
                                    class="brand-logo"
                                >
                            </span>
                        @else
                            <span class="brand-mark">
                                {{ $organizationInitial }}
                            </span>
                        @endif

                        <span class="brand-copy">
                            <span class="organization-name">
                                {{ $organizationName }}
                            </span>

                            <span class="organization-label">
                                Issuing organization
                            </span>
                        </span>
                    </div>

                    <h1>
                        {{ $title }}
                    </h1>

                    <p class="subtitle">
                        Consent records export
                    </p>
                </td>

                <td class="header-side">
                    <span class="register-badge">
                        Consent Register
                    </span>
                </td>
            </tr>
        </table>

        <div class="header-rule"></div>

        <div class="meta-strip">
            <table class="meta-table">
                <tr>
                    <td>
                        <span class="meta-label">
                            Records
                        </span>

                        <span class="meta-value">
                            <strong>{{ $recordCount }}</strong>
                            {{ $recordCount === 1 ? 'record' : 'records' }}
                        </span>
                    </td>

                    <td>
                        <span class="meta-label">
                            Columns
                        </span>

                        <span class="meta-value">
                            {{ $headings->count() }}
                            {{ $headings->count() === 1 ? 'column' : 'columns' }}
                        </span>
                    </td>

                    <td>
                        <span class="meta-label">
                            Generated
                        </span>

                        <span class="meta-value">
                            {{ $generatedAt->format('d M Y, H:i') }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    @if ($rows->isEmpty())
        <div class="empty">
            No matching consent records.
        </div>
    @else
        <table class="register-table">
            <thead>
                <tr>
                    @foreach ($headings as $heading)
                        <th>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($row as $value)
                            <td>{{ $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div
        class="platform-notice"
    >
        <strong>
            Securely created with eConsent
        </strong>

        <span class="platform-notice-separator">
            —
        </span>

        <a href="https://econsent.site">
            econsent.site
        </a>

        <div class="platform-notice-copy">
            This platform notice is separate from the exported
            consent records above.
        </div>
    </div>

    <div class="footer-note">
        {{ $organizationName }}
        &nbsp;·&nbsp;
        Consent Register
        &nbsp;·&nbsp;
        {{ $generatedAt->format('d M Y') }}
    </div>
</body>
</html>
