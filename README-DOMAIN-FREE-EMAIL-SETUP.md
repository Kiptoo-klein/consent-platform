# Domain-Free Email Setup

This feature provides email testing before a branded domain is purchased.

## Features

- Email diagnostics dashboard.
- SMTP configuration checks.
- Immediate test email.
- Queued test email.
- Attachment delivery test.
- Password-safe configuration display.
- Artisan email test command.
- Clear domain-authentication deferral.

## Dashboard

Open:

`/settings/email-diagnostics`

## Command-line test

Immediate:

```bash
php artisan mail:diagnose your-email@example.com
```

Queued:

```bash
php artisan mail:diagnose your-email@example.com --queue
```

Without an attachment:

```bash
php artisan mail:diagnose your-email@example.com --no-attachment
```

## Domain-free SMTP

Configure the SMTP credentials supplied by the selected email provider.

The From address should remain the same as the authenticated account during testing.

## Later domain work

- Branded From address.
- Domain verification.
- SPF.
- DKIM.
- DMARC.
- Deliverability testing.
