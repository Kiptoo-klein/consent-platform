<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <title>
        Subscription Receipt {{ $transaction->reference }}
    </title>

    <style>
        @page {
            margin: 42px;
        }

        body {
            color: #1f2937;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }

        h1 {
            color: #111827;
            font-size: 24px;
            margin: 0 0 6px;
        }

        .subtitle {
            color: #6b7280;
            margin: 0 0 28px;
        }

        .reference {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            margin-bottom: 24px;
            padding: 18px;
        }

        .reference-label {
            color: #6b7280;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .reference-value {
            color: #111827;
            font-size: 20px;
            font-weight: bold;
            margin-top: 5px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        td {
            border: 1px solid #d1d5db;
            padding: 12px;
            vertical-align: top;
            width: 50%;
        }

        .label {
            color: #6b7280;
            display: block;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .value {
            color: #111827;
            font-weight: bold;
        }

        .amount {
            font-size: 17px;
        }

        .notes {
            border: 1px solid #d1d5db;
            margin-top: 20px;
            padding: 14px;
        }

        .footer {
            color: #6b7280;
            font-size: 9px;
            margin-top: 28px;
            text-align: center;
        }
    </style>
</head>

<body>
    <h1>Subscription Receipt</h1>

    <p class="subtitle">
        Receipt for {{ $transaction->organization->name }}
    </p>

    <div class="reference">
        <div class="reference-label">
            Transaction reference
        </div>

        <div class="reference-value">
            {{ $transaction->reference }}
        </div>
    </div>

    <table>
        <tr>
            <td>
                <span class="label">Organization</span>
                <span class="value">
                    {{ $transaction->organization->name }}
                </span>
            </td>

            <td>
                <span class="label">Subscription plan</span>
                <span class="value">
                    {{ $transaction->plan->name }}
                </span>
            </td>
        </tr>

        <tr>
            <td>
                <span class="label">Transaction type</span>
                <span class="value">
                    {{ $transaction->type?->label() }}
                </span>
            </td>

            <td>
                <span class="label">Status</span>
                <span class="value">
                    {{ $transaction->status?->label() }}
                </span>
            </td>
        </tr>

        <tr>
            <td>
                <span class="label">Amount</span>
                <span class="value amount">
                    {{ $transaction->currency }}
                    {{ number_format(
                        (float) $transaction->amount,
                        2
                    ) }}
                </span>
            </td>

            <td>
                <span class="label">Payment method</span>
                <span class="value">
                    {{ $transaction->payment_method ?? '—' }}
                </span>
            </td>
        </tr>

        <tr>
            <td>
                <span class="label">Paid at</span>
                <span class="value">
                    {{ $transaction->paid_at?->format('M d, Y H:i') ?? '—' }}
                </span>
            </td>

            <td>
                <span class="label">Recorded by</span>
                <span class="value">
                    {{ $transaction->recordedBy?->name ?? 'System' }}
                </span>
            </td>
        </tr>

        <tr>
            <td>
                <span class="label">Billing period starts</span>
                <span class="value">
                    {{ $transaction->period_starts_at?->format('M d, Y H:i') ?? '—' }}
                </span>
            </td>

            <td>
                <span class="label">Billing period ends</span>
                <span class="value">
                    {{ $transaction->period_ends_at?->format('M d, Y H:i') ?? '—' }}
                </span>
            </td>
        </tr>
    </table>

    @if ($transaction->notes)
        <div class="notes">
            <span class="label">Notes</span>

            <div>
                {{ $transaction->notes }}
            </div>
        </div>
    @endif

    <p class="footer">
        Generated by the eConsent subscription billing system.
    </p>
</body>
</html>
