@extends('emails.layouts.transactional')

@section(
    'title',
    $requestType === 'organization'
        ? 'Organization restoration requested'
        : 'Account restoration requested'
)

@section(
    'preheader',
    'A restoration request needs your review.'
)

@section(
    'eyebrow',
    'Restoration request'
)

@section('headline')
    @if ($requestType === 'organization')
        Restore {{ $organizationName }}?
    @else
        Restore {{ $requesterName }}?
    @endif
@endsection

@section('content')
    <p
        style="
            margin:0 0 18px;
            font-size:16px;
            line-height:1.7;
            color:#374151;
        "
    >
        Hello {{ $reviewerName }},
    </p>

    <p
        style="
            margin:0 0 20px;
            font-size:15px;
            line-height:1.7;
            color:#374151;
        "
    >
        @if ($requestType === 'organization')
            A user from
            <strong style="color:#111827;">
                {{ $organizationName }}
            </strong>
            has requested restoration of the archived organization.
        @else
            An archived user from
            <strong style="color:#111827;">
                {{ $organizationName }}
            </strong>
            has requested account restoration.
        @endif
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
                <div
                    style="
                        margin-bottom:12px;
                        font-size:12px;
                        line-height:1.4;
                        font-weight:700;
                        letter-spacing:.08em;
                        text-transform:uppercase;
                        color:#64748b;
                    "
                >
                    Request details
                </div>

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
                                word-break:break-all;
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
                            Requested
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
                            {{ $requestedAt }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p
        style="
            margin:0 0 20px;
            font-size:15px;
            line-height:1.7;
            color:#374151;
        "
    >
        Review the request before restoring access.
        @if ($requestType === 'user')
            Restoring the account will also enable it immediately.
        @endif
    </p>

    <table
        role="presentation"
        cellspacing="0"
        cellpadding="0"
        style="margin:26px 0;"
    >
        <tr>
            <td
                style="
                    border-radius:10px;
                    background:#0f766e;
                "
            >
                <a
                    href="{{ $reviewUrl }}"
                    style="
                        display:inline-block;
                        padding:14px 22px;
                        color:#ffffff;
                        text-decoration:none;
                        font-size:15px;
                        line-height:1.2;
                        font-weight:700;
                    "
                >
                    @if ($requestType === 'organization')
                        Review organization
                    @else
                        Review archived user
                    @endif
                </a>
            </td>
        </tr>
    </table>

    <div
        style="
            margin:0;
            padding:14px 16px;
            border-left:4px solid #f59e0b;
            border-radius:8px;
            background:#fffbeb;
            font-size:13px;
            line-height:1.6;
            color:#92400e;
        "
    >
        This request does not restore access automatically.
        Review the affected account or organization before approving it.
    </div>
@endsection
