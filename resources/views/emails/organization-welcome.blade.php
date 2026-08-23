@extends('emails.layouts.transactional')

@section('title', 'Welcome to eConsent')

@section(
    'preheader',
    'Your eConsent Free Evaluation workspace is ready.'
)

@section('eyebrow', 'Welcome to eConsent')

@section('headline')
    Welcome, {{ $userName }}!
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
        Your
        <strong style="color:#111827;">
            {{ $organizationName }}
        </strong>
        Free Evaluation workspace is ready.
    </p>

    <div
        style="
            margin:22px 0;
            padding:18px;
            border:1px solid #ccfbf1;
            border-radius:12px;
            background:#f0fdfa;
        "
    >
        <div
            style="
                margin-bottom:7px;
                font-size:12px;
                line-height:1.4;
                font-weight:700;
                letter-spacing:.08em;
                text-transform:uppercase;
                color:#0f766e;
            "
        >
            Your workspace
        </div>

        <div
            style="
                font-size:14px;
                line-height:1.7;
                color:#334155;
            "
        >
            The email you registered with is your initial
            organization contact email and the default reply-to
            address for new signing stations. You can change
            these settings later.
        </div>
    </div>

    <p
        style="
            margin:0 0 20px;
            font-size:15px;
            line-height:1.7;
            color:#374151;
        "
    >
        Use the Getting Started checklist to review your starter
        templates, publish a template, test a consent workflow,
        and create your first signing station.
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
                    href="{{ $dashboardUrl }}"
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
                    Open your eConsent dashboard
                </a>
            </td>
        </tr>
    </table>

    <div
        style="
            margin:0 0 22px;
            padding:14px 16px;
            border-left:4px solid #4f46e5;
            border-radius:8px;
            background:#eef2ff;
            font-size:14px;
            line-height:1.6;
            color:#3730a3;
        "
    >
        <strong>No time limit.</strong>
        Your Free Evaluation remains available while you explore
        the core eConsent workflow.
    </div>

    <p
        style="
            margin:0 0 7px;
            font-size:12px;
            line-height:1.6;
            color:#64748b;
        "
    >
        If the button does not open, copy this address into your
        browser:
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
        {{ $dashboardUrl }}
    </p>
@endsection
