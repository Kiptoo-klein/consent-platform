<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Automatic signed PDF delivery
    |--------------------------------------------------------------------------
    |
    | The feature uses the application's current Laravel mail configuration.
    | It only sends copies for completed records created by a public signing
    | station. Individual-consent invitation and reminder behaviour is separate.
    |
    */
    'enabled' => env('SIGNED_PDF_AUTO_EMAIL', true),

    'public_signing_stations_only' => true,

    'subject_prefix' => env(
        'SIGNED_PDF_EMAIL_SUBJECT_PREFIX',
        'Your signed consent'
    ),

    'attachment_search_minutes' => (int) env(
        'SIGNED_PDF_ATTACHMENT_SEARCH_MINUTES',
        120
    ),

    'processing_stale_minutes' => (int) env(
        'SIGNED_PDF_PROCESSING_STALE_MINUTES',
        10
    ),
];
