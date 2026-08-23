<?php

namespace App\Http\Controllers;

use App\Enums\OrganizationRole;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

class ProfileController extends Controller
{
    public function __construct(
        protected ActivityLogger $activityLogger
    ) {
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        $organization = null;
        $isOrganizationAdministrator = false;
        $isBillingOwner = false;

        if ($user->organization_id !== null) {
            $user->load('organization.subscription');

            $organization = $user->organization;

            if ($organization !== null) {
                app(PermissionRegistrar::class)
                    ->setPermissionsTeamId(
                        $organization->id
                    );

                $isOrganizationAdministrator =
                    $user->hasRole(
                        OrganizationRole::
                            ORGANIZATION_ADMINISTRATOR
                            ->label()
                    );

                $isBillingOwner =
                    $organization
                        ->subscription
                        ?->billing_owner_user_id !== null
                    && (int) $organization
                        ->subscription
                        ->billing_owner_user_id
                        === (int) $user->id;
            }
        }

        return view('profile.edit', [
            'user' => $user,
            'organization' => $organization,
            'isOrganizationAdministrator' =>
                $isOrganizationAdministrator,
            'isBillingOwner' => $isBillingOwner,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(
        ProfileUpdateRequest $request
    ): RedirectResponse {
        $request->user()->fill(
            $request->validated()
        );

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')
            ->with(
                'status',
                'profile-updated'
            );
    }

    /**
     * Archive the current account, or archive the entire organization when
     * an Organization Admin deliberately performs the action.
     */
    public function destroy(
        Request $request
    ): RedirectResponse {
        $user = $request->user();

        $organization = null;
        $isOrganizationAdministrator = false;

        if ($user->organization_id !== null) {
            $user->load('organization.subscription');

            $organization = $user->organization;

            if ($organization !== null) {
                app(PermissionRegistrar::class)
                    ->setPermissionsTeamId(
                        $organization->id
                    );

                $isOrganizationAdministrator =
                    $user->hasRole(
                        OrganizationRole::
                            ORGANIZATION_ADMINISTRATOR
                            ->label()
                    );
            }
        }

        $rules = [
            'password' => [
                'required',
                'current_password',
            ],
        ];

        if (
            $isOrganizationAdministrator
            && $organization !== null
        ) {
            $rules['organization_name'] = [
                'required',
                'string',
                'max:255',
            ];
        }

        $request->validateWithBag(
            'userDeletion',
            $rules
        );

        /*
         * An Organization Admin's self-archive is deliberately treated as
         * an organization-wide archive rather than an individual user
         * archive.
         */
        if (
            $isOrganizationAdministrator
            && $organization !== null
        ) {
            $submittedOrganizationName = trim(
                (string) $request->input(
                    'organization_name'
                )
            );

            if (
                ! hash_equals(
                    $organization->name,
                    $submittedOrganizationName
                )
            ) {
                return back()->withErrors(
                    [
                        'organization_name' =>
                            'Enter the organization name exactly '
                            .'as shown to confirm archival.',
                    ],
                    'userDeletion'
                );
            }

            DB::transaction(function () use (
                $organization,
                $user
            ): void {
                $lockedOrganization =
                    $organization
                        ->newQuery()
                        ->whereKey($organization->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $lockedOrganization
                        ->archived_at === null
                ) {
                    $lockedOrganization
                        ->forceFill([
                            'archived_at' => now(),
                        ])
                        ->save();

                    $this->activityLogger->log(
                        action: 'organization.archived',
                        description:
                            "Archived organization "
                            ."{$lockedOrganization->name}.",
                        subject: $lockedOrganization,
                        organizationId:
                            $lockedOrganization->id,
                        properties: [
                            'archived_by_user_id' =>
                                $user->id,
                            'archived_at' =>
                                $lockedOrganization
                                    ->archived_at
                                    ?->toDateTimeString(),
                        ],
                    );
                }
            });

            $this->logout($request);

            return Redirect::to('/');
        }

        /*
         * A non-admin Billing Owner must transfer billing ownership before
         * archiving their personal account.
         */
        if (
            $organization !== null
            && $organization
                ->subscription
                ?->billing_owner_user_id !== null
            && (int) $organization
                ->subscription
                ->billing_owner_user_id
                === (int) $user->id
        ) {
            return back()->withErrors(
                [
                    'archive' =>
                        'You are the current Billing Owner. '
                        .'Ask an Organization Admin to transfer '
                        .'billing ownership before archiving '
                        .'your account.',
                ],
                'userDeletion'
            );
        }

        /*
         * Normal organization users archive only their own account.
         * Their organization and its records remain unchanged.
         */
        DB::transaction(function () use (
            $organization,
            $user
        ): void {
            $user->is_active = false;
            $user->save();

            $user->delete();

            if ($organization !== null) {
                $this->activityLogger->log(
                    action: 'user.archived',
                    description:
                        "User {$user->name} archived "
                        .'their own account.',
                    subject: $user,
                    organizationId:
                        $organization->id,
                    properties: [
                        'self_service' => true,
                    ],
                );
            }
        });

        $this->logout($request);

        return Redirect::to('/');
    }

    private function logout(
        Request $request
    ): void {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
