<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrganizationNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Organization $organization
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject:
                'New organization created: '
                .$this->organization->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-organization-notification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
