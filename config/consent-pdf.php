<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Private consent PDF disk
    |--------------------------------------------------------------------------
    |
    | Local development continues using Laravel's private local disk.
    | Production should use a private Laravel Cloud Object Storage disk.
    |
    */
    'disk' => env(
        'CONSENT_PDF_DISK',
        'local'
    ),
];
