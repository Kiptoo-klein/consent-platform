<?php

namespace App\Notifications;

use App\Jobs\Middleware\EnforceEmailQuota;
use App\Models\EmailQuotaAttempt;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationUserInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1000;

    public int $maxExceptions = 3;

    public function __construct(
        public readonly string $token,
        public readonly string $organizationName,
        public readonly string $roleName
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
        $url = route(
            'organization-invitations.accept',
            [
                'token' => $this->token,
                'email' => $notifiable->email,
            ]
        );

        return (new MailMessage())
            ->subject(
                'You have been invited to '
                .$this->organizationName
                .' on eConsent'
            )
            ->greeting(
                'Hello '.$notifiable->name
            )
            ->line(
                'You have been invited to join '
                .$this->organizationName
                .' on eConsent.'
            )
            ->line(
                'Name: '.$notifiable->name
            )
            ->line(
                'Role: '.$this->roleName
            )
            ->line(
                'Email: '.$notifiable->email
            )
            ->line(
                'Complete your account setup by choosing '
                .'your own password.'
            )
            ->action(
                'Complete account setup',
                $url
            )
            ->line(
                'This invitation expires after seven days.'
            );
    }

    public function middleware(
        mixed $notifiable = null,
        ?string $channel = null
    ): array {
        return [
            new EnforceEmailQuota(
                category: 'organization_user_invitation',
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
