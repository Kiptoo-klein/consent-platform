<?php

namespace App\Services;

use App\Models\ConsentSession;
use App\Models\ConsentTemplate;
use App\Models\OrganizationSubscription;
use App\Models\SigningStation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final class SubscriptionUsageLimitService
{
    /**
     * Ensure the organization may create or restore another template.
     *
     * Call this inside the same database transaction that creates or
     * restores the template. The subscription row lock serializes
     * concurrent attempts for the same organization.
     */
    public function assertTemplateSlotAvailableLocked(
        int $organizationId
    ): void {
        $subscription =
            $this->lockedSubscription($organizationId);

        $limit =
            $subscription?->effectiveConsentTemplateLimit();

        /*
         * Missing subscriptions and null limits remain the responsibility
         * of the subscription-access layer and are treated as unlimited.
         */
        if ($limit === null) {
            return;
        }

        $used = $this->templateUsage(
            $organizationId
        );

        if ($used >= (int) $limit) {
            $message = $subscription?->isEvaluation()
                ? "The free evaluation includes {$limit} active consent "
                    ."templates and all {$used} slots are in use. "
                    .'Archive an unused template or choose a plan to '
                    .'continue creating templates.'
                : 'The consent template limit for this subscription '
                    ."plan has been reached ({$used} of {$limit}). "
                    .'Archive an unused template or upgrade the plan.';

            throw ValidationException::withMessages([
                'subscription' => $message,
            ]);
        }
    }

    /**
     * Ensure one more consent may be completed in the current period.
     *
     * Call this inside the same transaction that writes the signature and
     * marks the consent completed. Locking the subscription prevents two
     * concurrent signers from consuming the final slot simultaneously.
     */
    public function assertSignedConsentSlotAvailableLocked(
        int $organizationId,
        ?int $excludeConsentSessionId = null
    ): void {
        $subscription =
            $this->lockedSubscription($organizationId);

        $limit =
            $subscription
                ?->effectiveSignedConsentLimit();

        if ($limit === null) {
            return;
        }

        $used = $this->signedConsentUsageForSubscription(
            $subscription,
            $excludeConsentSessionId
        );

        if ($used >= (int) $limit) {
            $message = $subscription?->isEvaluation()
                ? 'The free evaluation includes '
                    ."{$limit} completed consents and all {$used} "
                    .'have been used. Choose a plan to continue '
                    .'collecting new signatures.'
                : 'The signed consent limit for the current '
                    ."subscription period has been reached ({$used} "
                    ."of {$limit}). Try again in the next billing "
                    .'period or upgrade the plan.';

            throw ValidationException::withMessages([
                'subscription' => $message,
            ]);
        }
    }


    /**
     * Return signed-consent usage details for customer-facing screens.
     *
     * @return array{
     *     plan_name: string|null,
     *     limit: int|null,
     *     used: int,
     *     remaining: int|null,
     *     reached: bool,
     *     warning: bool,
     *     period_start: CarbonInterface|null,
     *     period_end: CarbonInterface|null
     * }
     */
    public function signedConsentCapacity(
        int $organizationId
    ): array {
        $subscription =
            OrganizationSubscription::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->with('plan')
                ->first();

        $rawLimit =
            $subscription
                ?->effectiveSignedConsentLimit();

        $limit =
            $rawLimit === null
                ? null
                : (int) $rawLimit;

        $used =
            $subscription === null
                ? 0
                : $this->signedConsentUsageForSubscription(
                    $subscription
                );

        $remaining =
            $limit === null
                ? null
                : max(
                    0,
                    $limit - $used
                );

        $bounds =
            $subscription === null
                ? [
                    'start' => null,
                    'end' => null,
                ]
                : $this->periodBounds(
                    $subscription
                );

        return [
            'plan_name' =>
                $subscription?->isEvaluation()
                    ? 'Free evaluation'
                    : $subscription
                        ?->plan
                        ?->name,

            'limit' =>
                $limit,

            'used' =>
                $used,

            'remaining' =>
                $remaining,

            'reached' =>
                $limit !== null
                && $used >= $limit,

            'warning' =>
                $limit !== null
                && $limit > 0
                && $used >= (int) ceil(
                    $limit * 0.8
                ),

            'period_start' =>
                $bounds['start'],

            'period_end' =>
                $bounds['end'],
        ];
    }

    /**
     * Return active kiosk usage details for organization screens.
     *
     * @return array{
     *     plan_name: string|null,
     *     limit: int|null,
     *     used: int,
     *     remaining: int|null,
     *     reached: bool
     * }
     */
    public function activeKioskCapacity(
        int $organizationId
    ): array {
        $subscription =
            OrganizationSubscription::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->with('plan')
                ->first();

        $rawLimit =
            $subscription
                ?->effectiveActiveKioskLimit();

        $limit =
            $rawLimit === null
                ? null
                : (int) $rawLimit;

        $used =
            SigningStation::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->where(
                    'active',
                    true
                )
                ->count();

        return [
            'plan_name' =>
                $subscription?->isEvaluation()
                    ? 'Free evaluation'
                    : $subscription
                        ?->plan
                        ?->name,

            'limit' =>
                $limit,

            'used' =>
                $used,

            'remaining' =>
                $limit === null
                    ? null
                    : max(
                        0,
                        $limit - $used
                    ),

            'reached' =>
                $limit !== null
                && $used >= $limit,
        ];
    }

    /**
     * Prevent new signing requests after signed-consent capacity is used.
     *
     * The final locked completion check remains authoritative.
     */
    public function assertSignedConsentCreationAvailable(
        int $organizationId
    ): void {
        $capacity =
            $this->signedConsentCapacity(
                $organizationId
            );

        if (! $capacity['reached']) {
            return;
        }

        $subscription =
            OrganizationSubscription::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->first();

        $message = $subscription?->isEvaluation()
            ? 'The free evaluation includes '
                ."{$capacity['limit']} completed consents and all "
                ."{$capacity['used']} have been used. Choose a plan "
                .'to continue collecting new signatures.'
            : 'The signed consent limit for the current '
                .'subscription period has been reached '
                ."({$capacity['used']} of {$capacity['limit']}). "
                .'Wait for the next billing period or upgrade the plan.';

        throw ValidationException::withMessages([
            'subscription' => $message,
        ]);
    }

    /**
     * Count non-archived templates occupying plan capacity.
     */
    public function templateUsage(
        int $organizationId
    ): int {
        return ConsentTemplate::query()
            ->where(
                'organization_id',
                $organizationId
            )
            ->where('status', '!=', 'archived')
            ->count();
    }

    /**
     * Count completed consents in the subscription's current period.
     */
    public function signedConsentUsage(
        int $organizationId
    ): int {
        $subscription =
            OrganizationSubscription::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->with('plan')
                ->first();

        if ($subscription === null) {
            return 0;
        }

        return $this->signedConsentUsageForSubscription(
            $subscription
        );
    }

    /**
     * Return the usage window shown to customers and used for enforcement.
     *
     * Active billing dates take priority. Trial dates are used when an
     * unpaid trial has not yet received an invoiced billing period.
     *
     * @return array{
     *     start: CarbonInterface|null,
     *     end: CarbonInterface|null
     * }
     */
    public function periodBounds(
        OrganizationSubscription $subscription
    ): array {
        if ($subscription->isEvaluation()) {
            return [
                'start' => null,
                'end' => null,
            ];
        }

        return [
            'start' =>
                $subscription->current_period_starts_at
                ?? $subscription->starts_at
                ?? $subscription->created_at,

            'end' =>
                $subscription->current_period_ends_at
                ?? $subscription->trial_ends_at
                ?? $subscription->ends_at,
        ];
    }

    private function lockedSubscription(
        int $organizationId
    ): ?OrganizationSubscription {
        return OrganizationSubscription::query()
            ->where(
                'organization_id',
                $organizationId
            )
            ->with('plan')
            ->lockForUpdate()
            ->first();
    }

    private function signedConsentUsageForSubscription(
        OrganizationSubscription $subscription,
        ?int $excludeConsentSessionId = null
    ): int {
        $bounds = $this->periodBounds(
            $subscription
        );

        $query = ConsentSession::query()
            ->where(
                'organization_id',
                $subscription->organization_id
            )
            ->where(
                'status',
                ConsentSession::STATUS_COMPLETED
            )
            ->whereNotNull('completed_at');

        /*
         * Evaluation capacity is lifetime. Paid plans and timed trials
         * continue to use their normal subscription-period window.
         */
        if (! $subscription->isEvaluation()) {
            $this->applyPeriodBounds(
                $query,
                $bounds['start'],
                $bounds['end']
            );
        }

        if ($excludeConsentSessionId !== null) {
            $query->where(
                'id',
                '!=',
                $excludeConsentSessionId
            );
        }

        return $query->count();
    }

    private function applyPeriodBounds(
        Builder $query,
        ?CarbonInterface $start,
        ?CarbonInterface $end
    ): void {
        if ($start !== null) {
            $query->where(
                'completed_at',
                '>=',
                $start
            );
        }

        if ($end !== null) {
            $query->where(
                'completed_at',
                '<',
                $end
            );
        }
    }
}
