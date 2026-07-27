<?php

namespace App\Mail;

use App\Models\ConsentSession;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConsentSigningRequestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public ConsentSession $consentSession,
        public string $mailSubject,
        public string $introMessage,
        public string $notificationType
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject($this->mailSubject)
            ->view('emails.consent-signing-request')
            ->with([
                'signingUrl' => route(
                    'public-consent.show',
                    [
                        'accessToken' =>
                            $this->consentSession->access_token,
                    ]
                ),
            ]);
    }
}
