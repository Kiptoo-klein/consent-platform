<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Runtime heartbeats
    |--------------------------------------------------------------------------
    */

    'heartbeat' => [
        'directory' => env(
            'PRODUCTION_HEARTBEAT_DIRECTORY',
            'production-readiness'
        ),

        'scheduler_max_age_seconds' => (int) env(
            'PRODUCTION_SCHEDULER_MAX_AGE_SECONDS',
            180
        ),

        'queue_max_age_seconds' => (int) env(
            'PRODUCTION_QUEUE_MAX_AGE_SECONDS',
            180
        ),

        'queue_name' => env(
            'PRODUCTION_HEARTBEAT_QUEUE',
            'default'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Backups
    |--------------------------------------------------------------------------
    */

    'backups' => [
        'directory' => env(
            'PRODUCTION_BACKUP_DIRECTORY',
            'production-backups'
        ),

        'retention_days' => (int) env(
            'PRODUCTION_BACKUP_RETENTION_DAYS',
            14
        ),

        'retention_count' => (int) env(
            'PRODUCTION_BACKUP_RETENTION_COUNT',
            14
        ),

        'include_private_files' => (bool) env(
            'PRODUCTION_BACKUP_PRIVATE_FILES',
            true
        ),

        'warning_age_hours' => (int) env(
            'PRODUCTION_BACKUP_WARNING_AGE_HOURS',
            36
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logs and jobs
    |--------------------------------------------------------------------------
    */

    'cleanup' => [
        'log_retention_days' => (int) env(
            'PRODUCTION_LOG_RETENTION_DAYS',
            30
        ),

        'failed_job_retention_days' => (int) env(
            'PRODUCTION_FAILED_JOB_RETENTION_DAYS',
            30
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deployment requirements
    |--------------------------------------------------------------------------
    */

    'deployment' => [
        'minimum_php_version' => env(
            'PRODUCTION_MINIMUM_PHP_VERSION',
            '8.2.0'
        ),

        'minimum_free_disk_mb' => (int) env(
            'PRODUCTION_MINIMUM_FREE_DISK_MB',
            1024
        ),

        'maximum_queued_jobs_warning' => (int) env(
            'PRODUCTION_MAX_QUEUED_JOBS_WARNING',
            100
        ),
    ],
];
