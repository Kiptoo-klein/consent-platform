<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HTTPS and trusted hosts
    |--------------------------------------------------------------------------
    */

    'force_https' => env(
        'SECURITY_FORCE_HTTPS',
        false
    ),

    'allowed_hosts' => array_values(
        array_filter(
            array_map(
                'trim',
                explode(
                    ',',
                    (string) env(
                        'SECURITY_ALLOWED_HOSTS',
                        ''
                    )
                )
            )
        )
    ),

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | Supported values:
    | - off
    | - report-only
    | - enforce
    |
    | Report-only is the safe initial setting because it identifies
    | compatibility problems without blocking existing interface code.
    */

    'csp_mode' => env(
        'SECURITY_CSP_MODE',
        'report-only'
    ),

    'csp_policy' => implode(' ', [
        "default-src 'self';",
        "base-uri 'self';",
        "object-src 'none';",
        "frame-ancestors 'self';",
        "form-action 'self';",
        "img-src 'self' data: blob:;",
        "font-src 'self' data:;",
        "style-src 'self' 'unsafe-inline';",
        "script-src 'self' 'unsafe-inline';",
        "connect-src 'self';",
    ]),

    'hsts_max_age' => (int) env(
        'SECURITY_HSTS_MAX_AGE',
        31536000
    ),

    /*
    |--------------------------------------------------------------------------
    | Public route rate limits
    |--------------------------------------------------------------------------
    */

    'limits' => [
        'station_views_per_minute' => (int) env(
            'SECURITY_STATION_VIEWS_PER_MINUTE',
            180
        ),

        'station_actions_per_minute' => (int) env(
            'SECURITY_STATION_ACTIONS_PER_MINUTE',
            30
        ),

        'consent_views_per_minute' => (int) env(
            'SECURITY_CONSENT_VIEWS_PER_MINUTE',
            120
        ),

        'consent_updates_per_minute' => (int) env(
            'SECURITY_CONSENT_UPDATES_PER_MINUTE',
            30
        ),

        'signature_submissions_per_minute' => (int) env(
            'SECURITY_SIGNATURE_SUBMISSIONS_PER_MINUTE',
            5
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Signature protection
    |--------------------------------------------------------------------------
    */

    'signature' => [
        'max_bytes' => (int) env(
            'SECURITY_SIGNATURE_MAX_BYTES',
            1500000
        ),

        'max_dimension' => (int) env(
            'SECURITY_SIGNATURE_MAX_DIMENSION',
            4096
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Automated cleanup
    |--------------------------------------------------------------------------
    */

    'cleanup' => [
        'stale_kiosk_hours' => (int) env(
            'SECURITY_STALE_KIOSK_HOURS',
            24
        ),

        'failed_job_days' => (int) env(
            'SECURITY_FAILED_JOB_DAYS',
            30
        ),

        'database_session_days' => (int) env(
            'SECURITY_DATABASE_SESSION_DAYS',
            7
        ),
    ],
];
