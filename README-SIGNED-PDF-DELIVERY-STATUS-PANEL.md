# Signed PDF Delivery Status Panel

Adds delivery tracking to organization consent-record pages for public signing-station records.

## Included

- PDF generation status based on `pdf_path` and `pdf_generated_at`
- Recipient email
- Email state: not started, waiting, queued, processing, sent, skipped, failed
- Attempt count and timestamps
- Last delivery error
- Retry / send-another-copy action
- Organization ownership checks
- Safe behaviour when the delivery migration has not been run

## Requirements

Install the Public Signing Station Signed PDF Email feature first.

The queue worker must be running for queued retries:

```bash
php artisan queue:work
```

No new migration is included in this panel feature.
