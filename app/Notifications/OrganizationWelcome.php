<?php

namespace App\Notifications;

use App\Jobs\Middleware\EnforceEmailQuota;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationWelcome extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1000;

    public int $maxExceptions = 3;

    public function __construct(
        public readonly string $organizationName
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(
        object $notifiable
    ): MailMessage {
        return (new MailMessage())
            ->subject('Welcome to eConsent')
            ->view(
                'emails.organization-welcome',
                [
                    'userName' => $notifiable->name,
                    'organizationName' =>
                        $this->organizationName,
                    'dashboardUrl' =>
                        route('dashboard'),
                ]
            );
    }

    public function middleware(
        mixed $notifiable = null,
        ?string $channel = null
    ): array {
        return [
            new EnforceEmailQuota(
                category: 'organization_welcome'
            ),
        ];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDays(7);
    }
}
