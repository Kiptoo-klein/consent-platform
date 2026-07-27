<?php

namespace App\Services;

use App\Models\ConsentSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsentExpiryService
{
    public function __construct(
        private readonly ConsentAuditService $consentAuditService
    ) {
    }

    /**
     * Lock a consent record and expire it when its deadline has passed.
     */
    public function expireIfDue(
        ConsentSession $consentSession,
        string $source,
        ?Request $request = null
    ): bool {
        return DB::transaction(
            function () use (
                $consentSession,
                $source,
                $request
            ): bool {
                $lockedSession = ConsentSession::query()
                    ->whereKey($consentSession->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                return $this->expireLockedIfDue(
                    consentSession: $lockedSession,
                    source: $source,
                    request: $request
                );
            },
            3
        );
    }

    /**
     * Expire a consent record that is already locked by the caller.
     *
     * Returns true only when this call changed the record to expired.
     */
    public function expireLockedIfDue(
        ConsentSession $consentSession,
        string $source,
        ?Request $request = null
    ): bool {
        if (! $consentSession->isPastDue()) {
            return false;
        }

        $previousStatus = $consentSession->status;
        $expiredAt = now();

        $consentSession->update([
            'status' =>
                ConsentSession::STATUS_EXPIRED,

            'expired_at' =>
                $expiredAt,

            'cancelled_at' =>
                null,
        ]);

        $this->consentAuditService->record(
            consentSession: $consentSession,
            eventType: 'consent.expired',
            description:
                'The consent record expired before it was completed.',
            metadata: [
                'expiration_source' =>
                    trim($source) !== ''
                        ? trim($source)
                        : 'system',

                'previous_status' =>
                    $previousStatus,

                'expires_at' =>
                    $consentSession->expires_at?->toIso8601String(),

                'expired_at' =>
                    $expiredAt->toIso8601String(),
            ],
            request: $request
        );

        return true;
    }
}
