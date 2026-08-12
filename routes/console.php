<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
 * Keep scheduler mutexes off the database so Laravel Cloud's
 * schedule runner does not keep Serverless Postgres awake
 * solely for scheduler locks.
 *
 * Production currently runs a single App replica.
 */
Schedule::useCache('file');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('consents:expire')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('subscriptions:expire')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('subscription-invoices:mark-overdue')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('subscription-invoices:send-reminders')
    ->hourly()
    ->withoutOverlapping();

/* BEGIN CONSENT EMAIL REMINDERS */
\Illuminate\Support\Facades\Schedule::command(
    'consent:send-reminders'
)
    ->hourly()
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
    ->hourly()
    ->withoutOverlapping();

Schedule::command('production:prune')
    ->dailyAt('02:15')
    ->withoutOverlapping();

