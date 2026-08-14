<?php

namespace App\Jobs;

use App\Jobs\Middleware\EnforceEmailQuota;
use App\Mail\ConsentCompletedMail;
use App\Models\ConsentSession;
use App\Services\ConsentAuditService;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendCompletedConsentMailJob implements
    ShouldQueue,
    ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1000;

    public int $maxExceptions = 3;

    public int $timeout = 120;

    public int $uniqueFor = 3456000;

    public function __construct(
        public int $consentSessionId
    ) {
    }

    public function uniqueId(): string
    {
        return (string) $this->consentSessionId;
    }

    public function middleware(): array
    {
        return [
            new EnforceEmailQuota(
                category: 'completed_consent_pdf',
                jobKey:
                    'completed_consent_pdf:'
                    .$this->consentSessionId
            ),
        ];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDays(40);
    }

    public function failImmediatelyOnEmailDeliveryError(): bool
    {
        return true;
    }

    public function shouldConsumeEmailQuota(): bool
    {
        return ConsentSession::query()
            ->whereKey($this->consentSessionId)
            ->whereNotNull('signer_email')
            ->whereNull('pdf_emailed_at')
            ->exists();
    }

    public function handle(
        ConsentAuditService $auditService
    ): void {
        $session = ConsentSession::query()
            ->with([
                'organization',
                'consentTemplate',
                'consentTemplateVersion',
                'signature',
                'creator',
                'signingStation',
            ])
            ->find($this->consentSessionId);

        if (
            $session === null
            || ! $session->isCompleted()
            || blank($session->signer_email)
            || filled($session->pdf_emailed_at)
        ) {
            return;
        }

        Mail::to(
            $session->signer_email
        )->send(
            new ConsentCompletedMail(
                $session
            )
        );

        $session->forceFill([
            'pdf_emailed_at' => now(),
        ])->save();

        $alreadyRecorded =
            $session
                ->auditEvents()
                ->where(
                    'event_type',
                    'consent.pdf_emailed'
                )
                ->exists();

        if (! $alreadyRecorded) {
            $auditService->record(
                consentSession: $session,
                eventType: 'consent.pdf_emailed',
                description:
                    'The completed consent PDF was processed for email delivery.',
                metadata: [
                    'mailer' => config('mail.default'),
                    'pdf_emailed_at' =>
                        (string) $session->pdf_emailed_at,
                ]
            );
        }
    }

    public function failed(
        Throwable $exception
    ): void {
        report($exception);
    }
}
