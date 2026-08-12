<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class CloudCostScheduleTest extends TestCase
{
    public function test_high_frequency_background_tasks_are_cost_optimized(): void
    {
        $events = collect(
            app(Schedule::class)->events()
        );

        $this->assertCommandExpression(
            $events,
            'consents:expire',
            '0 * * * *'
        );

        $this->assertCommandExpression(
            $events,
            'subscriptions:expire',
            '0 * * * *'
        );

        $this->assertCommandExpression(
            $events,
            'subscription-invoices:mark-overdue',
            '0 * * * *'
        );

        $this->assertCommandExpression(
            $events,
            'subscription-invoices:send-reminders',
            '0 * * * *'
        );

        $this->assertCommandExpression(
            $events,
            'consent:send-reminders',
            '0 * * * *'
        );

        $this->assertCommandExpression(
            $events,
            'production:heartbeat',
            '0 * * * *'
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

    public function test_hourly_heartbeat_has_cost_appropriate_health_window(): void
    {
        $this->assertSame(
            7200,
            config(
                'production-readiness.heartbeat.scheduler_max_age_seconds'
            )
        );

        $this->assertSame(
            7200,
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
