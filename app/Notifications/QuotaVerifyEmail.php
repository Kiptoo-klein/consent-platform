<?php

namespace App\Notifications;

use App\Jobs\Middleware\EnforceEmailQuota;
use App\Models\EmailQuotaAttempt;
use DateTimeInterface;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class QuotaVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 1000;

    public int $maxExceptions = 3;

    public function middleware(
        mixed $notifiable = null,
        ?string $channel = null
    ): array {
        return [
            new EnforceEmailQuota(
                category: 'auth_verification',
                priority:
                    EmailQuotaAttempt::PRIORITY_CRITICAL
            ),
        ];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDays(7);
    }
}
