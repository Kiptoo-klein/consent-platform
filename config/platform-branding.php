<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform branding disk
    |--------------------------------------------------------------------------
    |
    | Local development uses Laravel's public disk. Production may point this
    | to a public object-storage disk, such as a Laravel Cloud branding bucket.
    |
    */
    'disk' => env(
        'PLATFORM_BRANDING_DISK',
        'public'
    ),
];
