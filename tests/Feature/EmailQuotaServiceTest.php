<?php

namespace Tests\Feature;

use App\Models\EmailQuotaAttempt;
use App\Services\EmailQuotaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class EmailQuotaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'email-quota.enabled' => true,
            'email-quota.only_mailer' => '',
            'email-quota.daily_limit' => 3,
            'email-quota.daily_window_hours' => 24,
            'email-quota.monthly_limit' => 10,
            'email-quota.monthly_window_days' => 31,
            'email-quota.critical_daily_reserve' => 1,
            'email-quota.critical_monthly_reserve' => 1,
            'email-quota.per_second_limit' => 100,
            'email-quota.release_buffer_seconds' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_normal_mail_stops_before_reserved_critical_capacity(): void
    {
        $service = app(EmailQuotaService::class);

        $first = $service->reserve('normal-one');
        $second = $service->reserve('normal-two');
        $blocked = $service->reserve('normal-three');

        $critical = $service->reserve(
            category: 'password-reset',
            priority:
                EmailQuotaAttempt::PRIORITY_CRITICAL
        );

        $this->assertTrue($first['allowed']);
        $this->assertTrue($second['allowed']);
        $this->assertFalse($blocked['allowed']);
        $this->assertStringContainsString(
            'daily',
            (string) $blocked['reason']
        );
        $this->assertTrue($critical['allowed']);
    }

    public function test_daily_capacity_reopens_after_rolling_window(): void
    {
        Carbon::setTestNow(
            '2026-08-02 12:00:00'
        );

        $service = app(EmailQuotaService::class);

        for ($index = 1; $index <= 3; $index++) {
            $reservation = $service->reserve(
                category: "critical-{$index}",
                priority:
                    EmailQuotaAttempt::PRIORITY_CRITICAL
            );

            $this->assertTrue(
                $reservation['allowed']
            );
        }

        $this->assertFalse(
            $service->reserve(
                category: 'critical-four',
                priority:
                    EmailQuotaAttempt::PRIORITY_CRITICAL
            )['allowed']
        );

        Carbon::setTestNow(
            '2026-08-03 12:00:02'
        );

        $this->assertTrue(
            $service->reserve(
                category: 'critical-five',
                priority:
                    EmailQuotaAttempt::PRIORITY_CRITICAL
            )['allowed']
        );
    }

    public function test_failed_attempt_no_longer_consumes_capacity(): void
    {
        $service = app(EmailQuotaService::class);

        $reservation = $service->reserve(
            'normal-one'
        );

        $service->markFailed(
            $reservation['attempt_id'],
            new RuntimeException(
                'Simulated transport failure.'
            )
        );

        $this->assertDatabaseHas(
            'email_quota_attempts',
            [
                'id' => $reservation['attempt_id'],
                'status' =>
                    EmailQuotaAttempt::STATUS_FAILED,
            ]
        );

        $this->assertTrue(
            $service->reserve(
                'normal-two'
            )['allowed']
        );
    }

    public function test_stale_reserved_attempt_is_recovered_before_quota_counting(): void
    {
        Carbon::setTestNow(
            '2026-08-15 10:00:00'
        );

        config([
            'email-quota.daily_limit' => 1,
            'email-quota.monthly_limit' => 1,
            'email-quota.critical_daily_reserve' => 0,
            'email-quota.critical_monthly_reserve' => 0,
            'email-quota.stale_reservation_minutes' => 10,
        ]);

        $service = app(
            EmailQuotaService::class
        );

        $stale = $service->reserve(
            'stale-reservation'
        );

        $this->assertTrue(
            $stale['allowed']
        );

        EmailQuotaAttempt::query()
            ->whereKey(
                $stale['attempt_id']
            )
            ->update([
                'reserved_at' =>
                    now()->subMinutes(11),
            ]);

        $replacement = $service->reserve(
            'replacement-reservation'
        );

        $this->assertTrue(
            $replacement['allowed']
        );

        $this->assertDatabaseHas(
            'email_quota_attempts',
            [
                'id' =>
                    $stale['attempt_id'],
                'status' =>
                    EmailQuotaAttempt::
                        STATUS_FAILED,
                'error_message' =>
                    'Stale email quota reservation recovered automatically.',
            ]
        );

        $this->assertNotNull(
            EmailQuotaAttempt::query()
                ->findOrFail(
                    $stale['attempt_id']
                )
                ->failed_at
        );
    }

    public function test_recent_reserved_attempt_is_not_recovered(): void
    {
        Carbon::setTestNow(
            '2026-08-15 10:00:00'
        );

        config([
            'email-quota.daily_limit' => 1,
            'email-quota.monthly_limit' => 1,
            'email-quota.critical_daily_reserve' => 0,
            'email-quota.critical_monthly_reserve' => 0,
            'email-quota.stale_reservation_minutes' => 10,
        ]);

        $service = app(
            EmailQuotaService::class
        );

        $recent = $service->reserve(
            'recent-reservation'
        );

        $this->assertTrue(
            $recent['allowed']
        );

        EmailQuotaAttempt::query()
            ->whereKey(
                $recent['attempt_id']
            )
            ->update([
                'reserved_at' =>
                    now()->subMinutes(5),
            ]);

        $blocked = $service->reserve(
            'replacement-reservation'
        );

        $this->assertFalse(
            $blocked['allowed']
        );

        $this->assertStringContainsString(
            'daily',
            (string) $blocked['reason']
        );

        $this->assertDatabaseHas(
            'email_quota_attempts',
            [
                'id' =>
                    $recent['attempt_id'],
                'status' =>
                    EmailQuotaAttempt::
                        STATUS_RESERVED,
            ]
        );

        $this->assertNull(
            EmailQuotaAttempt::query()
                ->findOrFail(
                    $recent['attempt_id']
                )
                ->failed_at
        );
    }

}
