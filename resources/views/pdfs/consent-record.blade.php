<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        Consent Record {{ $consentSession->id }}
    </title>

    @php
        $pdfColorPattern = '/^#[0-9A-Fa-f]{6}$/';

        $configuredPdfPrimaryColor =
            $consentSession->organization->pdf_primary_color
            ?? null;

        $configuredPdfAccentColor =
            $consentSession->organization->pdf_accent_color
            ?? null;

        $pdfPrimaryColor = is_string($configuredPdfPrimaryColor)
            && preg_match($pdfColorPattern, $configuredPdfPrimaryColor)
                ? strtoupper($configuredPdfPrimaryColor)
                : '#17324D';

        $pdfAccentColor = is_string($configuredPdfAccentColor)
            && preg_match($pdfColorPattern, $configuredPdfAccentColor)
                ? strtoupper($configuredPdfAccentColor)
                : '#0F766E';
    @endphp

    <style>
        @page {
            margin: 42px 42px 62px 42px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #243244;
            font-size: 10.5px;
            line-height: 1.62;
            background: #ffffff;
        }

        .top-rule {
            height: 5px;
            background: {{ $pdfAccentColor }};
            margin: -42px -42px 28px -42px;
        }

        .header-table,
        .summary-table,
        .identity-table,
        .signature-table,
        .verification-table,
        .response-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td,
        .summary-table td,
        .identity-table td,
        .signature-table td,
        .verification-table td {
            vertical-align: top;
        }

        .brand-cell {
            width: 62%;
        }

        .brand-mark {
            display: inline-block;
            width: 38px;
            height: 38px;
            line-height: 38px;
            text-align: center;
            background: {{ $pdfPrimaryColor }};
            color: #ffffff;
            font-size: 18px;
            font-weight: bold;
            border-radius: 4px;
            margin-right: 10px;
        }

        .brand-logo-wrap {
            display: inline-block;
            overflow: hidden;
            width: 52px;
            height: 52px;
            margin-right: 10px;
            border-radius: 4px;
            vertical-align: top;
        }

        .brand-logo {
            display: block;
            width: 52px;
            height: 52px;
        }

        .brand-copy {
            display: inline-block;
            vertical-align: top;
            padding-top: 5px;
        }

        .organization-name {
            display: block;
            margin: 0;
            color: {{ $pdfPrimaryColor }};
            font-size: 18px;
            font-weight: bold;
            line-height: 1.2;
        }

        .organization-label {
            display: block;
            margin-top: 4px;
            color: #64748b;
            font-size: 9px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .record-cell {
            width: 38%;
            text-align: right;
        }

        .record-label {
            color: #64748b;
            font-size: 8.5px;
            letter-spacing: 0.7px;
            text-transform: uppercase;
        }

        .record-number {
            margin-top: 2px;
            color: {{ $pdfPrimaryColor }};
            font-size: 15px;
            font-weight: bold;
        }

        .status-badge {
            display: inline-block;
            margin-top: 7px;
            padding: 4px 10px;
            border: 1px solid #9fd4c8;
            border-radius: 12px;
            background: #edf9f6;
            color: #0f6b5f;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .title-block {
            margin-top: 30px;
            padding-bottom: 18px;
            border-bottom: 1px solid #d9e1e8;
        }

        .document-type {
            margin: 0 0 7px 0;
            color: {{ $pdfAccentColor }};
            font-size: 8.5px;
            font-weight: bold;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        .document-title {
            margin: 0;
            color: #152536;
            font-size: 24px;
            font-weight: normal;
            line-height: 1.25;
        }

        .document-subtitle {
            margin-top: 8px;
            color: #64748b;
            font-size: 10px;
        }

        .summary-card {
            margin-top: 22px;
            padding: 15px 16px;
            border: 1px solid #dbe3ea;
            border-left: 4px solid {{ $pdfAccentColor }};
            background: #f8fafc;
        }

        .summary-table td {
            width: 25%;
            padding-right: 10px;
        }

        .meta-label {
            color: #64748b;
            font-size: 7.8px;
            font-weight: bold;
            letter-spacing: 0.65px;
            text-transform: uppercase;
        }

        .meta-value {
            margin-top: 4px;
            color: #1f2f40;
            font-size: 9.7px;
            font-weight: bold;
            line-height: 1.4;
        }

        .section {
            margin-top: 27px;
        }

        .section-heading {
            margin-bottom: 11px;
        }

        .section-number {
            display: inline-table;
            width: 22px;
            height: 22px;
            margin-right: 8px;
            padding: 0;
            border-radius: 11px;
            background: {{ $pdfPrimaryColor }};
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .section-number > span {
            display: table-cell;
            width: 22px;
            height: 22px;
            padding: 0;
            line-height: 1;
            text-align: center;
            vertical-align: middle;
        }

        .section-title {
            display: inline-block;
            color: {{ $pdfPrimaryColor }};
            font-size: 13px;
            font-weight: bold;
            vertical-align: middle;
        }

        .section-note {
            margin: 2px 0 0 32px;
            color: #718096;
            font-size: 8.8px;
        }

        .consent-content {
            padding: 18px 19px;
            border: 1px solid #dbe3ea;
            background: #ffffff;
            color: #25384a;
            font-size: 10.3px;
            line-height: 1.75;
        }

        .identity-table {
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-left: -8px;
            margin-right: 0;
            width: 100%;
        }

        .identity-table td {
            width: 50%;
            padding: 13px 14px;
            border: 1px solid #dbe3ea;
            background: #f8fafc;
        }

        .identity-table .full-width {
            width: 100%;
        }

        .field-label {
            color: #64748b;
            font-size: 7.8px;
            font-weight: bold;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .field-value {
            margin-top: 5px;
            color: #1d2d3d;
            font-size: 10px;
            font-weight: bold;
            word-wrap: break-word;
        }

        .response-table {
            border: 1px solid #dbe3ea;
        }

        .response-table th {
            padding: 9px 11px;
            background: {{ $pdfPrimaryColor }};
            color: #ffffff;
            font-size: 8px;
            letter-spacing: 0.5px;
            text-align: left;
            text-transform: uppercase;
        }

        .response-table th:first-child {
            width: 38%;
        }

        .response-table td {
            padding: 10px 11px;
            border-bottom: 1px solid #e5ebf0;
            vertical-align: top;
            word-wrap: break-word;
        }

        .response-table tr:last-child td {
            border-bottom: 0;
        }

        .response-table td:first-child {
            color: #526579;
            font-weight: bold;
            background: #f8fafc;
        }

        .declaration-box {
            padding: 15px 16px;
            border: 1px solid #cbd8e2;
            background: #f8fafc;
            color: #34485a;
        }

        .declaration-title {
            color: {{ $pdfPrimaryColor }};
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .signature-panel {
            margin-top: 13px;
            padding: 17px;
            border: 1px solid #cbd8e2;
            border-top: 3px solid {{ $pdfAccentColor }};
            background: #ffffff;
        }

        .signature-table td:first-child {
            width: 57%;
            padding-right: 18px;
            border-right: 1px solid #e0e7ed;
        }

        .signature-table td:last-child {
            width: 43%;
            padding-left: 18px;
        }

        .signature-label {
            color: #64748b;
            font-size: 7.8px;
            font-weight: bold;
            letter-spacing: 0.65px;
            text-transform: uppercase;
        }

        .signature-image-wrap {
            min-height: 86px;
            padding-top: 7px;
        }

        .signature-image {
            max-width: 275px;
            max-height: 90px;
        }

        .signature-name {
            margin-top: 5px;
            color: {{ $pdfPrimaryColor }};
            font-size: 11px;
            font-weight: bold;
        }

        .signature-detail {
            margin-top: 10px;
        }

        .signature-detail:first-child {
            margin-top: 0;
        }

        .verification-box {
            margin-top: 28px;
            padding: 14px 15px;
            border: 1px solid #dbe3ea;
            background: #f8fafc;
        }

        .verification-title {
            margin-bottom: 8px;
            color: {{ $pdfPrimaryColor }};
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.55px;
            text-transform: uppercase;
        }

        .verification-table td {
            width: 33.33%;
            padding-right: 12px;
        }

        .verification-statement {
            margin-top: 11px;
            color: #64748b;
            font-size: 8.5px;
            line-height: 1.5;
        }

        .empty {
            color: #718096;
            font-style: italic;
        }

        .page-break-avoid {
            page-break-inside: avoid;
        }

        .footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -40px;
            padding-top: 8px;
            border-top: 1px solid #d9e1e8;
            color: #7a8998;
            font-size: 7.8px;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td:last-child {
            text-align: right;
        }

        .page-number:after {
            content: counter(page);
        }
    </style>
</head>

<body>
    @php
        $organizationName =
            $consentSession->organization->name
            ?? 'Organization';

        $organizationInitial = strtoupper(
            mb_substr($organizationName, 0, 1)
        );

        /*
        |--------------------------------------------------------------------------
        | Embedded organization logo
        |--------------------------------------------------------------------------
        |
        | Dompdf can have difficulty loading browser-facing storage URLs.
        | Read the logo directly from the public disk and embed it as a data
        | URI. If it is unavailable or unsupported, the organization initial
        | remains as the safe fallback.
        |
        */

        $organizationLogoDataUri = null;
        $organizationLogoPath =
            $consentSession->organization?->logo;

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

                    /*
                     * Convert WebP to PNG where GD supports it because PNG
                     * rendering is more reliable across Dompdf installations.
                     */
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

                    /*
                     * Create a square thumbnail before embedding the logo.
                     * This lets portrait and landscape images fill the
                     * dedicated PDF logo frame without distortion.
                     */
                    if (
                        $logoMime !== null
                        && is_string($logoBytes)
                        && $logoBytes !== ''
                        && function_exists('imagecreatefromstring')
                        && function_exists('imagecreatetruecolor')
                    ) {
                        $sourceLogo = @imagecreatefromstring($logoBytes);

                        if ($sourceLogo !== false) {
                            $sourceWidth = imagesx($sourceLogo);
                            $sourceHeight = imagesy($sourceLogo);
                            $cropSize = min($sourceWidth, $sourceHeight);

                            $sourceX = (int) floor(
                                ($sourceWidth - $cropSize) / 2
                            );

                            $sourceY = (int) floor(
                                ($sourceHeight - $cropSize) / 2
                            );

                            $squareLogo = imagecreatetruecolor(160, 160);

                            imagealphablending($squareLogo, false);
                            imagesavealpha($squareLogo, true);

                            $transparent = imagecolorallocatealpha(
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
                            $squareLogoBytes = ob_get_clean();

                            imagedestroy($squareLogo);
                            imagedestroy($sourceLogo);

                            if (
                                is_string($squareLogoBytes)
                                && $squareLogoBytes !== ''
                            ) {
                                $logoBytes = $squareLogoBytes;
                                $logoMime = 'image/png';
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

        $templateTitle =
            $publishedVersion->title
            ?? $consentSession->consentTemplate->title
            ?? 'Consent Form';

        $versionNumber =
            $publishedVersion->version_number
            ?? null;

        $signature =
            $consentSession->signature;

        $statusLabel = ucwords(
            str_replace('_', ' ', $consentSession->status)
        );

        $formatValue = function ($value) {
            if (is_bool($value)) {
                return $value ? 'Yes' : 'No';
            }

            if (is_array($value)) {
                return implode(
                    ', ',
                    array_map(
                        fn ($item) => is_scalar($item)
                            ? (string) $item
                            : json_encode($item),
                        $value
                    )
                );
            }

            if ($value === null || $value === '') {
                return 'Not provided';
            }

            return (string) $value;
        };
    @endphp

    <div class="top-rule"></div>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>
                    Electronic consent record | {{ $organizationName }}
                </td>
                <td>
                    Record #{{ $consentSession->id }} | Page
                    <span class="page-number"></span>
                </td>
            </tr>
        </table>
    </div>

    <table class="header-table">
        <tr>
            <td class="brand-cell">
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
            </td>

            <td class="record-cell">
                <div class="record-label">
                    Consent record
                </div>

                <div class="record-number">
                    #{{ $consentSession->id }}
                </div>

                <span class="status-badge">
                    {{ $statusLabel }}
                </span>
            </td>
        </tr>
    </table>

    <div class="title-block">
        <div class="document-type">
            Certificate of electronic consent
        </div>

        <h1 class="document-title">
            {{ $templateTitle }}
        </h1>

        <div class="document-subtitle">
            A formal record of the consent presented, responses supplied,
            and electronic signature submitted by the signer.
        </div>
    </div>

    <div class="summary-card page-break-avoid">
        <table class="summary-table">
            <tr>
                <td>
                    <div class="meta-label">Document version</div>
                    <div class="meta-value">
                        {{ $versionNumber !== null
                            ? 'Version '.$versionNumber
                            : 'Not available' }}
                    </div>
                </td>

                <td>
                    <div class="meta-label">Started</div>
                    <div class="meta-value">
                        {{ \App\Support\DisplayTime::format($consentSession->started_at, 'j M Y, g:i A', '')
                            ?: 'Not recorded' }}
                    </div>
                </td>

                <td>
                    <div class="meta-label">Completed</div>
                    <div class="meta-value">
                        {{ \App\Support\DisplayTime::format($consentSession->completed_at, 'j M Y, g:i A', '')
                            ?: 'Not recorded' }}
                    </div>
                </td>

                <td>
                    <div class="meta-label">Signing channel</div>
                    <div class="meta-value">
                        {{ $consentSession->signingStation->name
                            ?? 'Direct signing link' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-heading">
            <span class="section-number">
                <span>1</span>
            </span>
            <span class="section-title">Consent presented</span>
            <div class="section-note">
                The exact consent text from the published template version
                linked to this record.
            </div>
        </div>

        <div class="consent-content">
            @if (filled($consentText))
                {!! nl2br(e($consentText)) !!}
            @else
                <span class="empty">
                    No consent text was stored with this published version.
                </span>
            @endif
        </div>
    </div>

    <div class="section page-break-avoid">
        <div class="section-heading">
            <span class="section-number">
                <span>2</span>
            </span>
            <span class="section-title">Signer information</span>
            <div class="section-note">
                Identity information supplied for this consent record.
            </div>
        </div>

        <table class="identity-table">
            <tr>
                <td>
                    <div class="field-label">Full name</div>
                    <div class="field-value">
                        {{ $consentSession->signer_name }}
                    </div>
                </td>

                <td>
                    <div class="field-label">Email address</div>
                    <div class="field-value">
                        {{ $consentSession->signer_email ?: 'Not provided' }}
                    </div>
                </td>
            </tr>
        </table>

    </div>

    @if (count($responseEvidence) > 0)
        <div class="section">
            <div class="section-heading">
                <span class="section-number">
                    <span>3</span>
                </span>
                <span class="section-title">Recorded responses</span>
                <div class="section-note">
                    Additional information and acknowledgements supplied by
                    the signer.
                </div>
            </div>

            <table class="response-table">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Recorded response</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($responseEvidence as $evidence)
                        <tr>
                            <td>{{ $evidence['label'] }}</td>
                            <td>{{ $formatValue($evidence['value']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="section page-break-avoid">
        <div class="section-heading">
            <span class="section-number">
                <span>{{ count($responseEvidence) > 0 ? '4' : '3' }}</span>
            </span>
            <span class="section-title">Consent declaration and signature</span>
            <div class="section-note">
                Evidence of the signer's electronic confirmation.
            </div>
        </div>

        <div class="declaration-box">
            <div class="declaration-title">
                Signer declaration
            </div>

            By submitting the electronic signature shown below, the signer
            confirmed that they had reviewed the consent presented in this
            document and intended to provide their consent electronically.
        </div>

        <div class="signature-panel">
            @if ($signature && filled($signature->signature_data))
                <table class="signature-table">
                    <tr>
                        <td>
                            <div class="signature-label">
                                Electronic signature
                            </div>

                            <div class="signature-image-wrap">
                                <img
                                    src="{{ $signature->signature_data }}"
                                    alt="Electronic signature"
                                    class="signature-image"
                                >
                            </div>

                            <div class="signature-name">
                                {{ $signature->signer_name }}
                            </div>
                        </td>

                        <td>
                            <div class="signature-detail">
                                <div class="field-label">Signed by</div>
                                <div class="field-value">
                                    {{ $signature->signer_name }}
                                </div>
                            </div>

                            <div class="signature-detail">
                                <div class="field-label">Signed at</div>
                                <div class="field-value">
                                    {{ \App\Support\DisplayTime::format($signature->signed_at, 'j F Y, g:i A', '')
                                        ?: 'Not recorded' }}
                                </div>
                            </div>

                            <div class="signature-detail">
                                <div class="field-label">Signature type</div>
                                <div class="field-value">
                                    {{ ucfirst($signature->signature_type) }}
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            @else
                <div class="empty">
                    No valid electronic signature was found for this record.
                </div>
            @endif
        </div>
    </div>

    <div class="verification-box page-break-avoid">
        <div class="verification-title">
            Document verification
        </div>

        <table class="verification-table">
            <tr>
                <td>
                    <div class="meta-label">Record identifier</div>
                    <div class="meta-value">
                        #{{ $consentSession->id }}
                    </div>
                </td>

                <td>
                    <div class="meta-label">Template</div>
                    <div class="meta-value">
                        {{ $templateTitle }}
                    </div>
                </td>

                <td>
                    <div class="meta-label">Generated from</div>
                    <div class="meta-value">
                        Published version
                        {{ $versionNumber !== null
                            ? $versionNumber
                            : 'not available' }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="verification-statement">
            This document is the stored PDF generated for this completed
            consent record. It reflects the exact published consent version,
            signer details, recorded responses, and signature associated with
            the record at the time of completion. Technical audit information
            is retained separately within the protected application audit trail.
        </div>
    </div>
    <div
        class="page-break-avoid"
        style="
            margin-top:24px;
            padding:14px 18px;
            border-top:2px solid #4f46e5;
            background:#f5f7ff;
            text-align:center;
            color:#374151;
            font-family:DejaVu Sans, sans-serif;
            font-size:10px;
            line-height:1.5;
        "
    >
        <strong style="color:#312e81;">
            Securely created with eConsent
        </strong>

        <span style="margin:0 5px;">—</span>

        <a
            href="https://econsent.site"
            style="color:#4338ca;text-decoration:none;font-weight:bold;"
        >
            econsent.site
        </a>

        <div style="margin-top:4px;color:#6b7280;font-size:9px;">
            This platform notice is separate from the consent
            declaration and signing evidence above.
        </div>
    </div>
</body>
</html>
