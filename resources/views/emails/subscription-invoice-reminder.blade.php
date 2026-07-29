<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        {{ $headline }}
    </title>
</head>

<body style="margin:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    @php
        $organizationName =
            $invoice->organization?->name
            ?? config('app.name', 'Consent Platform');

        $planName =
            $invoice->plan?->name
            ?? 'Subscription plan';

        $billingOwnerName =
            $invoice
                ->subscription
                ?->billingOwner
                ?->name
            ?? 'Billing administrator';
    @endphp

    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        style="background:#f3f4f6;padding:32px 12px;"
    >
        <tr>
            <td align="center">
                <table
                    role="presentation"
                    width="100%"
                    cellspacing="0"
                    cellpadding="0"
                    style="max-width:620px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb;"
                >
                    <tr>
                        <td style="background:#312e81;padding:28px 32px;color:#ffffff;">
                            <div style="font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;opacity:.8;">
                                {{ $organizationName }}
                            </div>

                            <h1 style="margin:10px 0 0;font-size:25px;line-height:1.3;">
                                {{ $headline }}
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 18px;font-size:16px;line-height:1.7;">
                                Hello {{ $billingOwnerName }},
                            </p>

                            <p style="margin:0 0 18px;font-size:16px;line-height:1.7;color:#374151;">
                                {{ $introMessage }}
                            </p>

                            <div style="margin:22px 0;padding:18px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;">
                                <div style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#6b7280;">
                                    Subscription invoice
                                </div>

                                <div style="margin-top:7px;font-size:19px;font-weight:700;color:#111827;">
                                    {{ $invoice->invoice_number }}
                                </div>

                                <table
                                    role="presentation"
                                    width="100%"
                                    cellspacing="0"
                                    cellpadding="0"
                                    style="margin-top:16px;"
                                >
                                    <tr>
                                        <td style="padding:5px 0;color:#6b7280;">
                                            Plan
                                        </td>

                                        <td
                                            align="right"
                                            style="padding:5px 0;font-weight:700;"
                                        >
                                            {{ $planName }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td style="padding:5px 0;color:#6b7280;">
                                            Due date
                                        </td>

                                        <td
                                            align="right"
                                            style="padding:5px 0;font-weight:700;"
                                        >
                                            {{ $invoice->due_date?->format('d M Y') }}
                                        </td>
                                    </tr>

                                    <tr>
                                        <td style="padding:5px 0;color:#6b7280;">
                                            Amount due
                                        </td>

                                        <td
                                            align="right"
                                            style="padding:5px 0;font-size:18px;font-weight:700;"
                                        >
                                            {{ $invoice->currency.' '.number_format((float) $invoice->total_amount, 2) }}
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <table
                                role="presentation"
                                cellspacing="0"
                                cellpadding="0"
                                style="margin:26px 0;"
                            >
                                <tr>
                                    <td style="border-radius:10px;background:#4f46e5;">
                                        <a
                                            href="{{ $invoiceUrl }}"
                                            style="display:inline-block;padding:14px 22px;color:#ffffff;text-decoration:none;font-weight:700;font-size:16px;"
                                        >
                                            View subscription invoice
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#6b7280;">
                                Or copy this link into your browser:
                            </p>

                            <p style="margin:0;padding:12px;background:#f3f4f6;border-radius:8px;font-size:12px;line-height:1.6;word-break:break-all;color:#374151;">
                                {{ $invoiceUrl }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 32px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:12px;line-height:1.6;color:#6b7280;">
                            Sent by
                            {{ config('app.name', 'Consent Platform') }}
                            for {{ $organizationName }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
