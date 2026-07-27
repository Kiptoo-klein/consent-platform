<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailConfigurationTestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $requestedBy,
        public readonly string $deliveryMode,
        public readonly string $mailerName,
        public readonly bool $includeAttachment = true,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Consent Platform Email Delivery Test',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-configuration-test',
            with: [
                'sentAt' => now(),
            ],
        );
    }

    public function attachments(): array
    {
        if (! $this->includeAttachment) {
            return [];
        }

        return [
            Attachment::fromData(
                fn (): string => implode("\n", [
                    'Consent Platform Email Delivery Test',
                    '',
                    'Result: The test attachment was generated successfully.',
                    'Mailer: '.$this->mailerName,
                    'Delivery mode: '.$this->deliveryMode,
                    'Requested by: '.$this->requestedBy,
                    'Generated at: '.now()->toDateTimeString(),
                ]),
                'consent-platform-email-test.txt'
            )->withMime('text/plain'),
        ];
    }
}
