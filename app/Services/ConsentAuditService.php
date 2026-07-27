<?php

namespace App\Services;

use App\Models\ConsentAuditEvent;
use App\Models\ConsentSession;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ConsentAuditService
{
    /**
     * Record a permanent consent audit event.
     *
     * @param array<string, mixed> $metadata
     */
    public function record(
        ConsentSession $consentSession,
        string $eventType,
        string $description,
        array $metadata = [],
        ?int $userId = null,
        ?Request $request = null
    ): ConsentAuditEvent {
        $eventType = trim($eventType);
        $description = trim($description);

        if ($eventType === '') {
            throw new InvalidArgumentException(
                'The audit event type cannot be empty.'
            );
        }

        if (mb_strlen($eventType) > 100) {
            throw new InvalidArgumentException(
                'The audit event type cannot exceed 100 characters.'
            );
        }

        if ($description === '') {
            throw new InvalidArgumentException(
                'The audit event description cannot be empty.'
            );
        }

        /*
         * For authenticated organization actions, obtain the user
         * from the HTTP request unless an explicit user ID was given.
         *
         * Signer and automated system events may have no user ID.
         */
        $resolvedUserId = $userId;

        if (
            $resolvedUserId === null
            && $request?->user() !== null
        ) {
            $resolvedUserId = (int) $request
                ->user()
                ->getAuthIdentifier();
        }

        return ConsentAuditEvent::query()->create([
            /*
             * The organization is always obtained from the consent
             * session so callers cannot assign an event to another
             * tenant accidentally.
             */
            'organization_id' => $consentSession->organization_id,

            'consent_session_id' => $consentSession->id,

            'user_id' => $resolvedUserId,

            'event_type' => $eventType,

            'description' => $description,

            'metadata' => $metadata !== []
                ? $metadata
                : null,

            'ip_address' => $request?->ip(),

            'user_agent' => $request?->userAgent(),
        ]);
    }
}
