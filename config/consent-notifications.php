<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Automatic reminder days
    |--------------------------------------------------------------------------
    |
    | A reminder is sent once when a signing deadline enters each configured
    | day window. The defaults are three days and one day before expiry.
    |
    */
    'reminder_days' => array_values(
        array_filter(
            array_map(
                'intval',
                explode(
                    ',',
                    (string) env(
                        'CONSENT_REMINDER_DAYS',
                        '3,1'
                    )
                )
            )
        )
    ),

    'manual_cooldown_minutes' => (int) env(
        'CONSENT_EMAIL_COOLDOWN_MINUTES',
        5
    ),

    'automatic_retry_minutes' => (int) env(
        'CONSENT_EMAIL_RETRY_MINUTES',
        60
    ),
];
