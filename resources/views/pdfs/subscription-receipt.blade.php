<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <title>
        Subscription Receipt {{ $transaction->reference }}
    </title>

    <style>
        @page {
            margin: 30px;
        }

        body {
            color: #1f2937;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            line-height: 1.45;
            margin: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .header {
            background: #0f172a;
            color: #ffffff;
            margin-bottom: 18px;
            padding: 22px;
        }

        .eyebrow {
            color: #99f6e4;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .title {
            color: #ffffff;
            font-size: 24px;
            font-weight: bold;
            margin: 4px 0 2px;
        }

        .subtitle {
            color: #cbd5e1;
            font-size: 10px;
        }

        .status {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
            font-size: 9px;
            font-weight: bold;
            padding: 7px 10px;
            text-align: center;
        }

        .amount-label,
        .small-label {
            color: #64748b;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .amount {
            color: #ffffff;
            font-size: 25px;
            font-weight: bold;
            margin-top: 3px;
        }

        .reference-box {
            background: #1e293b;
            border: 1px solid #334155;
            color: #ffffff;
            padding: 10px;
        }

        .reference-box .small-label {
            color: #94a3b8;
        }

        .reference-value {
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            margin-top: 3px;
        }

        .section {
            border: 1px solid #dbe3ea;
            margin-top: 14px;
            padding: 14px;
        }

        .section-title {
            color: #111827;
            font-size: 13px;
            font-weight: bold;
            margin: 0 0 10px;
        }

        .detail-table td {
            border: 1px solid #e2e8f0;
            padding: 9px;
            vertical-align: top;
            width: 50%;
        }

        .value {
            color: #111827;
            font-size: 10px;
            font-weight: bold;
            margin-top: 3px;
        }

        .verification {
            background: #f0fdfa;
            border-color: #99f6e4;
        }

        .verification-title {
            color: #134e4a;
        }

        .verification .small-label {
            color: #0f766e;
        }

        .verification .value {
            color: #134e4a;
        }

        .notes {
            background: #fffbeb;
            border-color: #fde68a;
        }

        .notes .section-title {
            color: #92400e;
        }

        .notes-text {
            color: #78350f;
            white-space: pre-line;
        }

        .footer {
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 8px;
            margin-top: 18px;
            padding-top: 10px;
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        $workflowTransaction =
            $subscriptionTransaction
            ?? $transaction
            ?? null;

        $workflowReceiptInvoice =
            $workflowTransaction?->invoice;

        $workflowReceiptRequest =
            $workflowReceiptInvoice?->planRequest;

        $workflowOrganizationEmail =
            $workflowTransaction?->organization?->email
            ?: $workflowTransaction?->organization?->support_email
            ?: $workflowTransaction?->subscription?->billingOwner?->email;

        $receiptStatus =
            $transaction->status?->label()
            ?? 'Recorded';
    @endphp

    <div class="header">
        <table>
            <tr>
                <td style="width:70%; vertical-align:top;">
                    <div class="eyebrow">
                        Official subscription receipt
                    </div>

                    <div class="title">
                        Subscription Receipt
                    </div>

                    <div class="subtitle">
                        {{ $transaction->organization->name }}
                        - {{ $transaction->plan->name }}
                    </div>
                </td>

                <td style="width:30%; vertical-align:top;">
                    <div class="status">
                        Payment {{ $receiptStatus }}
                    </div>
                </td>
            </tr>
        </table>

        <table style="margin-top:20px;">
            <tr>
                <td style="width:55%; vertical-align:bottom;">
                    <div class="amount-label">
                        Amount paid
                    </div>

                    <div class="amount">
                        {{ $transaction->currency }}
                        {{ number_format(
                            (float) $transaction->amount,
                            2
                        ) }}
                    </div>
                </td>

                <td style="width:45%; vertical-align:bottom;">
                    <div class="reference-box">
                        <div class="small-label">
                            Receipt reference
                        </div>

                        <div class="reference-value">
                            {{ $transaction->reference }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">
            Payment details
        </div>

        <table class="detail-table">
            <tr>
                <td>
                    <div class="small-label">Organization</div>
                    <div class="value">
                        {{ $transaction->organization->name }}
                    </div>
                </td>

                <td>
                    <div class="small-label">Subscription plan</div>
                    <div class="value">
                        {{ $transaction->plan->name }}
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="small-label">Transaction type</div>
                    <div class="value">
                        {{ $transaction->type?->label() }}
                    </div>
                </td>

                <td>
                    <div class="small-label">Payment method</div>
                    <div class="value">
                        {{ $transaction->payment_method ?? '—' }}
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="small-label">Paid at</div>
                    <div class="value">
                        {{ $transaction->paid_at?->copy()?->timezone(config('app.display_timezone'))?->format('M j, Y g:i A') ?? '—' }}
                    </div>
                </td>

                <td>
                    <div class="small-label">Recorded by</div>
                    <div class="value">
                        {{ $transaction->recordedBy?->name ?? 'System' }}
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="small-label">Billing period starts</div>
                    <div class="value">
                        {{ $transaction->period_starts_at?->copy()?->timezone(config('app.display_timezone'))?->format('M j, Y g:i A') ?? '—' }}
                    </div>
                </td>

                <td>
                    <div class="small-label">Billing period ends</div>
                    <div class="value">
                        {{ $transaction->period_ends_at?->copy()?->timezone(config('app.display_timezone'))?->format('M j, Y g:i A') ?? '—' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section verification">
        <div class="section-title verification-title">
            Verified payment details
        </div>

        <table class="detail-table">
            <tr>
                <td>
                    <div class="small-label">Organization email</div>
                    <div class="value">
                        {{ $workflowOrganizationEmail ?: '—' }}
                    </div>
                </td>

                <td>
                    <div class="small-label">Invoice reference</div>
                    <div class="value">
                        {{ $workflowReceiptInvoice?->invoice_number ?? '—' }}
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="small-label">Billing cycle</div>
                    <div class="value">
                        {{ ucfirst(
                            $workflowReceiptRequest?->billing_cycle
                            ?? '—'
                        ) }}
                    </div>
                </td>

                <td>
                    <div class="small-label">Exact payment time</div>
                    <div class="value">
                        {{ $workflowTransaction?->paid_at?->copy()?->timezone(config('app.display_timezone'))?->format(
                            'M j, Y g:i:s A T'
                        ) ?? '—' }}
                    </div>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="small-label">Confirmed by</div>
                    <div class="value">
                        {{ $workflowTransaction?->recordedBy?->name
                            ?? 'Platform Billing' }}
                    </div>
                </td>

                <td>
                    <div class="small-label">Receipt reference</div>
                    <div class="value">
                        {{ $workflowTransaction?->reference ?? '—' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if ($transaction->notes)
        <div class="section notes">
            <div class="section-title">
                Notes
            </div>

            <div class="notes-text">
                {{ $transaction->notes }}
            </div>
        </div>
    @endif

    <div class="footer">
        Generated by the eConsent subscription billing system.
    </div>
</body>
</html>
