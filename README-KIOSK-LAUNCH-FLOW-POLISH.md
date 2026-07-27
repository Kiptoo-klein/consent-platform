# Kiosk launch flow polish

This feature corrects and consolidates the public signing-station workflow.

## Kiosk create and edit

Both pages now show:

- Sender display name
- Reply-to email
- Email description
- Return-to-station delay, defaulting to 3 seconds and adjustable from 3 to 300 seconds

The authenticated From address continues to come from `MAIL_FROM_ADDRESS`. The kiosk sender name controls the display name, and signer replies go to the kiosk reply-to email.

## Signer details

- Signer name remains the first standard field.
- Signer email remains the second standard field.
- Only fields selected in the published template appear after email.
- Optional selected fields remain optional; required selected fields remain required.
- Selected fields use the same card and spacing as name and email.
- No empty Additional Information section is shown.
- The old standalone Reference Number kiosk option is removed. Add a reference field through the template builder when a particular consent needs it.

## Completion reset

New kiosks default to returning to the station after 3 seconds. The delay remains adjustable between 3 and 300 seconds.

## Deadline dependency

Deadline time starts blank and disabled until a deadline date is selected. Selecting a date sets the initial time to `00:00`, which can then be changed. Clearing the date clears and disables the time.

Server-side validation also rejects a submitted deadline time when no deadline date was supplied.

## Installation

Run the installer from the Laravel project root, then run:

```bash
php artisan migrate
php artisan queue:restart
php artisan optimize:clear
```
