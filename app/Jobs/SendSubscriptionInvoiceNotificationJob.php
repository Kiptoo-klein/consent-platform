<?php

namespace App\Jobs;

use App\Jobs\Middleware\EnforceEmailQuota;
use App\Models\SubscriptionInvoiceNotification;
use App\Services\SubscriptionInvoiceNotificationService;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendSubscriptionInvoiceNotificationJob implements ShouldQueue
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
                    'subscription_invoice_notification',
                jobKey:
                    'subscription_invoice_notification:'
                    .$this->notificationId
            ),
        ];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDays(40);
    }

    public function shouldConsumeEmailQuota(): bool
    {
        return SubscriptionInvoiceNotification::query()
            ->whereKey($this->notificationId)
            ->whereNotIn(
                'status',
                [
                    SubscriptionInvoiceNotification::STATUS_SENT,
                    SubscriptionInvoiceNotification::STATUS_FAILED,
                ]
            )
            ->exists();
    }

    public function handle(
        SubscriptionInvoiceNotificationService $service
    ): void {
        $service->deliverQueued(
            $this->notificationId
        );
    }

    public function failed(
        Throwable $exception
    ): void {
        SubscriptionInvoiceNotification::query()
            ->whereKey($this->notificationId)
            ->where(
                'status',
                '!=',
                SubscriptionInvoiceNotification::STATUS_SENT
            )
            ->update([
                'status' =>
                    SubscriptionInvoiceNotification::STATUS_FAILED,
                'sent_at' => null,
                'failed_at' => now(),
                'error_message' => mb_substr(
                    $exception->getMessage(),
                    0,
                    2000
                ),
            ]);
    }
}
