<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mailSubject }}</title>
</head>
<body style="margin:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    @php
        $organizationName =
            $consentSession->organization?->name
            ?? config('app.name', 'Consent Platform');

        $templateTitle =
            $consentSession->consentTemplate?->title
            ?? 'Consent document';

        $isReminder = in_array(
            $notificationType,
            [
                \App\Models\ConsentNotification::TYPE_MANUAL_REMINDER,
                \App\Models\ConsentNotification::TYPE_AUTOMATIC_REMINDER,
            ],
            true
        );
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="background:#312e81;padding:28px 32px;color:#ffffff;">
                            <div style="font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;opacity:.8;">
                                {{ $organizationName }}
                            </div>

                            <h1 style="margin:10px 0 0;font-size:25px;line-height:1.3;">
                                {{ $isReminder ? 'Consent signature reminder' : 'Consent document ready for review' }}
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 18px;font-size:16px;line-height:1.7;">
                                Hello {{ $consentSession->signer_name }},
                            </p>

                            <p style="margin:0 0 18px;font-size:16px;line-height:1.7;color:#374151;">
                                {{ $introMessage }}
                            </p>

                            <div style="margin:22px 0;padding:18px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;">
                                <div style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#6b7280;">
                                    Consent document
                                </div>

                                <div style="margin-top:7px;font-size:17px;font-weight:700;color:#111827;">
                                    {{ $templateTitle }}
                                </div>

                                @if ($consentSession->expires_at)
                                    <div style="margin-top:10px;font-size:14px;color:#92400e;">
                                        Signing deadline:
                                        {{ $consentSession->expires_at->format('d/m/y H:i') }}
                                        {{ config('app.timezone') }}
                                    </div>
                                @endif
                            </div>

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:26px 0;">
                                <tr>
                                    <td style="border-radius:10px;background:#4f46e5;">
                                        <a href="{{ $signingUrl }}" style="display:inline-block;padding:14px 22px;color:#ffffff;text-decoration:none;font-weight:700;font-size:16px;">
                                            Review and sign consent
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#6b7280;">
                                Or copy this secure link into your browser:
                            </p>

                            <p style="margin:0;padding:12px;background:#f3f4f6;border-radius:8px;font-size:12px;line-height:1.6;word-break:break-all;color:#374151;">
                                {{ $signingUrl }}
                            </p>

                            <p style="margin:24px 0 0;font-size:13px;line-height:1.6;color:#6b7280;">
                                This link provides access to your individual consent record. Do not forward it to anyone else.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 32px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:12px;line-height:1.6;color:#6b7280;">
                            Sent securely by {{ $organizationName }} using {{ config('app.name', 'Consent Platform') }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
