# Public Signing Station — Automatic Signed PDF Email

This feature automatically emails the stored signed PDF to a public signing-station signer after successful submission, but only when the signer entered a valid email address.

## Behaviour

- Runs after the existing `GenerateConsentPdfJob` successfully stores the PDF.
- Applies only to completed records with a `signing_station_id`.
- Skips records without a valid signer email.
- Attaches the same stored PDF used by the organization's Consent Records area.
- Sends once by default and logs status in `consent_pdf_deliveries`.
- Uses the platform's current Laravel `MAIL_*` settings as the sender.
- Retries failed delivery up to four times through the queue.
- Does not replace template, signer, evidence, sharing or WhatsApp views.
- Individual-consent invitation and reminder behaviour is unchanged.

## Installation

```bash
cd ~/Projects/consent-platform
bash ~/Downloads/install_public_station_signed_pdf_email.sh
php artisan migrate
```

Ensure the queue worker is running:

```bash
php artisan queue:work
```

## Safe local testing

```env
MAIL_MAILER=log
SIGNED_PDF_AUTO_EMAIL=true
```

Then clear configuration and watch the log:

```bash
php artisan config:clear
tail -f storage/logs/laravel.log
```

With the log mailer, the workflow is tested but nothing reaches a real inbox.

## Real delivery

Configure a real SMTP or transactional mail provider in `.env`. The visible sender comes from `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME`.

## Optional settings

```env
SIGNED_PDF_AUTO_EMAIL=true
SIGNED_PDF_EMAIL_SUBJECT_PREFIX="Your signed consent"
SIGNED_PDF_ATTACHMENT_SEARCH_MINUTES=120
SIGNED_PDF_PROCESSING_STALE_MINUTES=10
```

## Manual retry

```bash
php artisan consent:send-signed-pdf RECORD_ID
php artisan consent:send-signed-pdf RECORD_ID --force
```

`--force` sends another copy even when the delivery log already says it was sent.
