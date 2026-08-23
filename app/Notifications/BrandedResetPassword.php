<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class BrandedResetPassword extends ResetPassword
{
    public function toMail($notifiable)
    {
        $resetUrl =
            $this->resetUrl($notifiable);

        $broker = (string) config(
            'auth.defaults.passwords',
            'users'
        );

        $expiresInMinutes = (int) config(
            "auth.passwords.{$broker}.expire",
            60
        );

        return (new MailMessage())
            ->subject('Reset your eConsent password')
            ->view(
                'emails.reset-password',
                [
                    'userName' => $notifiable->name,
                    'resetUrl' => $resetUrl,
                    'expiresInMinutes' =>
                        $expiresInMinutes,
                ]
            );
    }
}
