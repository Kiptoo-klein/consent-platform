<?php

namespace App\Jobs;

use App\Mail\ConsentCompletedMail;
use App\Models\ConsentSession;
use App\Services\ConsentAuditService;
use App\Services\ConsentPdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class GenerateConsentPdfJob implements ShouldQueue
{
    use Queueable;

    /**
     * Number of times Laravel may attempt this job.
     */
    public int $tries = 3;

    /**
     * Maximum number of seconds the job may run.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $consentSessionId
    ) {
        $this->onQueue('default');
    }

    /**
     * Generate and store the PDF, then optionally
     * email it to the signer.
     */
    public function handle(
        ConsentPdfService $consentPdfService,
        ConsentAuditService $consentAuditService
    ): void {
        $consentSession = ConsentSession::query()
            ->with([
                'organization',
                'consentTemplate',
                'consentTemplateVersion',
                'signature',
                'creator',
                'signingStation',
            ])
            ->find($this->consentSessionId);

        if (! $consentSession) {
            return;
        }

        if (! $consentSession->isCompleted()) {
            return;
        }

        /*
         * Always generate and store the immutable PDF,
         * whether or not an email address was provided.
         */
        if (
            empty($consentSession->pdf_path)
            || empty($consentSession->pdf_generated_at)
        ) {
            $consentPdfService->generateAndStore(
                $consentSession
            );

            /* SIGNED PDF AUTO EMAIL HOOK */
            $__signedPdfDeliverySessionId = (is_object($consentSession) && method_exists($consentSession, 'getKey') ? $consentSession->getKey() : null);

            if ($__signedPdfDeliverySessionId) {
                \App\Jobs\SendSignedConsentPdfJob::dispatch(
                    (int) $__signedPdfDeliverySessionId
                )->afterCommit();
            }


            $consentSession->refresh();
        } else {
            /*
             * This also backfills a missing PDF-generated
             * audit event without generating a second PDF.
             */
            $consentPdfService->generateAndStore(
                $consentSession
            );
        }

        /*
         * No email address was provided.
         *
         * The PDF remains safely stored, but email delivery
         * is skipped and the reason is recorded.
         */
        if (blank($consentSession->signer_email)) {
            $this->recordEmailSkippedIfMissing(
                $consentSession,
                $consentAuditService
            );

            return;
        }

        /*
         * The email was already processed previously.
         *
         * Do not send it again, but backfill its audit event
         * if the audit event is missing.
         */
        if (filled($consentSession->pdf_emailed_at)) {
            $this->recordPdfEmailedIfMissing(
                $consentSession,
                $consentAuditService
            );

            return;
        }

        Mail::to(
            $consentSession->signer_email
        )->send(
            new ConsentCompletedMail(
                $consentSession
            )
        );

        $consentSession->forceFill([
            'pdf_emailed_at' => now(),
        ])->save();

        $this->recordPdfEmailedIfMissing(
            $consentSession,
            $consentAuditService
        );
    }

    /**
     * Record successful email processing once.
     */
    private function recordPdfEmailedIfMissing(
        ConsentSession $consentSession,
        ConsentAuditService $consentAuditService
    ): void {
        $alreadyRecorded = $consentSession
            ->auditEvents()
            ->where(
                'event_type',
                'consent.pdf_emailed'
            )
            ->exists();

        if ($alreadyRecorded) {
            return;
        }

        $consentAuditService->record(
            consentSession: $consentSession,
            eventType: 'consent.pdf_emailed',
            description:
                'The completed consent PDF was processed for email delivery.',
            metadata: [
                'mailer' =>
                    config('mail.default'),

                'pdf_emailed_at' =>
                    (string) $consentSession->pdf_emailed_at,
            ]
        );
    }

    /**
     * Record the missing-email outcome once.
     */
    private function recordEmailSkippedIfMissing(
        ConsentSession $consentSession,
        ConsentAuditService $consentAuditService
    ): void {
        $alreadyRecorded = $consentSession
            ->auditEvents()
            ->where(
                'event_type',
                'consent.email_skipped'
            )
            ->exists();

        if ($alreadyRecorded) {
            return;
        }

        $consentAuditService->record(
            consentSession: $consentSession,
            eventType: 'consent.email_skipped',
            description:
                'Email delivery was skipped because no signer email address was provided.',
            metadata: [
                'reason' =>
                    'No signer email address was provided.',
            ]
        );
    }

    /**
     * Retry delays in seconds.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    /**
     * Called if the job permanently fails.
     */
    public function failed(
        Throwable $exception
    ): void {
        report($exception);
    }
}
