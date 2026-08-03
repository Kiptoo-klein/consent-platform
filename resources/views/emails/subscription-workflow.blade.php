<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>{{ $notification->subject }}</title>
</head>
<body
    style="margin:0;background:#f3f4f6;padding:24px;font-family:Arial,sans-serif;color:#111827;"
>
    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
    >
        <tr>
            <td align="center">
                <table
                    role="presentation"
                    width="100%"
                    cellspacing="0"
                    cellpadding="0"
                    style="max-width:640px;background:#ffffff;border:1px solid #d1d5db;border-radius:16px;overflow:hidden;"
                >
                    <tr>
                        <td
                            style="background:#0f766e;padding:24px;color:#ffffff;"
                        >
                            <div
                                style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;opacity:.9;"
                            >
                                eConsent Subscription
                            </div>

                            <h1
                                style="margin:10px 0 0;font-size:24px;line-height:1.3;"
                            >
                                {{ $notification->subject }}
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px;">
                            <p
                                style="margin:0;font-size:16px;line-height:1.7;"
                            >
                                {{ $notification->message }}
                            </p>

                            <table
                                role="presentation"
                                width="100%"
                                cellspacing="0"
                                cellpadding="0"
                                style="margin-top:24px;border-collapse:collapse;"
                            >
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px;">
                                        Organization
                                    </td>
                                    <td align="right" style="padding:10px 0;border-bottom:1px solid #e5e7eb;font-size:13px;font-weight:700;">
                                        {{ $notification->organization?->name ?? '—' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px;">
                                        Invoice
                                    </td>
                                    <td align="right" style="padding:10px 0;border-bottom:1px solid #e5e7eb;font-size:13px;font-weight:700;">
                                        {{ $notification->invoice?->invoice_number ?? '—' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:13px;">
                                        Plan
                                    </td>
                                    <td align="right" style="padding:10px 0;border-bottom:1px solid #e5e7eb;font-size:13px;font-weight:700;">
                                        {{ $notification->planRequest?->requestedPlan?->name ?? '—' }}
                                        /
                                        {{ ucfirst($notification->planRequest?->billing_cycle ?? '') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;color:#6b7280;font-size:13px;">
                                        Amount
                                    </td>
                                    <td align="right" style="padding:10px 0;font-size:13px;font-weight:700;">
                                        {{ $notification->invoice?->currency }}
                                        {{ number_format(
                                            (float) ($notification->invoice?->total_amount ?? 0),
                                            2
                                        ) }}
                                    </td>
                                </tr>
                            </table>

                            @if (
                                $notification->planRequest
                                    ?->payment_claim_reference
                            )
                                <div
                                    style="margin-top:22px;padding:16px;border:1px solid #bfdbfe;border-radius:12px;background:#eff6ff;"
                                >
                                    <div
                                        style="font-size:12px;font-weight:700;color:#1e3a8a;text-transform:uppercase;"
                                    >
                                        Payment report
                                    </div>

                                    <div
                                        style="margin-top:8px;font-size:14px;line-height:1.7;color:#1e3a8a;"
                                    >
                                        Reference:
                                        <strong>{{ $notification->planRequest->payment_claim_reference }}</strong>
                                        <br>
                                        Method:
                                        <strong>{{ $notification->planRequest->payment_claim_method }}</strong>
                                        <br>
                                        Paid:
                                        <strong>{{ $notification->planRequest->payment_claim_paid_at?->copy()?->timezone(config('app.display_timezone'))?->format('M j, Y g:i:s A T') }}</strong>
                                    </div>
                                </div>
                            @endif

                            @if (
                                $notification->planRequest
                                    ?->payment_claim_rejection_reason
                            )
                                <div
                                    style="margin-top:22px;padding:16px;border:1px solid #fecaca;border-radius:12px;background:#fef2f2;color:#991b1b;"
                                >
                                    <strong>Verification note</strong>
                                    <br>
                                    {{ $notification->planRequest->payment_claim_rejection_reason }}
                                </div>
                            @endif

                            @if ($actionUrl && $actionLabel)
                                <p style="margin:28px 0 0;">
                                    <a
                                        href="{{ $actionUrl }}"
                                        style="display:inline-block;border-radius:10px;background:#0f766e;padding:13px 20px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;"
                                    >
                                        {{ $actionLabel }}
                                    </a>
                                </p>
                            @endif

                            <p
                                style="margin:28px 0 0;color:#6b7280;font-size:12px;line-height:1.6;"
                            >
                                This is an automated subscription workflow
                                notification from eConsent.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
