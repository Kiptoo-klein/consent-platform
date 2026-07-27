<?php

namespace App\Console\Commands;

use App\Models\ConsentSession;
use App\Services\ConsentExpiryService;
use Illuminate\Console\Command;

class ExpireConsentSessions extends Command
{
    /**
     * @var string
     */
    protected $signature =
        'consents:expire {--chunk=100 : Number of records to process per batch}';

    /**
     * @var string
     */
    protected $description =
        'Expire incomplete consent records whose signing deadlines have passed.';

    public function handle(
        ConsentExpiryService $consentExpiryService
    ): int {
        $chunkSize = max(
            1,
            min(
                (int) $this->option('chunk'),
                1000
            )
        );

        $expiredCount = 0;

        ConsentSession::query()
            ->whereIn(
                'status',
                [
                    ConsentSession::STATUS_PENDING,
                    ConsentSession::STATUS_IN_PROGRESS,
                ]
            )
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(
                $chunkSize,
                function ($sessions) use (
                    $consentExpiryService,
                    &$expiredCount
                ): void {
                    foreach ($sessions as $consentSession) {
                        $wasExpired =
                            $consentExpiryService
                                ->expireIfDue(
                                    consentSession:
                                        $consentSession,
                                    source:
                                        'scheduled_command'
                                );

                        if ($wasExpired) {
                            $expiredCount++;
                        }
                    }
                }
            );

        $this->info(
            $expiredCount === 1
                ? 'Expired 1 consent record.'
                : "Expired {$expiredCount} consent records."
        );

        return self::SUCCESS;
    }
}
