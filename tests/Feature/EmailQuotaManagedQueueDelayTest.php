<?php

namespace Tests\Feature;

use App\Jobs\Middleware\EnforceEmailQuota;
use App\Services\EmailQuotaService;
use Mockery;
use Tests\TestCase;

class EmailQuotaManagedQueueDelayTest extends TestCase
{
    public function test_long_quota_delay_is_capped_at_fifteen_minutes(): void
    {
        $service = Mockery::mock(
            EmailQuotaService::class
        );

        $service
            ->shouldReceive('reserve')
            ->once()
            ->andReturn([
                'allowed' => false,
                'attempt_id' => null,
                'retry_after' => 7200,
                'reason' => 'daily',
            ]);

        $this->app->instance(
            EmailQuotaService::class,
            $service
        );

        $job = new class
        {
            public ?int $releasedFor = null;

            public function release(
                int $seconds
            ): void {
                $this->releasedFor = $seconds;
            }
        };

        $nextWasCalled = false;

        (new EnforceEmailQuota(
            category: 'managed_queue_test'
        ))->handle(
            $job,
            function () use (
                &$nextWasCalled
            ): void {
                $nextWasCalled = true;
            }
        );

        $this->assertSame(
            900,
            $job->releasedFor
        );

        $this->assertFalse(
            $nextWasCalled
        );
    }

    public function test_short_quota_delay_is_preserved(): void
    {
        $service = Mockery::mock(
            EmailQuotaService::class
        );

        $service
            ->shouldReceive('reserve')
            ->once()
            ->andReturn([
                'allowed' => false,
                'attempt_id' => null,
                'retry_after' => 12,
                'reason' => 'per_second',
            ]);

        $this->app->instance(
            EmailQuotaService::class,
            $service
        );

        $job = new class
        {
            public ?int $releasedFor = null;

            public function release(
                int $seconds
            ): void {
                $this->releasedFor = $seconds;
            }
        };

        (new EnforceEmailQuota(
            category: 'managed_queue_test'
        ))->handle(
            $job,
            static function (): void {
                throw new \RuntimeException(
                    'The next middleware callback should not run.'
                );
            }
        );

        $this->assertSame(
            12,
            $job->releasedFor
        );
    }
}
