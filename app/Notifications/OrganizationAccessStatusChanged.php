<?php

namespace App\Notifications;

use App\Jobs\Middleware\EnforceEmailQuota;
use App\Models\EmailQuotaAttempt;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationAccessStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1000;

    public int $maxExceptions = 3;

    public function __construct(
        public readonly string $status,
        public readonly string $organizationName,
        public readonly string $performedByName,
        public readonly ?string $archiveReason,
        public readonly string $changedAt
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
        $archived =
            $this->status === 'archived';

        return (new MailMessage())
            ->subject(
                $archived
                    ? 'Organization archived: '
                        .$this->organizationName
                    : 'Organization restored: '
                        .$this->organizationName
            )
            ->view(
                'emails.organization-access-status-changed',
                [
                    'recipientName' =>
                        $notifiable->name,

                    'status' =>
                        $this->status,

                    'organizationName' =>
                        $this->organizationName,

                    'performedByName' =>
                        $this->performedByName,

                    'archiveReason' =>
                        $this->archiveReason,

                    'changedAt' =>
                        $this->changedAt,
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
                    'organization_access_status',
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
