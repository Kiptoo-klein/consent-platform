<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class OrganizationSettingsController extends Controller
{
    /**
     * Display organization settings available to the current user.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        abort_if(
            $user === null
            || $user->organization_id === null,
            403,
            'An organization account is required.'
        );

        $user->loadMissing(
            'organization.subscription'
        );

        $organization = $user->organization;

        abort_if(
            $organization === null,
            403,
            'An organization account is required.'
        );

        app(
            PermissionRegistrar::class
        )->setPermissionsTeamId(
            $organization->id
        );

        $isOrganizationAdministrator =
            $user->hasRole(
                OrganizationRole::
                    ORGANIZATION_ADMINISTRATOR
                    ->label()
            );

        $subscription =
            $organization->subscription;

        $isBillingOwner =
            $subscription !== null
            && (int) $subscription
                ->billing_owner_user_id
                === (int) $user->id;

        $hasOrganizationAccess =
            $subscription
                ?->allowsOrganizationAccess()
            ?? false;

        $requiresSubscriptionRecovery =
            ! $hasOrganizationAccess;

        return view(
            'organization-settings.index',
            [
                'organization' =>
                    $organization,

                'subscription' =>
                    $subscription,

                'isOrganizationAdministrator' =>
                    $isOrganizationAdministrator,

                'isBillingOwner' =>
                    $isBillingOwner,

                'hasOrganizationAccess' =>
                    $hasOrganizationAccess,

                'requiresSubscriptionRecovery' =>
                    $requiresSubscriptionRecovery,
            ]
        );
    }
}
