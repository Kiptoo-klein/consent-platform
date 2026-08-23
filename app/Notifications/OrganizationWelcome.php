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
            ->greeting(
                'Welcome to eConsent, '
                .$notifiable->name
                .'!'
            )
            ->line(
                'Your '
                .$this->organizationName
                .' Free Evaluation workspace is ready.'
            )
            ->line(
                'The email you registered with is your initial '
                .'organization contact email and the default '
                .'reply-to address for new signing stations. '
                .'You can edit these settings later.'
            )
            ->line(
                'Use the Getting Started checklist to review '
                .'your starter templates, publish a template, '
                .'test a consent workflow and create a signing '
                .'station.'
            )
            ->action(
                'Open your eConsent dashboard',
                route('dashboard')
            )
            ->line(
                'There is no time limit on your Free Evaluation.'
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
