<?php

namespace App\Services;

use App\Models\EmailQuotaAttempt;
use App\Models\EmailQuotaState;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class EmailQuotaService
{
    public function shouldQueue(): bool
    {
        if (! (bool) config(
            'email-quota.enabled',
            false
        )) {
            return false;
        }

        $limitedMailer = trim(
            (string) config(
                'email-quota.only_mailer',
                'resend'
            )
        );

        return $limitedMailer === ''
            || $limitedMailer === (string) config(
                'mail.default'
            );
    }

    /**
     * @return array{
     *     allowed:bool,
     *     attempt_id:int|null,
     *     retry_after:int,
     *     reason:string|null
     * }
     */
    public function reserve(
        string $category,
        ?string $jobKey = null,
        string $priority =
            EmailQuotaAttempt::PRIORITY_NORMAL
    ): array {
        if (! $this->shouldQueue()) {
            return [
                'allowed' => true,
                'attempt_id' => null,
                'retry_after' => 0,
                'reason' => null,
            ];
        }

        return DB::transaction(
            function () use (
                $category,
                $jobKey,
                $priority
            ): array {
                EmailQuotaState::query()
                    ->whereKey(1)
                    ->lockForUpdate()
                    ->firstOrFail();

                $now = CarbonImmutable::now();

                $dailyHours = max(
                    1,
                    (int) config(
                        'email-quota.daily_window_hours',
                        24
                    )
                );

                $monthlyDays = max(
                    1,
                    (int) config(
                        'email-quota.monthly_window_days',
                        31
                    )
                );

                $dailyLimit = max(
                    1,
                    (int) config(
                        'email-quota.daily_limit',
                        99
                    )
                );

                $monthlyLimit = max(
                    1,
                    (int) config(
                        'email-quota.monthly_limit',
                        2999
                    )
                );

                $critical =
                    $priority
                    === EmailQuotaAttempt::PRIORITY_CRITICAL;

                if (! $critical) {
                    $dailyLimit = max(
                        1,
                        $dailyLimit - max(
                            0,
                            (int) config(
                                'email-quota.critical_daily_reserve',
                                5
                            )
                        )
                    );

                    $monthlyLimit = max(
                        1,
                        $monthlyLimit - max(
                            0,
                            (int) config(
                                'email-quota.critical_monthly_reserve',
                                50
                            )
                        )
                    );
                }

                $perSecondLimit = max(
                    1,
                    (int) config(
                        'email-quota.per_second_limit',
                        8
                    )
                );

                $active = $this->activeQuery();

                $secondStart = $now->subSecond();
                $dailyStart = $now->subHours($dailyHours);
                $monthlyStart = $now->subDays($monthlyDays);

                $secondCount = (clone $active)
                    ->where(
                        'reserved_at',
                        '>=',
                        $secondStart
                    )
                    ->count();

                $dailyCount = (clone $active)
                    ->where(
                        'reserved_at',
                        '>=',
                        $dailyStart
                    )
                    ->count();

                $monthlyCount = (clone $active)
                    ->where(
                        'reserved_at',
                        '>=',
                        $monthlyStart
                    )
                    ->count();

                $retryAt = null;
                $reasons = [];

                if ($secondCount >= $perSecondLimit) {
                    $reasons[] = 'per_second';

                    $oldest = (clone $active)
                        ->where(
                            'reserved_at',
                            '>=',
                            $secondStart
                        )
                        ->oldest('reserved_at')
                        ->value('reserved_at');

                    if ($oldest !== null) {
                        $retryAt =
                            CarbonImmutable::parse(
                                $oldest
                            )->addSecond();
                    }
                }

                if ($dailyCount >= $dailyLimit) {
                    $reasons[] = 'daily';

                    $oldest = (clone $active)
                        ->where(
                            'reserved_at',
                            '>=',
                            $dailyStart
                        )
                        ->oldest('reserved_at')
                        ->value('reserved_at');

                    if ($oldest !== null) {
                        $candidate =
                            CarbonImmutable::parse(
                                $oldest
                            )->addHours(
                                $dailyHours
                            );

                        if (
                            $retryAt === null
                            || $candidate->greaterThan(
                                $retryAt
                            )
                        ) {
                            $retryAt = $candidate;
                        }
                    }
                }

                if ($monthlyCount >= $monthlyLimit) {
                    $reasons[] = 'monthly';

                    $oldest = (clone $active)
                        ->where(
                            'reserved_at',
                            '>=',
                            $monthlyStart
                        )
                        ->oldest('reserved_at')
                        ->value('reserved_at');

                    if ($oldest !== null) {
                        $candidate =
                            CarbonImmutable::parse(
                                $oldest
                            )->addDays(
                                $monthlyDays
                            );

                        if (
                            $retryAt === null
                            || $candidate->greaterThan(
                                $retryAt
                            )
                        ) {
                            $retryAt = $candidate;
                        }
                    }
                }

                if ($reasons !== []) {
                    $buffer = max(
                        1,
                        (int) config(
                            'email-quota.release_buffer_seconds',
                            5
                        )
                    );

                    $retryAfter = max(
                        1,
                        (int) ceil(
                            $now->diffInSeconds(
                                $retryAt
                                    ?? $now->addMinute(),
                                false
                            )
                        ) + $buffer
                    );

                    return [
                        'allowed' => false,
                        'attempt_id' => null,
                        'retry_after' => $retryAfter,
                        'reason' => implode(',', $reasons),
                    ];
                }

                $attempt =
                    EmailQuotaAttempt::query()
                        ->create([
                            'category' => mb_substr(
                                $category,
                                0,
                                80
                            ),

                            'job_key' =>
                                $jobKey === null
                                    ? null
                                    : mb_substr(
                                        $jobKey,
                                        0,
                                        190
                                    ),

                            'priority' => $priority,

                            'status' =>
                                EmailQuotaAttempt::STATUS_RESERVED,

                            'reserved_at' => $now,
                        ]);

                return [
                    'allowed' => true,
                    'attempt_id' => (int) $attempt->id,
                    'retry_after' => 0,
                    'reason' => null,
                ];
            },
            5
        );
    }

    public function markSent(?int $attemptId): void
    {
        if ($attemptId === null) {
            return;
        }

        EmailQuotaAttempt::query()
            ->whereKey($attemptId)
            ->where(
                'status',
                EmailQuotaAttempt::STATUS_RESERVED
            )
            ->update([
                'status' => EmailQuotaAttempt::STATUS_SENT,
                'sent_at' => now(),
                'failed_at' => null,
                'error_message' => null,
                'updated_at' => now(),
            ]);
    }

    public function markFailed(
        ?int $attemptId,
        Throwable $exception
    ): void {
        if ($attemptId === null) {
            return;
        }

        EmailQuotaAttempt::query()
            ->whereKey($attemptId)
            ->where(
                'status',
                EmailQuotaAttempt::STATUS_RESERVED
            )
            ->update([
                'status' => EmailQuotaAttempt::STATUS_FAILED,
                'sent_at' => null,
                'failed_at' => now(),
                'error_message' => mb_substr(
                    $exception->getMessage(),
                    0,
                    4000
                ),
                'updated_at' => now(),
            ]);
    }

    private function activeQuery(): Builder
    {
        return EmailQuotaAttempt::query()
            ->whereIn(
                'status',
                [
                    EmailQuotaAttempt::STATUS_RESERVED,
                    EmailQuotaAttempt::STATUS_SENT,
                ]
            );
    }
}
