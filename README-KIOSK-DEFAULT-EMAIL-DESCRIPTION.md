# Kiosk default email description

This add-on gives every public signing station a ready-to-use signed-PDF email description.

## Behaviour

- Create Kiosk shows the default text inside the Email description textarea.
- Edit Kiosk shows the saved custom text, or the default when no custom text exists.
- Organizations may keep, edit or replace the message.
- Older kiosks with a blank description use the default at send time.
- Multi-line custom descriptions retain their line breaks in the HTML email.

## Optional environment override

```env
KIOSK_DEFAULT_EMAIL_DESCRIPTION="Your preferred platform-wide default message"
```

After changing the environment value, run:

```bash
php artisan optimize:clear
```

No database migration is required.
