# Kiosk email identity and description

This feature adds three optional settings to public signing stations:

- **Sender display name** — displayed beside the platform's authenticated sender address.
- **Reply-to email** — signer replies are directed to this address.
- **Email description** — a short, non-promotional explanation included in the signed-PDF email.

The actual `From` email address remains the globally configured and authenticated Laravel mail address. This avoids email spoofing and avoids storing each organization's mailbox password.

## Install

```bash
cd ~/Projects/consent-platform
bash ~/Downloads/install_kiosk_email_identity.sh
php artisan migrate
php artisan queue:restart
```

During local development, keep `MAIL_MAILER=log`. Restart `php artisan queue:work` after installation.

## Delivery behaviour

The email includes:

- A descriptive transactional subject
- The signer and consent title
- The consent reference or record number
- Completion date and time
- The kiosk's optional description
- A statement that no further action is required
- A reminder that the email is transactional, not marketing

A clearer message improves trust, but inbox placement still depends on authenticated production email, including SPF, DKIM, DMARC and provider reputation.
