<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Organization branding disk
    |--------------------------------------------------------------------------
    |
    | Local development keeps using Laravel's public disk. Production may
    | point this to a public Laravel Cloud Object Storage disk, for example
    | "branding".
    |
    */
    'disk' => env(
        'ORGANIZATION_BRANDING_DISK',
        'public'
    ),
];
