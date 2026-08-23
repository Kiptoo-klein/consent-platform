<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >
    <meta
        name="color-scheme"
        content="light"
    >

    <title>
        @yield('title', 'eConsent')
    </title>
</head>

<body
    style="
        margin:0;
        padding:0;
        background:#f3f4f6;
        font-family:Arial,Helvetica,sans-serif;
        color:#111827;
    "
>
    <div
        style="
            display:none;
            max-height:0;
            overflow:hidden;
            opacity:0;
        "
    >
        @yield('preheader')
    </div>

    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        style="
            width:100%;
            background:#f3f4f6;
        "
    >
        <tr>
            <td
                align="center"
                style="padding:32px 12px;"
            >
                <table
                    role="presentation"
                    width="100%"
                    cellspacing="0"
                    cellpadding="0"
                    style="
                        width:100%;
                        max-width:620px;
                        background:#ffffff;
                        border:1px solid #e5e7eb;
                        border-radius:16px;
                        overflow:hidden;
                    "
                >
                    <tr>
                        <td
                            style="
                                height:5px;
                                background:#0f766e;
                                font-size:0;
                                line-height:0;
                            "
                        >
                            &nbsp;
                        </td>
                    </tr>

                    <tr>
                        <td
                            style="
                                padding:24px 30px;
                                background:#0f172a;
                                color:#ffffff;
                            "
                        >
                            <table
                                role="presentation"
                                width="100%"
                                cellspacing="0"
                                cellpadding="0"
                            >
                                <tr>
                                    <td valign="middle">
                                        <div
                                            style="
                                                font-size:18px;
                                                line-height:1.2;
                                                font-weight:700;
                                                color:#ffffff;
                                            "
                                        >
                                            eConsent
                                        </div>

                                        <div
                                            style="
                                                margin-top:5px;
                                                font-size:11px;
                                                line-height:1.4;
                                                letter-spacing:.09em;
                                                text-transform:uppercase;
                                                color:#99f6e4;
                                            "
                                        >
                                            Consent Management
                                        </div>
                                    </td>

                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td
                            style="
                                padding:32px 30px 28px;
                                background:#ffffff;
                            "
                        >
                            <div
                                style="
                                    margin:0 0 8px;
                                    font-size:12px;
                                    line-height:1.4;
                                    font-weight:700;
                                    letter-spacing:.08em;
                                    text-transform:uppercase;
                                    color:#0f766e;
                                "
                            >
                                @yield('eyebrow')
                            </div>

                            <h1
                                style="
                                    margin:0 0 22px;
                                    font-size:26px;
                                    line-height:1.3;
                                    font-weight:700;
                                    color:#111827;
                                "
                            >
                                @yield('headline')
                            </h1>

                            @yield('content')
                        </td>
                    </tr>

                    <tr>
                        <td
                            style="
                                padding:18px 30px;
                                border-top:1px solid #e5e7eb;
                                background:#f8fafc;
                                font-size:12px;
                                line-height:1.6;
                                color:#64748b;
                            "
                        >
                            This message was sent by eConsent.
                            Please keep secure links in this email private.
                        </td>
                    </tr>
                </table>

                <table
                    role="presentation"
                    width="100%"
                    cellspacing="0"
                    cellpadding="0"
                    style="
                        width:100%;
                        max-width:620px;
                    "
                >
                    <tr>
                        <td
                            align="center"
                            style="
                                padding:18px 20px 0;
                                font-size:11px;
                                line-height:1.6;
                                color:#94a3b8;
                            "
                        >
                            © {{ now()->year }} eConsent
                            &nbsp;•&nbsp;
                            Secure digital consent management
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
