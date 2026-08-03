<?php

namespace App\Jobs;

use App\Jobs\Middleware\EnforceEmailQuota;
use App\Models\SubscriptionWorkflowNotification;
use App\Services\SubscriptionWorkflowNotificationService;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendSubscriptionWorkflowNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1000;

    public int $timeout = 120;

    public function __construct(
        public int $notificationId
    ) {
    }

    public function middleware(): array
    {
        return [
            new EnforceEmailQuota(
                category:
                    'subscription_workflow_notification',

                jobKey:
                    'subscription_workflow_notification:'
                    .$this->notificationId,

                priority:
                    \App\Models\EmailQuotaAttempt::
                        PRIORITY_CRITICAL
            ),
        ];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDays(40);
    }

    public function shouldConsumeEmailQuota(): bool
    {
        return SubscriptionWorkflowNotification::query()
            ->whereKey($this->notificationId)
            ->whereNotIn(
                'status',
                [
                    SubscriptionWorkflowNotification::
                        STATUS_SENT,

                    SubscriptionWorkflowNotification::
                        STATUS_FAILED,
                ]
            )
            ->exists();
    }

    public function handle(
        SubscriptionWorkflowNotificationService $service
    ): void {
        $service->deliverQueued(
            $this->notificationId
        );
    }

    public function failed(
        Throwable $exception
    ): void {
        SubscriptionWorkflowNotification::query()
            ->whereKey($this->notificationId)
            ->where(
                'status',
                '!=',
                SubscriptionWorkflowNotification::STATUS_SENT
            )
            ->update([
                'status' =>
                    SubscriptionWorkflowNotification::
                        STATUS_FAILED,

                'sent_at' => null,
                'failed_at' => now(),

                'error_message' =>
                    mb_substr(
                        $exception->getMessage(),
                        0,
                        2000
                    ),
            ]);
    }
}
