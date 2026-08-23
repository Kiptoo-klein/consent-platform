<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class BrandedVerifyEmail extends VerifyEmail
{
    public function toMail($notifiable)
    {
        $verificationUrl =
            $this->verificationUrl($notifiable);

        $expiresInMinutes = (int) config(
            'auth.verification.expire',
            60
        );

        return (new MailMessage())
            ->subject('Verify your email address')
            ->view(
                'emails.verify-email',
                [
                    'userName' => $notifiable->name,
                    'verificationUrl' =>
                        $verificationUrl,
                    'expiresInMinutes' =>
                        $expiresInMinutes,
                ]
            );
    }
}
