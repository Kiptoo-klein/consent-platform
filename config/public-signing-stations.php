<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public station inactivity timeout
    |--------------------------------------------------------------------------
    |
    | After a signer starts reviewing a kiosk consent, inactivity for this many
    | seconds returns the shared device to the station welcome page. Active
    | typing, touching, scrolling and signature drawing reset the timer.
    |
    */
    'inactivity_timeout_seconds' => (int) env(
        'PUBLIC_STATION_INACTIVITY_TIMEOUT_SECONDS',
        120
    ),
];
