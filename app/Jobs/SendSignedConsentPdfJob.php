<?php

namespace App\Jobs;

use App\Jobs\Middleware\EnforceEmailQuota;
use App\Models\ConsentPdfDelivery;
use App\Models\ConsentSession;
use App\Services\SignedConsentPdfDeliveryService;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSignedConsentPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1000;

    public int $timeout = 120;

    public function __construct(public int $consentSessionId)
    {
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function middleware(): array
    {
        return [
            new EnforceEmailQuota(
                category: 'signed_consent_pdf',
                jobKey:
                    'signed_consent_pdf:'
                    .$this->consentSessionId
            ),
        ];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDays(40);
    }

    public function shouldConsumeEmailQuota(): bool
    {
        if (! config('signed-consent-delivery.enabled', true)) {
            return false;
        }

        $session = ConsentSession::query()
            ->find($this->consentSessionId);

        if (
            $session === null
            || ! $session->isCompleted()
            || ! filter_var(
                trim((string) $session->signer_email),
                FILTER_VALIDATE_EMAIL
            )
        ) {
            return false;
        }

        if (
            config(
                'signed-consent-delivery.public_signing_stations_only',
                true
            )
            && blank($session->signing_station_id)
        ) {
            return false;
        }

        $alreadySent = ConsentPdfDelivery::query()
            ->where(
                'consent_session_id',
                $session->id
            )
            ->whereNotNull('sent_at')
            ->exists();

        if ($alreadySent) {
            return false;
        }

        return true;
    }

    public function handle(
        SignedConsentPdfDeliveryService $deliveryService
    ): void {
        $deliveryService->deliver($this->consentSessionId);
    }
}
