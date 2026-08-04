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

        return [
            Attachment::fromStorageDisk(
                $pdfService->diskName(),
                $this->consentSession->pdf_path
            )->as(
                $pdfService->downloadFilename(
                    $this->consentSession
                )
            )->withMime(
                'application/pdf'
            ),
        ];
    }
}
