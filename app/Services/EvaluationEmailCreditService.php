<?php

namespace App\Services;

use App\Models\EvaluationEmailCreditUsage;
use App\Models\OrganizationSubscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class EvaluationEmailCreditService
{
    /**
     * Return the Evaluation signing-email allowance.
     *
     * Paid subscriptions are not constrained by this service.
     *
     * @return array{
     *     is_evaluation: bool,
     *     limit: int|null,
     *     used: int,
     *     remaining: int|null,
     *     reached: bool
     * }
     */
    public function capacity(
        int $organizationId
    ): array {
        $subscription =
            OrganizationSubscription::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->first();

        if (
            $subscription === null
            || ! $subscription->isEvaluation()
        ) {
            return [
                'is_evaluation' => false,
                'limit' => null,
                'used' => 0,
                'remaining' => null,
                'reached' => false,
            ];
        }

        $limit = $this->limit();
        $used = $this
            ->countedUsageQuery(
                $organizationId
            )
            ->count();

        return [
            'is_evaluation' => true,
            'limit' => $limit,
            'used' => $used,
            'remaining' => max(
                0,
                $limit - $used
            ),
            'reached' => $used >= $limit,
        ];
    }

    public function isEvaluationOrganization(
        int $organizationId
    ): bool {
        return OrganizationSubscription::query()
            ->where(
                'organization_id',
                $organizationId
            )
            ->first()
            ?->isEvaluation()
            ?? false;
    }

    /**
     * Reserve one signing-email credit.
     *
     * This method must run inside the same transaction that creates
     * the ConsentNotification. Locking the subscription serializes
     * concurrent Evaluation sends for the organization.
     */
    public function reserveLocked(
        int $organizationId,
        int $consentSessionId,
        string $notificationType
    ): ?EvaluationEmailCreditUsage {
        $subscription =
            OrganizationSubscription::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->lockForUpdate()
                ->first();

        if (
            $subscription === null
            || ! $subscription->isEvaluation()
        ) {
            return null;
        }

        $limit = $this->limit();

        $used = $this
            ->countedUsageQuery(
                $organizationId
            )
            ->count();

        if ($used >= $limit) {
            throw ValidationException::withMessages([
                'email' =>
                    'The free evaluation includes '
                    ."{$limit} signing emails and all "
                    ."{$used} have been used. Choose a "
                    .'subscription plan to send more '
                    .'signing emails.',
            ]);
        }

        return EvaluationEmailCreditUsage::query()
            ->create([
                'organization_id' =>
                    $organizationId,

                'consent_session_id' =>
                    $consentSessionId,

                'consent_notification_id' =>
                    null,

                'reservation_key' =>
                    (string) Str::uuid(),

                'notification_type' =>
                    $notificationType,

                'status' =>
                    EvaluationEmailCreditUsage::
                        STATUS_RESERVED,

                'reserved_at' => now(),

                /*
                 * Normally the notification is attached in the same
                 * transaction. The expiry prevents an orphaned reservation
                 * from permanently reducing the Evaluation allowance.
                 */
                'reservation_expires_at' =>
                    now()->addMinutes(15),

                'consumed_at' => null,
                'released_at' => null,
                'release_reason' => null,

                'metadata' => [
                    'source' =>
                        'consent_notification',
                ],
            ]);
    }

    /**
     * Bind a reservation to the notification created in the same
     * database transaction.
     */
    public function attachNotificationLocked(
        int $usageId,
        int $notificationId,
        string $notificationType
    ): void {
        EvaluationEmailCreditUsage::query()
            ->whereKey($usageId)
            ->where(
                'status',
                EvaluationEmailCreditUsage::
                    STATUS_RESERVED
            )
            ->update([
                'consent_notification_id' =>
                    $notificationId,

                'notification_type' =>
                    $notificationType,

                /*
                 * Once attached to a real notification it remains
                 * reserved for as long as queued retries are alive.
                 */
                'reservation_expires_at' =>
                    null,
            ]);
    }

    /**
     * Finalize a reserved credit after successful delivery.
     */
    public function consumeForNotification(
        int $notificationId
    ): void {
        EvaluationEmailCreditUsage::query()
            ->where(
                'consent_notification_id',
                $notificationId
            )
            ->where(
                'status',
                EvaluationEmailCreditUsage::
                    STATUS_RESERVED
            )
            ->update([
                'status' =>
                    EvaluationEmailCreditUsage::
                        STATUS_CONSUMED,

                'consumed_at' => now(),
                'released_at' => null,
                'release_reason' => null,
            ]);
    }

    /**
     * Release an undelivered reservation.
     *
     * Consumed credits are intentionally never released.
     */
    public function releaseForNotification(
        int $notificationId,
        string $reason
    ): void {
        EvaluationEmailCreditUsage::query()
            ->where(
                'consent_notification_id',
                $notificationId
            )
            ->where(
                'status',
                EvaluationEmailCreditUsage::
                    STATUS_RESERVED
            )
            ->update([
                'status' =>
                    EvaluationEmailCreditUsage::
                        STATUS_RELEASED,

                'released_at' => now(),

                'release_reason' =>
                    mb_substr(
                        $reason,
                        0,
                        255
                    ),
            ]);
    }

    private function limit(): int
    {
        return max(
            1,
            (int) config(
                'evaluation.limits.invitation_emails',
                5
            )
        );
    }

    private function countedUsageQuery(
        int $organizationId
    ): Builder {
        return EvaluationEmailCreditUsage::query()
            ->where(
                'organization_id',
                $organizationId
            )
            ->where(
                function (Builder $query): void {
                    $query
                        ->where(
                            'status',
                            EvaluationEmailCreditUsage::
                                STATUS_CONSUMED
                        )
                        ->orWhere(
                            function (
                                Builder $reserved
                            ): void {
                                $reserved
                                    ->where(
                                        'status',
                                        EvaluationEmailCreditUsage::
                                            STATUS_RESERVED
                                    )
                                    ->where(
                                        function (
                                            Builder $live
                                        ): void {
                                            $live
                                                ->whereNotNull(
                                                    'consent_notification_id'
                                                )
                                                ->orWhere(
                                                    'reservation_expires_at',
                                                    '>',
                                                    now()
                                                );
                                        }
                                    );
                            }
                        );
                }
            );
    }
}
