<?php

namespace App\Notifications;

use App\Jobs\Middleware\EnforceEmailQuota;
use App\Models\EmailQuotaAttempt;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountRestorationRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1000;

    public int $maxExceptions = 3;

    public function __construct(
        public readonly string $requestType,
        public readonly string $requesterName,
        public readonly string $requesterEmail,
        public readonly string $organizationName,
        public readonly string $reviewUrl,
        public readonly string $requestedAt
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(
        object $notifiable
    ): array {
        return ['mail'];
    }

    public function toMail(
        object $notifiable
    ): MailMessage {
        $organizationRequest =
            $this->requestType
            === 'organization';

        return (new MailMessage())
            ->subject(
                $organizationRequest
                    ? 'Organization restoration requested: '
                        .$this->organizationName
                    : 'Account restoration requested: '
                        .$this->requesterName
            )
            ->view(
                'emails.account-restoration-requested',
                [
                    'reviewerName' =>
                        $notifiable->name,

                    'requestType' =>
                        $this->requestType,

                    'requesterName' =>
                        $this->requesterName,

                    'requesterEmail' =>
                        $this->requesterEmail,

                    'organizationName' =>
                        $this->organizationName,

                    'reviewUrl' =>
                        $this->reviewUrl,

                    'requestedAt' =>
                        $this->requestedAt,
                ]
            );
    }

    public function middleware(
        mixed $notifiable = null,
        ?string $channel = null
    ): array {
        return [
            new EnforceEmailQuota(
                category:
                    'account_restoration_request',
                priority:
                    EmailQuotaAttempt::
                        PRIORITY_CRITICAL
            ),
        ];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDays(7);
    }
}
