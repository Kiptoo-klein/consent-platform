@extends('emails.layouts.transactional')

@section(
    'title',
    $requestType === 'problem'
        ? 'Problem report'
        : 'Support question'
)

@section(
    'preheader',
    'A new eConsent support request has been submitted.'
)

@section(
    'eyebrow',
    'eConsent support'
)

@section('headline')
    @if ($requestType === 'problem')
        Problem reported by {{ $organizationName }}
    @else
        Question from {{ $organizationName }}
    @endif
@endsection

@section('content')
    <p
        style="
            margin:0 0 18px;
            font-size:15px;
            line-height:1.7;
            color:#374151;
        "
    >
        A user submitted this request through the
        contextual Help panel.
    </p>

    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        style="
            width:100%;
            margin:22px 0;
            border:1px solid #e2e8f0;
            border-radius:12px;
            background:#f8fafc;
        "
    >
        <tr>
            <td style="padding:16px 18px;">
                <table
                    role="presentation"
                    width="100%"
                    cellspacing="0"
                    cellpadding="0"
                >
                    <tr>
                        <td
                            style="
                                padding:5px 0;
                                font-size:13px;
                                color:#64748b;
                            "
                        >
                            Request type
                        </td>

                        <td
                            align="right"
                            style="
                                padding:5px 0;
                                font-size:13px;
                                font-weight:700;
                                color:#111827;
                            "
                        >
                            {{ $requestType === 'problem'
                                ? 'Report a problem'
                                : 'Ask a question' }}
                        </td>
                    </tr>

                    <tr>
                        <td
                            style="
                                padding:5px 0;
                                font-size:13px;
                                color:#64748b;
                            "
                        >
                            Organization
                        </td>

                        <td
                            align="right"
                            style="
                                padding:5px 0;
                                font-size:13px;
                                font-weight:700;
                                color:#111827;
                            "
                        >
                            {{ $organizationName }}
                        </td>
                    </tr>

                    <tr>
                        <td
                            style="
                                padding:5px 0;
                                font-size:13px;
                                color:#64748b;
                            "
                        >
                            Requester
                        </td>

                        <td
                            align="right"
                            style="
                                padding:5px 0;
                                font-size:13px;
                                font-weight:700;
                                color:#111827;
                            "
                        >
                            {{ $requesterName }}
                        </td>
                    </tr>

                    <tr>
                        <td
                            style="
                                padding:5px 0;
                                font-size:13px;
                                color:#64748b;
                            "
                        >
                            Email
                        </td>

                        <td
                            align="right"
                            style="
                                padding:5px 0;
                                font-size:13px;
                                font-weight:700;
                                color:#111827;
                            "
                        >
                            {{ $requesterEmail }}
                        </td>
                    </tr>

                    <tr>
                        <td
                            style="
                                padding:5px 0;
                                font-size:13px;
                                color:#64748b;
                            "
                        >
                            Submitted
                        </td>

                        <td
                            align="right"
                            style="
                                padding:5px 0;
                                font-size:13px;
                                font-weight:700;
                                color:#111827;
                            "
                        >
                            {{ $submittedAt }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p
        style="
            margin:0 0 8px;
            font-size:13px;
            font-weight:700;
            color:#111827;
        "
    >
        Message
    </p>

    <div
        style="
            margin:0 0 22px;
            padding:16px 18px;
            border:1px solid #e2e8f0;
            border-radius:12px;
            background:#ffffff;
            font-size:14px;
            line-height:1.7;
            white-space:pre-wrap;
            color:#374151;
        "
    >{{ $messageBody }}</div>

    <p
        style="
            margin:0 0 8px;
            font-size:13px;
            font-weight:700;
            color:#111827;
        "
    >
        Page context
    </p>

    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        style="
            width:100%;
            margin:0 0 22px;
            border:1px solid #e2e8f0;
            border-radius:12px;
            background:#f8fafc;
        "
    >
        <tr>
            <td style="padding:16px 18px;">
                <p
                    style="
                        margin:0 0 6px;
                        font-size:13px;
                        line-height:1.6;
                        color:#475569;
                    "
                >
                    <strong>Route:</strong>
                    {{ $pageRoute ?: 'Unavailable' }}
                </p>

                <p
                    style="
                        margin:0 0 6px;
                        font-size:13px;
                        line-height:1.6;
                        color:#475569;
                    "
                >
                    <strong>Path:</strong>
                    {{ $pagePath ?: 'Unavailable' }}
                </p>

                <p
                    style="
                        margin:0;
                        font-size:13px;
                        line-height:1.6;
                        color:#475569;
                    "
                >
                    <strong>Help context:</strong>
                    {{ $helpContext ?: 'Unavailable' }}
                </p>
            </td>
        </tr>
    </table>

    <p
        style="
            margin:0;
            font-size:13px;
            line-height:1.7;
            color:#64748b;
        "
    >
        Reply to this email to respond directly to the requester.
    </p>
@endsection
