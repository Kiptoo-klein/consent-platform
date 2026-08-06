<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>
        {{ $signingStation->name }} – Scan to Sign
    </title>

    <style>
        @page {
            margin: 25mm 20mm;
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

        .organization {
            margin: 0;
            color: #4f46e5;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        h1 {
            margin: 18px 0 10px;
            font-size: 34px;
            line-height: 1.2;
        }

        .intro {
            width: 85%;
            margin: 0 auto;
            color: #4b5563;
            font-size: 17px;
            line-height: 1.6;
        }

        .document {
            margin-top: 22px;
            color: #1f2937;
            font-size: 18px;
            font-weight: bold;
        }

        .validity {
            width: 80%;
            margin: 18px auto 0;
            padding: 11px 16px;
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            background: #ecfdf5;
            color: #065f46;
            font-size: 13px;
            font-weight: bold;
            line-height: 1.5;
        }

        .qr-wrapper {
            width: 360px;
            height: 360px;
            margin: 28px auto 20px;
            padding: 18px;
            border: 2px solid #e5e7eb;
            border-radius: 20px;
        }

        .qr-wrapper img {
            width: 320px;
            height: 320px;
        }

        .steps {
            width: 86%;
            margin: 25px auto 0;
            border-collapse: collapse;
        }

        .steps td {
            width: 33.333%;
            padding: 12px;
            vertical-align: top;
        }

        .number {
            display: inline-block;
            width: 30px;
            height: 30px;
            padding-top: 5px;
            border-radius: 15px;
            background: #4f46e5;
            color: #ffffff;
            font-weight: bold;
        }

        .step-title {
            margin-top: 8px;
            font-size: 14px;
            font-weight: bold;
        }

        .fallback {
            margin-top: 28px;
            color: #6b7280;
            font-size: 11px;
            line-height: 1.5;
            word-break: break-all;
        }

        .privacy {
            margin-top: 24px;
            padding: 14px 18px;
            border-radius: 12px;
            background: #f3f4f6;
            color: #374151;
            font-size: 12px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <p class="organization">
        {{ $organization?->name ?? config('app.name') }}
    </p>

    <h1>Scan to Review and Sign</h1>

    <p class="intro">
        Use your phone camera to scan the QR code. You will
        review the consent document, enter your details and
        provide your electronic signature securely.
    </p>

    <p class="document">
        {{
            $signingStation->consentTemplate?->title
            ?? $signingStation->name
        }}
    </p>

    <div class="validity">
        Valid until
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

    <table class="steps" role="presentation">
        <tr>
            <td>
                <span class="number">1</span>

                <div class="step-title">
                    Scan
                </div>
            </td>

            <td>
                <span class="number">2</span>

                <div class="step-title">
                    Review and sign
                </div>
            </td>

            <td>
                <span class="number">3</span>

                <div class="step-title">
                    Submit securely
                </div>
            </td>
        </tr>
    </table>

    <p class="fallback">
        Unable to scan? Open:<br>
        {{ $scanUrl }}
    </p>

    <div class="privacy">
        Each signer receives an individual consent record.
        After submission, the record is locked and cannot be
        changed. You may safely close the page when finished.
    </div>
</body>
</html>
