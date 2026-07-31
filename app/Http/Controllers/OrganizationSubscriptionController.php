<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\SigningStation;
use App\Models\User;
use App\Services\SubscriptionUsageLimitService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OrganizationSubscriptionController extends Controller
{
    /**
     * Display the organization's subscription, access, and plan usage.
     *
     * Every authenticated organization user may view this page. Billing
     * and plan-management actions remain restricted by their own routes.
     */
    public function show(
        Request $request,
        SubscriptionUsageLimitService $usageLimitService
    ): View {
        [
            $organization,
            $subscription,
        ] = $this->subscriptionContext(
            $request
        );

        $usage = [
            /*
             * Disabled users still occupy subscription seats. Archived
             * users are excluded by the User model's SoftDeletes scope.
             */
            'users' =>
                $organization->users()->count(),

            'consent_managers' =>
                $this->roleUsage(
                    $organization->id,
                    'Consent Manager'
                ),

            'staff' =>
                $this->roleUsage(
                    $organization->id,
                    'Staff'
                ),

            'auditors' =>
                $this->roleUsage(
                    $organization->id,
                    'Auditor'
                ),

            'active_kiosks' =>
                SigningStation::query()
                    ->where(
                        'organization_id',
                        $organization->id
                    )
                    ->where('active', true)
                    ->count(),

            'consent_templates' =>
                $usageLimitService->templateUsage(
                    $organization->id
                ),

            'signed_consents' =>
                $usageLimitService->signedConsentUsage(
                    $organization->id
                ),
        ];

        return view(
            'organization-subscription.show',
            [
                'organization' =>
                    $organization,

                'subscription' =>
                    $subscription,

                'usage' =>
                    $usage,

                'usagePeriod' =>
                    $usageLimitService->periodBounds(
                        $subscription
                    ),
            ]
        );
    }

    /**
     * Resolve the current user's organization subscription.
     *
     * @return array{0: Organization, 1: mixed}
     */
    private function subscriptionContext(
        Request $request
    ): array {
        $user = $request->user();

        abort_if(
            $user === null
            || $user->organization_id === null,
            403,
            'An organization account is required.'
        );

        $organization =
            Organization::query()
                ->findOrFail(
                    (int) $user->organization_id
                );

        $subscription =
            $organization
                ->subscription()
                ->with([
                    'plan',
                    'billingOwner',
                ])
                ->first();

        abort_if(
            $subscription === null,
            404,
            'This organization has no subscription record.'
        );

        return [
            $organization,
            $subscription,
        ];
    }

    /**
     * Count non-archived organization users assigned to a role.
     */
    private function roleUsage(
        int $organizationId,
        string $roleName
    ): int {
        return User::query()
            ->where(
                'users.organization_id',
                $organizationId
            )
            ->whereHas(
                'roles',
                function ($query) use (
                    $organizationId,
                    $roleName
                ): void {
                    $query
                        ->where(
                            'roles.organization_id',
                            $organizationId
                        )
                        ->where(
                            'roles.guard_name',
                            'web'
                        )
                        ->where(
                            'roles.name',
                            $roleName
                        );
                }
            )
            ->count();
    }
}
