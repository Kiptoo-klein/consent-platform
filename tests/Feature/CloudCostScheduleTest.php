<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class CloudCostScheduleTest extends TestCase
{
    public function test_secondary_background_tasks_share_one_daily_window(): void
    {
        $events = collect(
            app(Schedule::class)->events()
        );

        $this->assertCommandExpression(
            $events,
            'consents:expire',
            '15 2 * * *'
        );

        $this->assertCommandExpression(
            $events,
            'subscriptions:expire',
            '15 2 * * *'
        );

        $this->assertCommandExpression(
            $events,
            'subscription-invoices:mark-overdue',
            '15 2 * * *'
        );

        $this->assertCommandExpression(
            $events,
            'subscription-invoices:send-reminders',
            '15 2 * * *'
        );

        $this->assertCommandExpression(
            $events,
            'consent:send-reminders',
            '15 2 * * *'
        );

        $this->assertCommandExpression(
            $events,
            'production:heartbeat',
            '15 2 * * *'
        );
    }

    public function test_ephemeral_local_backup_is_not_scheduled(): void
    {
        $events =
            collect(
                app(
                    \Illuminate\Console\Scheduling\Schedule::class
                )->events()
            );

        $this->assertFalse(
            $events->contains(
                fn ($event): bool =>
                    str_contains(
                        $event->command,
                        'production:backup'
                    )
            )
        );
    }

    public function test_daily_heartbeat_has_cost_appropriate_health_window(): void
    {
        $this->assertSame(
            90000,
            config(
                'production-readiness.heartbeat.scheduler_max_age_seconds'
            )
        );

        $this->assertSame(
            90000,
            config(
                'production-readiness.heartbeat.queue_max_age_seconds'
            )
        );
    }

    /**
     * @param \Illuminate\Support\Collection<int, mixed> $events
     */
    private function assertCommandExpression(
        $events,
        string $command,
        string $expression
    ): void {
        $event = $events->first(
            fn ($scheduledEvent): bool =>
                str_contains(
                    $scheduledEvent->command,
                    $command
                )
        );

        $this->assertNotNull(
            $event,
            "{$command} is not scheduled."
        );

        $this->assertSame(
            $expression,
            $event->expression
        );
    }
}
