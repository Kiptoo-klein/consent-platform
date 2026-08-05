<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Consent Platform Email Delivery Test</title>
</head>
<body style="margin: 0; padding: 24px; background: #f4f7f6; font-family: Arial, sans-serif; color: #1f2937;">
    <div style="max-width: 640px; margin: 0 auto; padding: 32px; background: #ffffff; border-radius: 12px;">
        <h1 style="margin: 0 0 18px; font-size: 24px;">
            Email configuration test
        </h1>

        <p style="margin: 0 0 18px; line-height: 1.6;">
            This email confirms that outbound email delivery is
            configured correctly for {{ config('app.name') }}.
        </p>

        <table
            role="presentation"
            style="width: 100%; border-collapse: collapse; margin: 0 0 18px;"
        >
            <tr>
                <td style="padding: 8px 0; font-weight: bold;">
                    Mailer
                </td>

                <td style="padding: 8px 0;">
                    {{ $mailerName }}
                </td>
            </tr>

            <tr>
                <td style="padding: 8px 0; font-weight: bold;">
                    Delivery mode
                </td>

                <td style="padding: 8px 0;">
                    {{ $deliveryMode }}
                </td>
            </tr>

            <tr>
                <td style="padding: 8px 0; font-weight: bold;">
                    Requested by
                </td>

                <td style="padding: 8px 0;">
                    {{ $requestedBy }}
                </td>
            </tr>

            <tr>
                <td style="padding: 8px 0; font-weight: bold;">
                    Generated at
                </td>

                <td style="padding: 8px 0;">
                    {{ $sentAt->toDayDateTimeString() }}
                </td>
            </tr>
        </table>

        <p style="margin: 0; line-height: 1.6; color: #6b7280;">
            No action is required.
        </p>
    </div>
</body>
</html>
