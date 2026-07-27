<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>Your Completed Consent Record</title>
</head>

<body style="margin: 0; padding: 0; background-color: #f3f4f6;">
    @php
        $organizationName =
            $consentSession->organization->name
            ?? 'the organization';

        $consentTitle =
            $consentSession->consentTemplateVersion->title
            ?? $consentSession->consentTemplate->title
            ?? 'Consent Form';
    @endphp

    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        style="background-color: #f3f4f6; padding: 24px;"
    >
        <tr>
            <td align="center">
                <table
                    role="presentation"
                    width="100%"
                    cellspacing="0"
                    cellpadding="0"
                    style="
                        max-width: 620px;
                        background-color: #ffffff;
                        border-radius: 8px;
                        padding: 32px;
                        font-family: Arial, sans-serif;
                        color: #1f2937;
                    "
                >
                    <tr>
                        <td>
                            <h1
                                style="
                                    margin: 0 0 20px;
                                    font-size: 22px;
                                    line-height: 1.3;
                                "
                            >
                                Your completed consent record
                            </h1>

                            <p style="margin: 0 0 16px;">
                                Hello {{ $consentSession->signer_name }},
                            </p>

                            <p style="margin: 0 0 16px; line-height: 1.6;">
                                Your consent record for
                                <strong>{{ $consentTitle }}</strong>
                                has been completed successfully.
                            </p>

                            <p style="margin: 0 0 16px; line-height: 1.6;">
                                A PDF copy of your completed consent record
                                is attached to this email for your records.
                            </p>

                            <table
                                role="presentation"
                                width="100%"
                                cellspacing="0"
                                cellpadding="0"
                                style="
                                    margin: 24px 0;
                                    border-collapse: collapse;
                                "
                            >
                                <tr>
                                    <td
                                        style="
                                            padding: 10px;
                                            border: 1px solid #d1d5db;
                                            font-weight: bold;
                                            width: 38%;
                                        "
                                    >
                                        Organization
                                    </td>

                                    <td
                                        style="
                                            padding: 10px;
                                            border: 1px solid #d1d5db;
                                        "
                                    >
                                        {{ $organizationName }}
                                    </td>
                                </tr>

                                <tr>
                                    <td
                                        style="
                                            padding: 10px;
                                            border: 1px solid #d1d5db;
                                            font-weight: bold;
                                        "
                                    >
                                        Consent record
                                    </td>

                                    <td
                                        style="
                                            padding: 10px;
                                            border: 1px solid #d1d5db;
                                        "
                                    >
                                        {{ $consentTitle }}
                                    </td>
                                </tr>

                                <tr>
                                    <td
                                        style="
                                            padding: 10px;
                                            border: 1px solid #d1d5db;
                                            font-weight: bold;
                                        "
                                    >
                                        Completed
                                    </td>

                                    <td
                                        style="
                                            padding: 10px;
                                            border: 1px solid #d1d5db;
                                        "
                                    >
                                        {{ optional($consentSession->completed_at)
                                            ->format('j F Y, g:i A') }}
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 16px; line-height: 1.6;">
                                Please keep the attached PDF in a secure place.
                            </p>

                            <p style="margin: 24px 0 0;">
                                Regards,<br>
                                {{ $organizationName }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
