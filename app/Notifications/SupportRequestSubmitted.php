<?php

namespace App\Notifications;

use App\Jobs\Middleware\EnforceEmailQuota;
use App\Models\EmailQuotaAttempt;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportRequestSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1000;

    public int $maxExceptions = 3;

    public function __construct(
        public readonly string $requestType,
        public readonly string $requesterName,
        public readonly string $requesterEmail,
        public readonly string $organizationName,
        public readonly string $messageBody,
        public readonly ?string $pageRoute,
        public readonly ?string $pagePath,
        public readonly ?string $helpContext,
        public readonly string $submittedAt
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
        $problem =
            $this->requestType
            === 'problem';

        return (new MailMessage())
            ->subject(
                $problem
                    ? 'Problem report: '
                        .$this->organizationName
                    : 'Support question: '
                        .$this->organizationName
            )
            ->replyTo(
                $this->requesterEmail,
                $this->requesterName
            )
            ->view(
                'emails.support-request-submitted',
                [
                    'requestType' =>
                        $this->requestType,

                    'requesterName' =>
                        $this->requesterName,

                    'requesterEmail' =>
                        $this->requesterEmail,

                    'organizationName' =>
                        $this->organizationName,

                    'messageBody' =>
                        $this->messageBody,

                    'pageRoute' =>
                        $this->pageRoute,

                    'pagePath' =>
                        $this->pagePath,

                    'helpContext' =>
                        $this->helpContext,

                    'submittedAt' =>
                        $this->submittedAt,
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
                    'support_request',
                priority:
                    EmailQuotaAttempt::
                        PRIORITY_NORMAL
            ),
        ];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDays(3);
    }
}
