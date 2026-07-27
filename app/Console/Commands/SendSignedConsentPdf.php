<?php

namespace App\Console\Commands;

use App\Services\SignedConsentPdfDeliveryService;
use Illuminate\Console\Command;
use Throwable;

class SendSignedConsentPdf extends Command
{
    protected $signature = 'consent:send-signed-pdf
        {consentSessionId : The completed consent record ID}
        {--force : Send again even when a successful delivery is logged}';

    protected $description = 'Email a completed public signing-station PDF to its signer';

    public function handle(
        SignedConsentPdfDeliveryService $deliveryService
    ): int {
        try {
            $sent = $deliveryService->deliver(
                (int) $this->argument('consentSessionId'),
                (bool) $this->option('force')
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($sent) {
            $this->info('The signed PDF email was sent successfully.');
        } else {
            $this->warn(
                'No email was sent. The record may be ineligible, missing an email, or already delivered.'
            );
        }

        return self::SUCCESS;
    }
}
