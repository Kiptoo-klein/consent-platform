<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Consent template asset storage
    |--------------------------------------------------------------------------
    |
    | Production should use a public, persistent object-storage disk. Local
    | development may use Laravel's public disk together with storage:link.
    |
    */

    'disk' => env(
        'CONSENT_TEMPLATE_ASSETS_DISK',
        'public'
    ),

    'max_image_bytes' => 5 * 1024 * 1024,

    'max_import_images' => 20,

    'max_import_image_bytes' => 20 * 1024 * 1024,
];
