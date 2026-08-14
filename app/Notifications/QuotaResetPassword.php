<?php

namespace App\Notifications;

use App\Jobs\Middleware\EnforceEmailQuota;
use App\Models\EmailQuotaAttempt;
use DateTimeInterface;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class QuotaResetPassword extends ResetPassword implements ShouldQueue
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
                category: 'auth_password_reset',
                priority:
                    EmailQuotaAttempt::PRIORITY_CRITICAL
            ),
        ];
    }

    public function retryUntil(): DateTimeInterface
    {
        $broker = (string) config(
            'auth.defaults.passwords',
            'users'
        );

        $minutes = max(
            5,
            (int) config(
                "auth.passwords.{$broker}.expire",
                60
            ) - 1
        );

        return now()->addMinutes($minutes);
    }
}
