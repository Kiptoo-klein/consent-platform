<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>{{ $title }} - Consent Register</title>

    <style>
        @page {
            margin: 32px 28px 42px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #1f2937;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9px;
            line-height: 1.35;
        }

        .header {
            margin-bottom: 18px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 12px;
        }

        .eyebrow {
            margin: 0 0 4px;
            color: #6b7280;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            color: #111827;
            font-size: 18px;
            line-height: 1.2;
        }

        .subtitle {
            margin: 3px 0 0;
            color: #4b5563;
            font-size: 10px;
        }

        .meta {
            margin-top: 10px;
            color: #6b7280;
            font-size: 8px;
        }

        .meta strong {
            color: #374151;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: {{ $tableFontSize }}px;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            vertical-align: top;
            overflow-wrap: anywhere;
            word-wrap: break-word;
        }

        th {
            background: #f3f4f6;
            color: #111827;
            font-weight: bold;
            text-align: left;
        }

        tbody tr:nth-child(even) td {
            background: #f9fafb;
        }

        .empty {
            padding: 30px 10px;
            color: #6b7280;
            text-align: center;
        }

        .footer-note {
            position: fixed;
            right: 110px;
            bottom: -25px;
            left: 0;
            color: #6b7280;
            font-size: 7px;
        }
    </style>
</head>

<body>
    <div class="header">
        <p class="eyebrow">
            eConsent
        </p>

        <h1>
            {{ $title }}
        </h1>

        <p class="subtitle">
            Consent Register
        </p>

        <p class="meta">
            <strong>{{ $recordCount }}</strong>
            {{ $recordCount === 1 ? 'record' : 'records' }}
            &nbsp;·&nbsp;
            Generated {{ $generatedAt->format('d M Y, H:i') }}
        </p>
    </div>

    @if ($rows->isEmpty())
        <div class="empty">
            No matching consent records.
        </div>
    @else
        <table>
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
        Generated from eConsent consent records.
    </div>
</body>
</html>
