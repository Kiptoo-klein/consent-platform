<?php

return [
    'enabled' => (bool) env(
        'EMAIL_QUOTA_ENABLED',
        false
    ),

    'only_mailer' => env(
        'EMAIL_QUOTA_ONLY_MAILER',
        'resend'
    ),

    'daily_limit' => max(
        1,
        (int) env('EMAIL_DAILY_LIMIT', 99)
    ),

    'daily_window_hours' => max(
        1,
        (int) env(
            'EMAIL_DAILY_WINDOW_HOURS',
            24
        )
    ),

    'monthly_limit' => max(
        1,
        (int) env(
            'EMAIL_MONTHLY_LIMIT',
            2999
        )
    ),

    'monthly_window_days' => max(
        1,
        (int) env(
            'EMAIL_MONTHLY_WINDOW_DAYS',
            31
        )
    ),

    'critical_daily_reserve' => max(
        0,
        (int) env(
            'EMAIL_CRITICAL_DAILY_RESERVE',
            5
        )
    ),

    'critical_monthly_reserve' => max(
        0,
        (int) env(
            'EMAIL_CRITICAL_MONTHLY_RESERVE',
            50
        )
    ),

    'per_second_limit' => max(
        1,
        (int) env(
            'EMAIL_PER_SECOND_LIMIT',
            8
        )
    ),

    'release_buffer_seconds' => max(
        1,
        (int) env(
            'EMAIL_QUOTA_RELEASE_BUFFER_SECONDS',
            5
        )
    ),

    'stale_reservation_minutes' => max(
        1,
        (int) env(
            'EMAIL_QUOTA_STALE_RESERVATION_MINUTES',
            10
        )
    ),
];
