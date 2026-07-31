<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <title>
        Subscription Invoice {{ $invoice->invoice_number }}
    </title>

    <style>
        @page {
            margin: 32px 38px 58px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            color: #1f2937;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.45;
            margin: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .no-border,
        .no-border td {
            border: 0;
        }

        .header {
            margin-bottom: 17px;
        }

        .brand-column {
            vertical-align: top;
            width: 58%;
        }

        .invoice-column {
            text-align: right;
            vertical-align: top;
            width: 42%;
        }

        .brand-mark {
            background: {{ $platformBrand['pdf_primary_color'] ?? '#312E81' }};
            border-radius: 50%;
            color: #ffffff;
            display: inline-block;
            font-size: 16px;
            font-weight: bold;
            height: 40px;
            line-height: 40px;
            margin-right: 9px;
            text-align: center;
            vertical-align: middle;
            width: 40px;
        }

        .brand-copy {
            display: inline-block;
            vertical-align: middle;
        }

        .brand-name {
            color: #111827;
            display: block;
            font-size: 18px;
            font-weight: bold;
        }

        .brand-description {
            color: #64748b;
            display: block;
            font-size: 8px;
            letter-spacing: 0.1em;
            margin-top: 3px;
            text-transform: uppercase;
        }

        .invoice-title {
            color: #111827;
            font-size: 26px;
            font-weight: bold;
            line-height: 1;
        }

        .invoice-number {
            color: #64748b;
            font-size: 9px;
            margin-top: 7px;
        }

        .status-badge {
            border-radius: 20px;
            display: inline-block;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 0.07em;
            margin-top: 8px;
            padding: 4px 10px;
            text-transform: uppercase;
        }

        .accent-line {
            background: {{ $platformBrand['accent_color'] ?? '#4F46E5' }};
            height: 4px;
            margin-bottom: 18px;
        }

        .section-label {
            color: {{ $platformBrand['accent_color'] ?? '#4F46E5' }};
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 0.09em;
            margin-bottom: 7px;
            text-transform: uppercase;
        }

        .party-table {
            margin-bottom: 18px;
        }

        .party-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 13px 15px;
            vertical-align: top;
            width: 49%;
        }

        .party-spacer {
            border: 0;
            width: 2%;
        }

        .party-name {
            color: #111827;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .party-line {
            color: #64748b;
            font-size: 9px;
            margin-top: 3px;
        }

        .metadata td {
            border: 0;
            border-bottom: 1px solid #e5e7eb;
            padding: 4px 0;
        }

        .metadata tr:last-child td {
            border-bottom: 0;
        }

        .metadata-label {
            color: #64748b;
            width: 45%;
        }

        .metadata-value {
            color: #111827;
            font-weight: bold;
            text-align: right;
            width: 55%;
        }

        .line-items {
            margin-bottom: 17px;
        }

        .line-items thead {
            display: table-header-group;
        }

        .line-items th {
            background: {{ $platformBrand['pdf_primary_color'] ?? '#312E81' }};
            border: 1px solid {{ $platformBrand['pdf_primary_color'] ?? '#312E81' }};
            color: #ffffff;
            font-size: 8px;
            letter-spacing: 0.06em;
            padding: 8px 9px;
            text-align: left;
            text-transform: uppercase;
        }

        .line-items td {
            border: 1px solid #e2e8f0;
            padding: 11px 9px;
            vertical-align: top;
        }

        .description-column {
            width: 46%;
        }

        .cycle-column {
            width: 17%;
        }

        .quantity-column {
            text-align: center;
            width: 8%;
        }

        .money-column {
            text-align: right;
            width: 14.5%;
        }

        .item-name {
            color: #111827;
            font-size: 11px;
            font-weight: bold;
        }

        .item-description {
            color: #64748b;
            font-size: 8.5px;
            margin-top: 4px;
        }

        .summary-table {
            margin-bottom: 17px;
            page-break-inside: avoid;
        }

        .reference-column {
            border: 0;
            color: #64748b;
            padding-right: 22px;
            vertical-align: top;
            width: 53%;
        }

        .totals-column {
            border: 0;
            vertical-align: top;
            width: 47%;
        }

        .reference-column strong {
            color: #111827;
        }

        .totals {
            border: 1px solid #e2e8f0;
        }

        .totals td {
            border: 0;
            border-bottom: 1px solid #e5e7eb;
            padding: 6px 10px;
        }

        .totals tr:last-child td {
            border-bottom: 0;
        }

        .total-label {
            color: #64748b;
        }

        .total-value {
            color: #111827;
            font-weight: bold;
            text-align: right;
        }

        .amount-due td {
            background: #eef2ff;
            border-top: 2px solid {{ $platformBrand['accent_color'] ?? '#4F46E5' }};
            color: {{ $platformBrand['pdf_primary_color'] ?? '#312E81' }};
            font-size: 11px;
            font-weight: bold;
            padding-bottom: 9px;
            padding-top: 9px;
        }

        .payment-section {
            border: 1px solid #c7d2fe;
            page-break-inside: avoid;
        }

        .payment-header {
            background: #eef2ff;
            border-bottom: 1px solid #c7d2fe;
            padding: 10px 13px;
        }

        .payment-title {
            color: {{ $platformBrand['pdf_primary_color'] ?? '#312E81' }};
            font-size: 12px;
            font-weight: bold;
        }

        .payment-subtitle {
            color: {{ $platformBrand['accent_color'] ?? '#4F46E5' }};
            font-size: 8.5px;
            margin-top: 3px;
        }

        .payment-body {
            padding: 12px;
        }

        .payment-table td {
            vertical-align: top;
        }

        .payment-card {
            border: 1px solid #d1d5db;
            padding: 10px 11px;
            width: 49%;
        }

        .payment-spacer {
            border: 0;
            width: 2%;
        }

        .mpesa-card {
            background: #f0fdf4;
            border-color: #86efac;
        }

        .bank-card {
            background: #eff6ff;
            border-color: #93c5fd;
        }

        .payment-method-name {
            color: #111827;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 7px;
        }

        .payment-label {
            color: #64748b;
            font-size: 7.5px;
            font-weight: bold;
            letter-spacing: 0.05em;
            margin-top: 6px;
            text-transform: uppercase;
        }

        .payment-value {
            color: #111827;
            font-size: 9.5px;
            font-weight: bold;
            margin-top: 2px;
            word-break: break-word;
        }

        .payment-value-large {
            font-size: 13px;
        }

        .instruction-text {
            color: #374151;
            font-size: 8.5px;
            margin-top: 7px;
            white-space: pre-line;
        }

        .billing-contact {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            margin-top: 10px;
            padding: 9px 11px;
        }

        .contact-table td {
            border: 0;
            padding: 2px 8px 2px 0;
            vertical-align: top;
        }

        .warning {
            background: #fff7ed;
            border: 1px solid #fdba74;
            color: #9a3412;
            font-weight: bold;
            padding: 10px;
        }

        .notes {
            background: #f8fafc;
            border-left: 4px solid #94a3b8;
            margin-top: 14px;
            padding: 9px 12px;
            page-break-inside: avoid;
        }

        .notes-title {
            color: #334155;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 0.07em;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .notes-copy {
            color: #475569;
            white-space: pre-line;
        }

        .footer {
            border-top: 1px solid #e5e7eb;
            bottom: -36px;
            color: #64748b;
            font-size: 7.5px;
            left: 0;
            padding-top: 6px;
            position: fixed;
            right: 0;
        }

        .footer td {
            border: 0;
        }

        .footer-left {
            text-align: left;
            width: 60%;
        }

        .footer-right {
            text-align: right;
            width: 40%;
        }
    </style>
</head>

<body data-invoice-pdf-layout="professional-v2">
    @php
        $paymentDetails =
            $invoice->payment_details_snapshot
            ?? [];

        $issuerName =
            $platformBrand['platform_name']
            ?? config(
                'app.name',
                'eConsent'
            );

        $platformLogoDataUri =
            app(
                \App\Services\PlatformBrandingSettingsService::class
            )->logoDataUri();

        $organization =
            $invoice->organization;

        $plan =
            $invoice->plan;

        $subscription =
            $invoice->relationLoaded('subscription')
                ? $invoice->subscription
                : null;

        $planRequest =
            $invoice->relationLoaded('planRequest')
                ? $invoice->planRequest
                : null;

        $transactions =
            $invoice->relationLoaded('transactions')
                ? $invoice->transactions
                : collect();

        $billingCycle =
            $planRequest?->billing_cycle
            ?? $subscription?->billing_cycle;

        $billingCycleLabel =
            match ($billingCycle) {
                'monthly' => 'Monthly',
                'annual' => 'Annual',
                default => 'Subscription',
            };

        $serviceDescription =
            match ($billingCycle) {
                'monthly' =>
                    'One calendar month from the confirmed payment date.',

                'annual' =>
                    'One calendar year from the confirmed payment date.',

                default =>
                    'Subscription period begins after payment confirmation.',
            };

        $successfulPaidTotal =
            (float) $transactions
                ->filter(
                    static function ($transaction): bool {
                        $status =
                            $transaction->status;

                        $type =
                            $transaction->type;

                        $statusValue =
                            $status instanceof \BackedEnum
                                ? $status->value
                                : (string) $status;

                        $typeValue =
                            $type instanceof \BackedEnum
                                ? $type->value
                                : (string) $type;

                        return $statusValue === 'successful'
                            && in_array(
                                $typeValue,
                                [
                                    'payment',
                                    'renewal',
                                ],
                                true
                            );
                    }
                )
                ->sum(
                    static fn ($transaction): float =>
                        (float) $transaction->amount
                );

        $balanceDue =
            max(
                0,
                round(
                    (float) $invoice->total_amount
                    - $successfulPaidTotal,
                    2
                )
            );

        $invoiceStatus =
            $invoice->status;

        $statusValue =
            $invoiceStatus instanceof \BackedEnum
                ? $invoiceStatus->value
                : (string) $invoiceStatus;

        $statusLabel =
            is_object($invoiceStatus)
            && method_exists(
                $invoiceStatus,
                'label'
            )
                ? $invoiceStatus->label()
                : ucfirst($statusValue);

        [
            $statusBackground,
            $statusColor,
            $statusBorder,
        ] = match ($statusValue) {
            'paid' => [
                '#dcfce7',
                '#166534',
                '#86efac',
            ],

            'overdue' => [
                '#fee2e2',
                '#991b1b',
                '#fca5a5',
            ],

            'issued' => [
                '#dbeafe',
                '#1e40af',
                '#93c5fd',
            ],

            'void',
            'cancelled' => [
                '#f1f5f9',
                '#475569',
                '#cbd5e1',
            ],

            default => [
                '#fef3c7',
                '#92400e',
                '#fcd34d',
            ],
        };

        $mpesaEnabled =
            (bool) data_get(
                $paymentDetails,
                'mpesa_enabled',
                false
            );

        $bankEnabled =
            (bool) data_get(
                $paymentDetails,
                'bank_enabled',
                false
            );

        $billingEmail =
            data_get(
                $paymentDetails,
                'billing_contact_email'
            );

        $billingPhone =
            data_get(
                $paymentDetails,
                'billing_contact_phone'
            );

        $additionalInstructions =
            data_get(
                $paymentDetails,
                'additional_instructions'
            );

        $hasPaymentInformation =
            $mpesaEnabled
            || $bankEnabled
            || filled($billingEmail)
            || filled($billingPhone)
            || filled($additionalInstructions);

        $organizationContactLines =
            array_values(
                array_filter([
                    $organization?->address,
                    $organization?->email,
                    $organization?->phone,
                    $organization?->website,
                ])
            );

        $bankFields =
            [
                'Bank' =>
                    data_get(
                        $paymentDetails,
                        'bank_name'
                    ),

                'Account name' =>
                    data_get(
                        $paymentDetails,
                        'bank_account_name'
                    ),

                'Account number' =>
                    data_get(
                        $paymentDetails,
                        'bank_account_number'
                    ),

                'Branch' =>
                    data_get(
                        $paymentDetails,
                        'bank_branch'
                    ),

                'SWIFT / BIC' =>
                    data_get(
                        $paymentDetails,
                        'bank_swift_code'
                    ),
            ];
    @endphp

    <table class="header no-border">
        <tr>
            <td class="brand-column">
                @if ($platformLogoDataUri)
                    <img
                        src="{{ $platformLogoDataUri }}"
                        alt="{{ $issuerName }} logo"
                        style="
                            display:inline-block;
                            height:40px;
                            max-width:120px;
                            margin-right:9px;
                            object-fit:contain;
                            vertical-align:middle;
                        "
                    >
                @else
                    <span class="brand-mark">
                        {{ $platformBrand['short_name'] ?? 'eC' }}
                    </span>
                @endif

                <span class="brand-copy">
                    <span class="brand-name">
                        {{ $issuerName }}
                    </span>

                    <span class="brand-description">
                        Subscription Billing
                    </span>
                </span>
            </td>

            <td class="invoice-column">
                <div class="invoice-title">
                    INVOICE
                </div>

                <div class="invoice-number">
                    {{ $invoice->invoice_number }}
                </div>

                <span
                    class="status-badge"
                    style="
                        background:{{ $statusBackground }};
                        color:{{ $statusColor }};
                        border:1px solid {{ $statusBorder }};
                    "
                >
                    {{ $statusLabel }}
                </span>
            </td>
        </tr>
    </table>

    <div class="accent-line"></div>

    <table class="party-table">
        <tr>
            <td class="party-card">
                <div class="section-label">
                    Billed To
                </div>

                <div class="party-name">
                    {{ $organization?->name ?? 'Organization' }}
                </div>

                @forelse ($organizationContactLines as $contactLine)
                    <div class="party-line">
                        {{ $contactLine }}
                    </div>
                @empty
                    <div class="party-line">
                        Organization subscription account
                    </div>
                @endforelse
            </td>

            <td class="party-spacer"></td>

            <td
                class="party-card"
                data-invoice-summary
            >
                <div class="section-label">
                    Invoice Summary
                </div>

                <table class="metadata">
                    <tr>
                        <td class="metadata-label">
                            Invoice number
                        </td>

                        <td class="metadata-value">
                            {{ $invoice->invoice_number }}
                        </td>
                    </tr>

                    <tr>
                        <td class="metadata-label">
                            Issue date
                        </td>

                        <td class="metadata-value">
                            {{ $invoice->issue_date?->format('d M Y') ?? '-' }}
                        </td>
                    </tr>

                    <tr>
                        <td class="metadata-label">
                            Due date
                        </td>

                        <td class="metadata-value">
                            {{ $invoice->due_date?->format('d M Y') ?? '-' }}
                        </td>
                    </tr>

                    <tr>
                        <td class="metadata-label">
                            Issued by
                        </td>

                        <td class="metadata-value">
                            {{ $invoice->issuedBy?->name ?? 'Platform Billing' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-label">
        Subscription Charges
    </div>

    <table
        class="line-items"
        data-invoice-line-items
    >
        <thead>
            <tr>
                <th class="description-column">
                    Description
                </th>

                <th class="cycle-column">
                    Billing Cycle
                </th>

                <th class="quantity-column">
                    Qty
                </th>

                <th class="money-column">
                    Rate
                </th>

                <th class="money-column">
                    Amount
                </th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td>
                    <div class="item-name">
                        {{ $plan?->name ?? 'Subscription' }} Plan
                    </div>

                    <div class="item-description">
                        {{ $serviceDescription }}
                    </div>
                </td>

                <td>
                    {{ $billingCycleLabel }}
                </td>

                <td class="quantity-column">
                    1
                </td>

                <td class="money-column">
                    {{ $invoice->currency }}
                    {{ number_format(
                        (float) $invoice->subtotal,
                        2
                    ) }}
                </td>

                <td class="money-column">
                    {{ $invoice->currency }}
                    {{ number_format(
                        (float) $invoice->subtotal,
                        2
                    ) }}
                </td>
            </tr>
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td class="reference-column">
                <div class="section-label">
                    Payment Reference
                </div>

                <div>
                    Use invoice number
                    <strong>{{ $invoice->invoice_number }}</strong>
                    as the payment reference unless the payment
                    instructions below specify otherwise.
                </div>

                @if ($invoice->paid_at)
                    <div style="margin-top:8px;">
                        <strong>Payment recorded:</strong>
                        {{ $invoice->paid_at->format('d M Y H:i') }}
                    </div>
                @endif
            </td>

            <td
                class="totals-column"
                data-invoice-totals
            >
                <table class="totals">
                    <tr>
                        <td class="total-label">
                            Subtotal
                        </td>

                        <td class="total-value">
                            {{ $invoice->currency }}
                            {{ number_format(
                                (float) $invoice->subtotal,
                                2
                            ) }}
                        </td>
                    </tr>

                    <tr>
                        <td class="total-label">
                            Tax
                        </td>

                        <td class="total-value">
                            {{ $invoice->currency }}
                            {{ number_format(
                                (float) $invoice->tax_amount,
                                2
                            ) }}
                        </td>
                    </tr>

                    <tr>
                        <td class="total-label">
                            Invoice total
                        </td>

                        <td class="total-value">
                            {{ $invoice->currency }}
                            {{ number_format(
                                (float) $invoice->total_amount,
                                2
                            ) }}
                        </td>
                    </tr>

                    @if ($successfulPaidTotal > 0)
                        <tr>
                            <td class="total-label">
                                Payments received
                            </td>

                            <td class="total-value">
                                - {{ $invoice->currency }}
                                {{ number_format(
                                    $successfulPaidTotal,
                                    2
                                ) }}
                            </td>
                        </tr>
                    @endif

                    <tr class="amount-due">
                        <td>
                            Amount Due
                        </td>

                        <td style="text-align:right;">
                            {{ $invoice->currency }}
                            {{ number_format(
                                $balanceDue,
                                2
                            ) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <section
        class="payment-section"
        data-invoice-payment-instructions
    >
        <div class="payment-header">
            <div class="payment-title">
                Payment Instructions
            </div>

            <div class="payment-subtitle">
                Use only the payment details preserved on this issued invoice.
            </div>
        </div>

        <div class="payment-body">
            @if ($hasPaymentInformation)
                @if ($mpesaEnabled && $bankEnabled)
                    <table class="payment-table">
                        <tr>
                            <td class="payment-card mpesa-card">
                                <div class="payment-method-name">
                                    M-Pesa
                                </div>

                                <div class="payment-label">
                                    Payment type
                                </div>

                                <div class="payment-value">
                                    {{ ucfirst(
                                        (string) data_get(
                                            $paymentDetails,
                                            'mpesa_type',
                                            'M-Pesa'
                                        )
                                    ) }}
                                </div>

                                <div class="payment-label">
                                    Paybill or Till number
                                </div>

                                <div class="payment-value payment-value-large">
                                    {{ data_get(
                                        $paymentDetails,
                                        'mpesa_business_number',
                                        'Unavailable'
                                    ) }}
                                </div>

                                @if (
                                    filled(
                                        data_get(
                                            $paymentDetails,
                                            'mpesa_account_reference_instructions'
                                        )
                                    )
                                )
                                    <div class="instruction-text">
                                        {{ data_get(
                                            $paymentDetails,
                                            'mpesa_account_reference_instructions'
                                        ) }}
                                    </div>
                                @endif

                                @if (
                                    filled(
                                        data_get(
                                            $paymentDetails,
                                            'mpesa_instructions'
                                        )
                                    )
                                )
                                    <div class="instruction-text">
                                        {{ data_get(
                                            $paymentDetails,
                                            'mpesa_instructions'
                                        ) }}
                                    </div>
                                @endif
                            </td>

                            <td class="payment-spacer"></td>

                            <td class="payment-card bank-card">
                                <div class="payment-method-name">
                                    Bank Transfer
                                </div>

                                @foreach ($bankFields as $label => $value)
                                    @if (filled($value))
                                        <div class="payment-label">
                                            {{ $label }}
                                        </div>

                                        <div class="payment-value">
                                            {{ $value }}
                                        </div>
                                    @endif
                                @endforeach

                                @if (
                                    filled(
                                        data_get(
                                            $paymentDetails,
                                            'bank_reference_instructions'
                                        )
                                    )
                                )
                                    <div class="instruction-text">
                                        {{ data_get(
                                            $paymentDetails,
                                            'bank_reference_instructions'
                                        ) }}
                                    </div>
                                @endif

                                @if (
                                    filled(
                                        data_get(
                                            $paymentDetails,
                                            'bank_instructions'
                                        )
                                    )
                                )
                                    <div class="instruction-text">
                                        {{ data_get(
                                            $paymentDetails,
                                            'bank_instructions'
                                        ) }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    </table>
                @elseif ($mpesaEnabled)
                    <div class="payment-card mpesa-card" style="width:100%;">
                        <div class="payment-method-name">
                            M-Pesa
                        </div>

                        <table class="contact-table">
                            <tr>
                                <td>
                                    <div class="payment-label">
                                        Payment type
                                    </div>

                                    <div class="payment-value">
                                        {{ ucfirst(
                                            (string) data_get(
                                                $paymentDetails,
                                                'mpesa_type',
                                                'M-Pesa'
                                            )
                                        ) }}
                                    </div>
                                </td>

                                <td>
                                    <div class="payment-label">
                                        Paybill or Till number
                                    </div>

                                    <div class="payment-value payment-value-large">
                                        {{ data_get(
                                            $paymentDetails,
                                            'mpesa_business_number',
                                            'Unavailable'
                                        ) }}
                                    </div>
                                </td>
                            </tr>
                        </table>

                        @if (
                            filled(
                                data_get(
                                    $paymentDetails,
                                    'mpesa_account_reference_instructions'
                                )
                            )
                        )
                            <div class="instruction-text">
                                {{ data_get(
                                    $paymentDetails,
                                    'mpesa_account_reference_instructions'
                                ) }}
                            </div>
                        @endif

                        @if (
                            filled(
                                data_get(
                                    $paymentDetails,
                                    'mpesa_instructions'
                                )
                            )
                        )
                            <div class="instruction-text">
                                {{ data_get(
                                    $paymentDetails,
                                    'mpesa_instructions'
                                ) }}
                            </div>
                        @endif
                    </div>
                @elseif ($bankEnabled)
                    <div class="payment-card bank-card" style="width:100%;">
                        <div class="payment-method-name">
                            Bank Transfer
                        </div>

                        <table class="contact-table">
                            @foreach (
                                array_chunk(
                                    $bankFields,
                                    2,
                                    true
                                )
                                as $bankRow
                            )
                                <tr>
                                    @foreach ($bankRow as $label => $value)
                                        <td>
                                            @if (filled($value))
                                                <div class="payment-label">
                                                    {{ $label }}
                                                </div>

                                                <div class="payment-value">
                                                    {{ $value }}
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </table>

                        @if (
                            filled(
                                data_get(
                                    $paymentDetails,
                                    'bank_reference_instructions'
                                )
                            )
                        )
                            <div class="instruction-text">
                                {{ data_get(
                                    $paymentDetails,
                                    'bank_reference_instructions'
                                ) }}
                            </div>
                        @endif

                        @if (
                            filled(
                                data_get(
                                    $paymentDetails,
                                    'bank_instructions'
                                )
                            )
                        )
                            <div class="instruction-text">
                                {{ data_get(
                                    $paymentDetails,
                                    'bank_instructions'
                                ) }}
                            </div>
                        @endif
                    </div>
                @endif

                @if (
                    filled($billingEmail)
                    || filled($billingPhone)
                    || filled($additionalInstructions)
                )
                    <div class="billing-contact">
                        <div class="payment-method-name">
                            Billing Contact
                        </div>

                        <table class="contact-table">
                            <tr>
                                @if (filled($billingEmail))
                                    <td>
                                        <div class="payment-label">
                                            Email
                                        </div>

                                        <div class="payment-value">
                                            {{ $billingEmail }}
                                        </div>
                                    </td>
                                @endif

                                @if (filled($billingPhone))
                                    <td>
                                        <div class="payment-label">
                                            Phone
                                        </div>

                                        <div class="payment-value">
                                            {{ $billingPhone }}
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        </table>

                        @if (filled($additionalInstructions))
                            <div class="instruction-text">
                                {{ $additionalInstructions }}
                            </div>
                        @endif
                    </div>
                @endif
            @else
                <div class="warning">
                    Payment details were not attached to this invoice.
                    Contact Platform Billing before sending payment.
                </div>
            @endif
        </div>
    </section>

    @if ($invoice->notes)
        <div class="notes">
            <div class="notes-title">
                Invoice Notes
            </div>

            <div class="notes-copy">
                {{ $invoice->notes }}
            </div>
        </div>
    @endif

    <table class="footer">
        <tr>
            <td class="footer-left">
                Generated by {{ $issuerName }} subscription billing.
            </td>

            <td class="footer-right">
                Document ID: {{ $invoice->invoice_number }}
            </td>
        </tr>
    </table>
</body>
</html>
