<?php

namespace App\Mail;

use App\Models\ConsentSession;
use App\Services\ConsentPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ConsentCompletedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public ConsentSession $consentSession
    ) {
    }

    /**
     * Email subject.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Completed Consent Record'
        );
    }

    /**
     * Email body.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.consent-completed'
        );
    }

    /**
     * Attach the stored PDF.
     */
    public function attachments(): array
    {
        $pdfService = app(ConsentPdfService::class);

        $path = trim(
            (string) $this->consentSession->pdf_path
        );

        if ($path === '') {
            throw new RuntimeException(
                'The completed consent PDF path is missing.'
            );
        }

        try {
            $data = Storage::disk(
                $pdfService->diskName()
            )->get($path);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'The completed consent PDF could not be read for email delivery.',
                previous: $exception
            );
        }

        if (
            ! is_string($data)
            || ! str_starts_with($data, '%PDF')
        ) {
            throw new RuntimeException(
                'The completed consent PDF is missing or invalid for email delivery.'
            );
        }

        return [
            Attachment::fromData(
                fn (): string => $data,
                $pdfService->downloadFilename(
                    $this->consentSession
                )
            )->withMime(
                'application/pdf'
            ),
        ];
    }
}
