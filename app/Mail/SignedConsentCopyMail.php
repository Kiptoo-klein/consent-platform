<?php

namespace App\Mail;

use App\Models\ConsentSession;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SignedConsentCopyMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public ConsentSession $consentSession,
        public string $pdfData,
        public string $attachmentName
    ) {
    }

    public function build(): self
    {
        $this->consentSession->loadMissing([
            'organization',
            'consentTemplate',
            'signingStation',
        ]);

        $station = $this->consentSession->signingStation;
        $organization = $this->consentSession->organization;

        $title = $this->consentSession->consentTemplate?->title
            ?? 'Consent form';

        $senderName = trim((string) (
            $station?->sender_name
            ?: $organization?->name
            ?: config('mail.from.name')
            ?: config('app.name')
        ));

        $mail = $this
            ->subject('Signed consent copy - '.$title.' | '.$senderName)
            ->view('emails.signed-consent-copy')
            ->with([
                'senderDisplayName' => $senderName,
                'stationEmailDescription' => trim((string) (
                    $station?->email_description
                    ?: config('kiosk-email.default_description')
                )),
            ])
            ->attachData(
                $this->pdfData,
                $this->attachmentName,
                ['mime' => 'application/pdf']
            );

        $fromAddress = config('mail.from.address');

        if (
            is_string($fromAddress)
            && filter_var($fromAddress, FILTER_VALIDATE_EMAIL)
        ) {
            $mail->from($fromAddress, $senderName);
        }

        $replyTo = collect([
            $station?->reply_to_email,
            data_get($organization, 'email'),
            data_get($organization, 'contact_email'),
            data_get($organization, 'support_email'),
        ])->first(fn ($email): bool => is_string($email)
            && filter_var($email, FILTER_VALIDATE_EMAIL));

        if ($replyTo) {
            $mail->replyTo($replyTo, $senderName);
        }

        return $mail;
    }
}
