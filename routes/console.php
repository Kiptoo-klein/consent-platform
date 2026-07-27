<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('consents:expire')
    ->everyMinute()
    ->withoutOverlapping();

/* BEGIN CONSENT EMAIL REMINDERS */
\Illuminate\Support\Facades\Schedule::command(
    'consent:send-reminders'
)
    ->everyTenMinutes()
    ->withoutOverlapping();
/* END CONSENT EMAIL REMINDERS */


// SECURITY_HARDENING_CLEANUP_SCHEDULE
Schedule::command(
    'security:cleanup --no-interaction'
)
    ->dailyAt('02:15')
    ->withoutOverlapping();


// PRODUCTION_READINESS_SCHEDULE
Schedule::command('production:heartbeat')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('production:backup --prune')
    ->dailyAt('01:30')
    ->withoutOverlapping(180);

Schedule::command('production:prune')
    ->dailyAt('02:15')
    ->withoutOverlapping();

