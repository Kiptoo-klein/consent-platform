@extends('emails.layouts.transactional')

@section('title', 'Verify your email address')

@section(
    'preheader',
    'Verify your email address to finish securing your eConsent account.'
)

@section('eyebrow', 'Email verification')

@section('headline')
    Verify your email address
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
        Please verify your email address to confirm your
        eConsent account and continue to your workspace.
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
                    href="{{ $verificationUrl }}"
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
                    Verify email address
                </a>
            </td>
        </tr>
    </table>

    <div
        style="
            margin:0 0 22px;
            padding:14px 16px;
            border-left:4px solid #0f766e;
            border-radius:8px;
            background:#f0fdfa;
            font-size:13px;
            line-height:1.6;
            color:#115e59;
        "
    >
        <strong>
            This verification link expires in
            {{ $expiresInMinutes }} minutes.
        </strong>
        For your security, use only the link sent to your
        registered email address.
    </div>

    <p
        style="
            margin:0 0 22px;
            font-size:13px;
            line-height:1.7;
            color:#64748b;
        "
    >
        If you did not create an eConsent account, you can
        safely ignore this email.
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
        {{ $verificationUrl }}
    </p>
@endsection
