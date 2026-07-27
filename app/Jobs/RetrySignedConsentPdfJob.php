<?php

namespace App\Jobs;

use App\Services\SignedConsentPdfDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetrySignedConsentPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 4;

    public int $timeout = 120;

    public function __construct(public int $consentSessionId)
    {
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(
        SignedConsentPdfDeliveryService $deliveryService
    ): void {
        $deliveryService->deliver(
            $this->consentSessionId,
            true
        );
    }
}
