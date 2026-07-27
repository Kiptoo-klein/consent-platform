# Individual Consent UI Restore

Restores the two individual-consent pages that can be overwritten by older feature installers:

- `resources/views/consent-sessions/create.blade.php`
  - signer details
  - optional signing deadline date and time
- `resources/views/consent-sessions/show.blade.php`
  - Copy link
  - browser Share
  - Email signer
  - WhatsApp share
  - email-delivery and reminder history

The share panel is rendered for every direct individual-consent record. If the record has no access token, the page displays a visible diagnostic instead of silently hiding the panel.
