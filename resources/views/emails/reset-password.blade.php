@extends('emails.layouts.transactional')

@section('title', 'Reset your eConsent password')

@section(
    'preheader',
    'Use this secure link to reset your eConsent password.'
)

@section('eyebrow', 'Account security')

@section('headline')
    Reset your password
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
        We received a request to reset the password for your
        eConsent account.
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
                    href="{{ $resetUrl }}"
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
                    Reset password
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
        <strong>
            This password reset link expires in
            {{ $expiresInMinutes }} minutes.
        </strong>
        After it expires, request a new reset link from the
        eConsent sign-in page.
    </div>

    <p
        style="
            margin:0 0 22px;
            font-size:13px;
            line-height:1.7;
            color:#64748b;
        "
    >
        If you did not request a password reset, no action is
        required. Your current password will remain unchanged.
    </p>

    <p
        style="
            margin:0 0 7px;
            font-size:12px;
            line-height:1.6;
            color:#64748b;
        "
    >
        If the button does not open, copy this secure address
        into your browser:
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
        {{ $resetUrl }}
    </p>
@endsection
