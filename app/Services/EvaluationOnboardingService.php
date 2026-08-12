<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SigningStation;
use Illuminate\Http\Request;

final class EvaluationOnboardingService
{
    public const STARTER_REVIEWED_ACTION =
        'evaluation.starter_template_reviewed';

    public function __construct(
        private readonly EvaluationEmailCreditService
            $emailCreditService,
        private readonly SubscriptionUsageLimitService
            $usageLimitService
    ) {
    }

    /**
     * Build the Evaluation-only dashboard state.
     *
     * Paid subscriptions return null so the normal dashboard remains
     * unchanged.
     *
     * @return array<string, mixed>|null
     */
    public function dashboardState(
        int $organizationId
    ): ?array {
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
            return null;
        }

        $organization =
            Organization::query()
                ->findOrFail($organizationId);

        $emailCapacity =
            $this->emailCreditService->capacity(
                $organizationId
            );

        $signedCapacity =
            $this->usageLimitService
                ->signedConsentCapacity(
                    $organizationId
                );

        $kioskCapacity =
            $this->usageLimitService
                ->activeKioskCapacity(
                    $organizationId
                );

        $templateLimit =
            $subscription
                ->effectiveConsentTemplateLimit();

        $templateUsed =
            $this->usageLimitService
                ->templateUsage(
                    $organizationId
                );

        $templateCapacity = [
            'limit' =>
                $templateLimit === null
                    ? null
                    : (int) $templateLimit,

            'used' =>
                $templateUsed,

            'remaining' =>
                $templateLimit === null
                    ? null
                    : max(
                        0,
                        (int) $templateLimit
                            - $templateUsed
                    ),

            'reached' =>
                $templateLimit !== null
                && $templateUsed
                    >= (int) $templateLimit,
        ];

        /*
         * Organization profile completion intentionally requires only
         * the practical contact fields needed during Evaluation.
         *
         * Website, support email, logo, and branding remain optional.
         */
        $profileCompleted =
            filled($organization->email)
            && filled($organization->phone)
            && filled($organization->address);

        /*
         * Starter review cannot be safely inferred from updated_at because
         * publishing and lifecycle operations also update the template row.
         */
        $starterReviewed =
            ActivityLog::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->where(
                    'action',
                    self::STARTER_REVIEWED_ACTION
                )
                ->exists();

        /*
         * Version history is immutable. Once any template has been
         * published, this step remains complete even if it is later
         * taken offline.
         */
        $templatePublished =
            ConsentTemplate::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->whereHas('versions')
                ->exists();

        /*
         * A queued/reserved Evaluation invitation already occupies an
         * email credit. A completed consent also satisfies this onboarding
         * milestone even when it came from a public signing station.
         */
        $firstConsentStarted =
            (int) $emailCapacity['used'] > 0
            || (int) $signedCapacity['used'] > 0;

        /*
         * This is intentionally "created", not "currently active".
         * Pausing a station later must not make onboarding regress.
         */
        $signingStationCreated =
            SigningStation::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->exists();

        $checklist = [
            'profile' =>
                $profileCompleted,

            'starter_reviewed' =>
                $starterReviewed,

            'template_published' =>
                $templatePublished,

            'first_consent' =>
                $firstConsentStarted,

            'signing_station' =>
                $signingStationCreated,
        ];

        $completedSteps =
            collect($checklist)
                ->filter()
                ->count();

        return [
            'usage' => [
                'emails' =>
                    $emailCapacity,

                'templates' =>
                    $templateCapacity,

                'signing_stations' =>
                    $kioskCapacity,

                'completed_consents' =>
                    $signedCapacity,
            ],

            'checklist' =>
                $checklist,

            'completed_steps' =>
                $completedSteps,

            'total_steps' =>
                count($checklist),

            'complete' =>
                $completedSteps
                    === count($checklist),
        ];
    }

    /**
     * Persist the one checklist signal that cannot be derived reliably.
     */
    public function recordStarterReviewed(
        ConsentTemplate $consentTemplate,
        ?int $userId,
        ?Request $request = null
    ): void {
        if (
            ! $consentTemplate
                ->isEvaluationStarter()
            || $consentTemplate
                ->isRetiredEvaluationStarter()
        ) {
            return;
        }

        ActivityLog::query()
            ->firstOrCreate(
                [
                    'organization_id' =>
                        $consentTemplate
                            ->organization_id,

                    'action' =>
                        self::STARTER_REVIEWED_ACTION,

                    'subject_type' =>
                        $consentTemplate
                            ->getMorphClass(),

                    'subject_id' =>
                        $consentTemplate->id,
                ],
                [
                    'user_id' =>
                        $userId,

                    'description' =>
                        'An Evaluation starter template '
                        .'was reviewed and updated.',

                    'properties' => [
                        'evaluation_starter_key' =>
                            $consentTemplate
                                ->evaluation_starter_key,

                        'template_title' =>
                            $consentTemplate->title,
                    ],

                    'ip_address' =>
                        $request?->ip(),

                    'user_agent' =>
                        $request?->userAgent(),
                ]
            );
    }
}
