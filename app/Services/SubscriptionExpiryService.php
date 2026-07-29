<?php

namespace App\Services;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\OrganizationSubscription;
use Illuminate\Support\Facades\DB;

class SubscriptionExpiryService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {
    }

    /**
     * Lock and expire a subscription when one of its lifecycle
     * deadlines has passed.
     */
    public function expireIfDue(
        OrganizationSubscription $organizationSubscription,
        string $source
    ): bool {
        return DB::transaction(
            function () use (
                $organizationSubscription,
                $source
            ): bool {
                $lockedSubscription =
                    OrganizationSubscription::query()
                        ->whereKey(
                            $organizationSubscription->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                return $this->expireLockedIfDue(
                    organizationSubscription:
                        $lockedSubscription,
                    source: $source
                );
            },
            3
        );
    }

    /**
     * Expire a subscription already locked by the caller.
     *
     * Returns true only when this call changes the subscription.
     */
    public function expireLockedIfDue(
        OrganizationSubscription $organizationSubscription,
        string $source
    ): bool {
        $expirationReason = $this->expirationReason(
            $organizationSubscription
        );

        if ($expirationReason === null) {
            return false;
        }

        $previousStatus =
            $organizationSubscription->status;

        $previousPaymentStatus =
            $organizationSubscription->payment_status;

        $newPaymentStatus =
            $previousPaymentStatus
                === SubscriptionPaymentStatus::PAID
                    ? SubscriptionPaymentStatus::PAST_DUE
                    : (
                        $previousPaymentStatus
                        ?? SubscriptionPaymentStatus::UNPAID
                    );

        $expiredAt = now();

        $organizationSubscription->update([
            'status' =>
                OrganizationSubscriptionStatus::EXPIRED,

            'payment_status' =>
                $newPaymentStatus,
        ]);

        $this->activityLogger->log(
            action:
                'organization.subscription_expired',

            description:
                'Organization subscription expired automatically.',

            subject:
                $organizationSubscription,

            organizationId:
                $organizationSubscription->organization_id,

            properties: [
                'expiration_reason' =>
                    $expirationReason,

                'expiration_source' =>
                    trim($source) !== ''
                        ? trim($source)
                        : 'system',

                'old' => [
                    'status' =>
                        $previousStatus?->value,

                    'payment_status' =>
                        $previousPaymentStatus?->value,
                ],

                'new' => [
                    'status' =>
                        OrganizationSubscriptionStatus::EXPIRED
                            ->value,

                    'payment_status' =>
                        $newPaymentStatus->value,
                ],

                'trial_ends_at' =>
                    $organizationSubscription
                        ->trial_ends_at
                        ?->toIso8601String(),

                'current_period_ends_at' =>
                    $organizationSubscription
                        ->current_period_ends_at
                        ?->toIso8601String(),

                'ends_at' =>
                    $organizationSubscription
                        ->ends_at
                        ?->toIso8601String(),

                'expired_at' =>
                    $expiredAt->toIso8601String(),
            ],
        );

        return true;
    }

    /**
     * Return the deadline that caused expiry, or null when the
     * subscription is not currently due for automatic expiry.
     */
    private function expirationReason(
        OrganizationSubscription $organizationSubscription
    ): ?string {
        if (
            ! in_array(
                $organizationSubscription->status,
                [
                    OrganizationSubscriptionStatus::TRIALING,
                    OrganizationSubscriptionStatus::ACTIVE,
                ],
                true
            )
        ) {
            return null;
        }

        /*
         * The final subscription end date has priority over other
         * billing-period deadlines.
         */
        if (
            $organizationSubscription->ends_at !== null
            && ! $organizationSubscription->ends_at->isFuture()
        ) {
            return 'subscription_ended';
        }

        if (
            $organizationSubscription->status
                === OrganizationSubscriptionStatus::TRIALING
            && $organizationSubscription->trial_ends_at !== null
            && ! $organizationSubscription->trial_ends_at
                ->isFuture()
        ) {
            return 'trial_ended';
        }

        if (
            $organizationSubscription->status
                === OrganizationSubscriptionStatus::ACTIVE
            && $organizationSubscription
                ->current_period_ends_at !== null
            && ! $organizationSubscription
                ->current_period_ends_at
                ->isFuture()
        ) {
            return 'billing_period_ended';
        }

        return null;
    }
}
