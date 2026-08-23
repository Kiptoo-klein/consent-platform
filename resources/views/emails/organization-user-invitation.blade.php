@extends('emails.layouts.transactional')

@section(
    'title',
    'Invitation to '.$organizationName.' on eConsent'
)

@section(
    'preheader',
    'Complete your eConsent account setup.'
)

@section('eyebrow', 'Organization invitation')

@section('headline')
    You’re invited to {{ $organizationName }}
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
        Hello {{ $userName }},
    </p>

    <p
        style="
            margin:0 0 20px;
            font-size:15px;
            line-height:1.7;
            color:#374151;
        "
    >
        You have been invited to join
        <strong style="color:#111827;">
            {{ $organizationName }}
        </strong>
        on eConsent.
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
            <td
                style="
                    padding:16px 18px;
                "
            >
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
                    Invitation details
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
                            Name
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
                            {{ $userName }}
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
                            Role
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
                            {{ $roleName }}
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
                                word-break:break-all;
                            "
                        >
                            {{ $email }}
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
        Complete your account setup by choosing your own password.
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
                    href="{{ $invitationUrl }}"
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
                    Complete account setup
                </a>
            </td>
        </tr>
    </table>

    <div
        style="
            margin:0 0 22px;
            padding:14px 16px;
            border-left:4px solid #f59e0b;
            border-radius:8px;
            background:#fffbeb;
            font-size:13px;
            line-height:1.6;
            color:#92400e;
        "
    >
        <strong>This invitation expires after seven days.</strong>
        If you were not expecting this invitation, you can safely
        ignore this email.
    </div>

    <p
        style="
            margin:0 0 7px;
            font-size:12px;
            line-height:1.6;
            color:#64748b;
        "
    >
        If the button does not open, copy this secure address into
        your browser:
    </p>

    <p
        style="
            margin:0;
            padding:11px 12px;
            border-radius:8px;
            background:#f8fafc;
            font-size:11px;
            line-height:1.6;
            word-break:break-all;
            color:#475569;
        "
    >
        {{ $invitationUrl }}
    </p>
@endsection
