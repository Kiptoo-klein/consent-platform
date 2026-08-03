@php
    $organizationName =
        $consentSession->organization?->name
        ?? $senderDisplayName
        ?? config('app.name');

    $templateTitle =
        $consentSession->consentTemplate?->title
        ?? 'Consent form';

    $recordReference =
        $consentSession->signer_reference
        ?: 'Consent record #'.$consentSession->id;

    $completedLabel = 'Completed';

    if ($consentSession->completed_at) {
        try {
            $completedLabel = \Illuminate\Support\Carbon::parse(
                $consentSession->completed_at
            )->copy()->timezone(config('app.display_timezone'))->format('j F Y, g:i A');
        } catch (\Throwable $exception) {
            $completedLabel = (string) $consentSession->completed_at;
        }
    }

    $emailDescription = trim(
        (string) ($stationEmailDescription ?? '')
    );
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Signed consent copy</title>
</head>
<body style="margin:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:26px 30px;background:#111827;color:#ffffff;">
                            <div style="font-size:13px;text-transform:uppercase;letter-spacing:.08em;opacity:.78;">
                                {{ $senderDisplayName ?? $organizationName }}
                            </div>

                            <h1 style="margin:10px 0 0;font-size:24px;line-height:1.3;">
                                Your signed consent copy
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:30px;">
                            <p style="margin:0 0 16px;line-height:1.65;">
                                Hello {{ $consentSession->signer_name }},
                            </p>

                            <p style="margin:0 0 16px;line-height:1.65;">
                                You are receiving this email because you completed and electronically signed
                                <strong>{{ $templateTitle }}</strong>
                                through {{ $organizationName }}.
                            </p>

                            @if ($emailDescription !== '')
                                <div style="margin:20px 0;padding:16px 18px;background:#eef2ff;border-left:4px solid #4f46e5;border-radius:6px;line-height:1.65;color:#3730a3;">
                                    {!! nl2br(e($emailDescription)) !!}
                                </div>
                            @else
                                <p style="margin:0 0 16px;line-height:1.65;">
                                    The attached PDF is the official copy of the consent you submitted. It contains the completed form and signing evidence for your records.
                                </p>
                            @endif

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;">
                                <tr>
                                    <td style="padding:18px;line-height:1.7;">
                                        <strong>Consent:</strong>
                                        {{ $templateTitle }}<br>

                                        <strong>Signer:</strong>
                                        {{ $consentSession->signer_name }}<br>

                                        <strong>Reference:</strong>
                                        {{ $recordReference }}<br>

                                        <strong>Completed:</strong>
                                        {{ $completedLabel }}
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 16px;line-height:1.65;color:#4b5563;">
                                No further action is required. Keep the attached document in a secure place for future reference.
                            </p>

                            <p style="margin:0;line-height:1.65;color:#4b5563;">
                                Questions about this consent may be sent by replying to this email.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 30px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:12px;line-height:1.6;color:#6b7280;">
                            This transactional message was generated after a consent form was signed. It was not sent as a marketing email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
