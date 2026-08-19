<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>{{ $title }} - Consent Register</title>

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

        .brand {
            margin: 0 0 6px;
            color: #0f766e;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            color: #0f172a;
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
            border: 1px solid #99f6e4;
            background: #f0fdfa;
            padding: 6px 10px;
            color: #0f766e;
            font-size: 7px;
            font-weight: bold;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .header-rule {
            margin-top: 14px;
            border-top: 2px solid #0f766e;
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
            background: #0f766e;
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
                    <p class="brand">
                        eConsent
                    </p>

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

    <div class="footer-note">
        eConsent &nbsp;·&nbsp; Consent Register
        &nbsp;·&nbsp;
        {{ $generatedAt->format('d M Y') }}
    </div>
</body>
</html>
