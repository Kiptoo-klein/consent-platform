# Consent Email Delivery and Reminders

This package extends the existing individual-consent workflow with:

- automatic signing email immediately after record creation;
- manual **Send signing email**, **Send email again**, and **Send reminder** actions;
- scheduled reminders three days and one day before the signing deadline;
- automatic stopping for completed, cancelled, expired, and signing-station records;
- delivery status history: processing, sent, and failed;
- failure details without cancelling consent creation;
- duplicate protection and manual-email cooldown;
- a dry-run reminder command.

## Install

Run the installer from the Laravel project root, then migrate:

```bash
bash ~/Downloads/install_consent_email_reminders.sh
php artisan migrate
```

## Configure email

For safe local testing, keep:

```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS="consent@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Emails will be written to `storage/logs/laravel.log`.

For real delivery, configure a supported SMTP or API mail provider in `.env`, for example:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="consent@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Then clear cached configuration:

```bash
php artisan config:clear
```

## Reminder schedule

The installer adds this schedule to `routes/console.php`:

```php
Schedule::command('consent:send-reminders')
    ->everyTenMinutes()
    ->withoutOverlapping();
```

The server must run Laravel's scheduler every minute:

```cron
* * * * * cd /path/to/consent-platform && php artisan schedule:run >> /dev/null 2>&1
```

For local development, run:

```bash
php artisan schedule:work
```

## Test reminders

Preview due reminders without sending:

```bash
php artisan consent:send-reminders --dry-run
```

Send due reminders now:

```bash
php artisan consent:send-reminders
```

## Optional settings

```env
CONSENT_REMINDER_DAYS=3,1
CONSENT_EMAIL_COOLDOWN_MINUTES=5
CONSENT_EMAIL_RETRY_MINUTES=60
```

After changing them:

```bash
php artisan config:clear
```

## Important behavior

- Automatic email is attempted only when an individual consent has a signer email.
- A failed email does not roll back or delete the consent record.
- Automatic reminders require a future signing deadline.
- Manual reminders may be sent before the deadline even when no automatic reminder is currently due.
- Notification attempts remain visible on the Consent Evidence page.
