<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default signed-consent email description
    |--------------------------------------------------------------------------
    |
    | This text is pre-filled when an organization creates or edits a public
    | signing station. The organization may keep it or replace it. It is also
    | used as the send-time fallback for older kiosks with no saved message.
    |
    */
    'default_description' => env(
        'KIOSK_DEFAULT_EMAIL_DESCRIPTION',
        'This email contains the official signed copy of the consent you completed. Please keep the attached PDF for your records. No further action is required unless the organization contacts you.'
    ),
];
