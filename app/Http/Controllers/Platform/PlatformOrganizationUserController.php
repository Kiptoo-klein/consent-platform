<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\OrganizationUserInvitation;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Handles platform-level management of users inside organizations.
 *
 * Platform administrators can:
 *
 * - View organization users.
 * - Create organization users.
 * - Edit organization users.
 * - Assign organization roles.
 * - Enable or disable organization users.
 * - Soft-delete organization users.
 * - Restore soft-deleted organization users.
 */
class PlatformOrganizationUserController extends Controller
{
    /**
     * Service responsible for recording audit activities.
     */
    public function __construct(
        protected ActivityLogger $activityLogger
    ) {
    }

    /**
     * Display users belonging to a specific organization.
     */
    public function index(Organization $organization): View
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($organization->id);

        $users = $organization
            ->users()
            ->with('roles')
            ->orderBy('name')
            ->paginate(15);

        /*
         * Management display count only.
         * Subscription capacity keeps its existing seat rules.
         */
        $activeUserCount = $organization
            ->users()
            ->where('is_active', true)
            ->count();

        return view(
            'platform.organizations.users.index',
            compact(
                'organization',
                'users',
                'activeUserCount'
            )
        );
    }

    /**
     * Display soft-deleted users belonging to an organization.
     */
    public function archived(Organization $organization): View
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($organization->id);

        $users = $organization
            ->users()
            ->onlyTrashed()
            ->with('roles')
            ->orderByDesc('deleted_at')
            ->paginate(15);

        return view(
            'platform.organizations.users.archived',
            compact('organization', 'users')
        );
    }

    /**
     * Display the form for creating an organization user.
     */
    public function create(Organization $organization): View
    {
        $roles = Role::query()
            ->where('organization_id', $organization->id)
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        return view(
            'platform.organizations.users.create',
            compact('organization', 'roles')
        );
    }

    /**
     * Store a newly created organization user.
     */
    public function store(
        Request $request,
        Organization $organization
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(
                    fn ($query) => $query
                        ->where('organization_id', $organization->id)
                        ->where('guard_name', 'web')
                ),
            ],
        ]);

        [$user, $roleName] = DB::transaction(function () use (
            $validated,
            $organization
        ): array {
            /*
             * Serialize user creation for this organization so concurrent
             * requests cannot exceed the subscription seat limit.
             */
            Organization::query()
                ->whereKey($organization->id)
                ->lockForUpdate()
                ->firstOrFail();

            $subscription = $organization
                ->subscription()
                ->with('plan')
                ->first();

            $maximumUsers = $subscription?->effectiveUserLimit();
            $currentUsers = $organization->users()->count();

            /*
             * Disabled users still occupy seats. Soft-deleted users are
             * excluded automatically by the User model's SoftDeletes scope.
             */
            if (
                $maximumUsers !== null
                && $currentUsers >= $maximumUsers
            ) {
                throw ValidationException::withMessages([
                    'subscription' =>
                        "This organization has reached its "
                        ."{$maximumUsers}-user subscription limit. "
                        .'Archive a user or upgrade the subscription '
                        .'before adding another user.',
                ]);
            }

            app(PermissionRegistrar::class)
                ->setPermissionsTeamId($organization->id);

            $role = Role::query()
                ->where('organization_id', $organization->id)
                ->where('guard_name', 'web')
                ->findOrFail($validated['role_id']);

            $maximumStaff = $subscription?->effectiveRoleLimit('Staff');

            if (
                $role->name === 'Staff'
                && $maximumStaff !== null
            ) {
                /*
                 * Disabled Staff users still occupy role seats. Archived
                 * users are excluded by the User model's SoftDeletes scope.
                 */
                $currentStaff = User::query()
                    ->where(
                        'users.organization_id',
                        $organization->id
                    )
                    ->whereHas(
                        'roles',
                        function ($query) use ($organization): void {
                            $query
                                ->where(
                                    'roles.organization_id',
                                    $organization->id
                                )
                                ->where(
                                    'roles.guard_name',
                                    'web'
                                )
                                ->where(
                                    'roles.name',
                                    'Staff'
                                );
                        }
                    )
                    ->count();

                if ($currentStaff >= $maximumStaff) {
                    throw ValidationException::withMessages([
                        'subscription' =>
                            'This organization has reached its Staff '
                            ."role limit of {$maximumStaff}. "
                            .'Archive a Staff user or upgrade the '
                            .'subscription before adding another.',
                    ]);
                }
            }

            $maximumConsentManagers =
                $subscription?->effectiveRoleLimit('Consent Manager');

            if (
                $role->name === 'Consent Manager'
                && $maximumConsentManagers !== null
            ) {
                /*
                 * Disabled Consent Managers still occupy role seats.
                 * Archived users are excluded by the SoftDeletes scope.
                 */
                $currentConsentManagers = User::query()
                    ->where(
                        'users.organization_id',
                        $organization->id
                    )
                    ->whereHas(
                        'roles',
                        function ($query) use ($organization): void {
                            $query
                                ->where(
                                    'roles.organization_id',
                                    $organization->id
                                )
                                ->where(
                                    'roles.guard_name',
                                    'web'
                                )
                                ->where(
                                    'roles.name',
                                    'Consent Manager'
                                );
                        }
                    )
                    ->count();

                if (
                    $currentConsentManagers
                    >= $maximumConsentManagers
                ) {
                    throw ValidationException::withMessages([
                        'subscription' =>
                            'This organization has reached its Consent '
                            .'Manager role limit of '
                            ."{$maximumConsentManagers}. "
                            .'Archive a Consent Manager or upgrade the '
                            .'subscription before adding another.',
                    ]);
                }
            }

            $maximumAuditors =
                $subscription?->effectiveRoleLimit('Auditor');

            if (
                $role->name === 'Auditor'
                && $maximumAuditors !== null
            ) {
                /*
                 * Disabled Auditors still occupy role seats. Archived users
                 * are excluded by the User model's SoftDeletes scope.
                 */
                $currentAuditors = User::query()
                    ->where(
                        'users.organization_id',
                        $organization->id
                    )
                    ->whereHas(
                        'roles',
                        function ($query) use ($organization): void {
                            $query
                                ->where(
                                    'roles.organization_id',
                                    $organization->id
                                )
                                ->where(
                                    'roles.guard_name',
                                    'web'
                                )
                                ->where(
                                    'roles.name',
                                    'Auditor'
                                );
                        }
                    )
                    ->count();

                if ($currentAuditors >= $maximumAuditors) {
                    throw ValidationException::withMessages([
                        'subscription' =>
                            'This organization has reached its Auditor '
                            ."role limit of {$maximumAuditors}. "
                            .'Archive an Auditor or upgrade the '
                            .'subscription before adding another.',
                    ]);
                }
            }

            $user = User::create([
                'organization_id' => $organization->id,
                'platform_role_id' => null,
                'name' => $validated['name'],
                'email' => $validated['email'],
                /*
                 * The administrator never chooses this password.
                 * The invitee replaces it during account setup.
                 */
                'password' => Str::random(64),
                'is_active' => true,
            ]);

            $user->assignRole($role);

            app(PermissionRegistrar::class)
                ->forgetCachedPermissions();

            $this->activityLogger->log(
                action: 'user.created',
                description: "Created user {$user->name}.",
                subject: $user,
                organizationId: $organization->id,
                properties: [
                    'old' => [],
                    'new' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $role->name,
                        'is_active' => (bool) $user->is_active,
                    ],
                ],
            );

            return [
                $user,
                $role->name,
            ];
        });

        /*
         * Generate the invitation only after the database transaction
         * has committed successfully.
         */
        $token = PasswordBroker::broker(
            'organization_invitations'
        )->createToken($user);

        try {
            $user->notify(
                new OrganizationUserInvitation(
                    token: $token,
                    organizationName: $organization->name,
                    roleName: $roleName
                )
            );
        } catch (\Throwable $exception) {
            /*
             * Account creation remains successful if initial dispatch
             * temporarily fails.
             */
            report($exception);
        }

        return redirect()
            ->route(
                $this->organizationUserRouteName('index'),
                $organization
            )
            ->with(
                'success',
                'Organization user invited successfully.'
            );
    }

    /**
     * Display the edit form for an organization user.
     */
    public function edit(
        Organization $organization,
        User $user
    ): View {
        $this->ensureUserBelongsToOrganization(
            $organization,
            $user
        );

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($organization->id);

        $roles = Role::query()
            ->where('organization_id', $organization->id)
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        $currentRole = $user
            ->roles()
            ->where(
                'roles.organization_id',
                $organization->id
            )
            ->first();

        return view(
            'platform.organizations.users.edit',
            compact(
                'organization',
                'user',
                'roles',
                'currentRole'
            )
        );
    }

    /**
     * Update an existing organization user.
     */
    public function update(
        Request $request,
        Organization $organization,
        User $user
    ): RedirectResponse {
        $this->ensureUserBelongsToOrganization(
            $organization,
            $user
        );

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($organization->id);

        $user->load('roles');

        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles->first()?->name,
            'is_active' => (bool) $user->is_active,
        ];

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'password' => [
                'nullable',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(
                    fn ($query) => $query
                        ->where('organization_id', $organization->id)
                        ->where('guard_name', 'web')
                ),
            ],
        ]);

        $newRole = Role::query()
            ->where('organization_id', $organization->id)
            ->where('guard_name', 'web')
            ->findOrFail($validated['role_id']);

        /*
         * Prevent the final active Organization Admin from being demoted.
         */
        if (
            $this->isOrganizationAdmin($organization, $user)
            && $newRole->name !== 'Organization Admin'
            && $user->is_active
            && $this->activeOrganizationAdminCount($organization) <= 1
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'role_id' =>
                        'This user is the last active Organization Admin. '
                        .'Assign another active Organization Admin before '
                        .'changing this role.',
                ]);
        }

        DB::transaction(function () use (
            $validated,
            $organization,
            $user,
            $newRole
        ): void {
            /*
             * Serialize role changes so concurrent requests cannot exceed
             * the organization's subscription role limits.
             */
            Organization::query()
                ->whereKey($organization->id)
                ->lockForUpdate()
                ->firstOrFail();

            app(PermissionRegistrar::class)
                ->setPermissionsTeamId($organization->id);

            $currentRole = $user
                ->roles()
                ->where(
                    'roles.organization_id',
                    $organization->id
                )
                ->where('roles.guard_name', 'web')
                ->first();

            /*
             * A role limit is consumed only when moving into that role.
             * Editing a user who is already Staff must remain allowed.
             */
            if (
                $newRole->name === 'Staff'
                && $currentRole?->id !== $newRole->id
            ) {
                $subscription = $organization
                    ->subscription()
                    ->with('plan')
                    ->first();

                $maximumStaff =
                    $subscription?->effectiveRoleLimit('Staff');

                if ($maximumStaff !== null) {
                    $currentStaff = User::query()
                        ->where(
                            'users.organization_id',
                            $organization->id
                        )
                        ->whereHas(
                            'roles',
                            function ($query) use ($organization): void {
                                $query
                                    ->where(
                                        'roles.organization_id',
                                        $organization->id
                                    )
                                    ->where(
                                        'roles.guard_name',
                                        'web'
                                    )
                                    ->where(
                                        'roles.name',
                                        'Staff'
                                    );
                            }
                        )
                        ->count();

                    if ($currentStaff >= $maximumStaff) {
                        throw ValidationException::withMessages([
                            'subscription' =>
                                'This organization has reached its Staff '
                                ."role limit of {$maximumStaff}. "
                                .'Archive a Staff user or upgrade the '
                                .'subscription before changing this role.',
                        ]);
                    }
                }
            }

            /*
             * A role limit is consumed only when moving into the
             * Consent Manager role. Editing an existing Consent Manager
             * must remain allowed.
             */
            if (
                $newRole->name === 'Consent Manager'
                && $currentRole?->id !== $newRole->id
            ) {
                $subscription = $organization
                    ->subscription()
                    ->with('plan')
                    ->first();

                $maximumConsentManagers =
                    $subscription?->effectiveRoleLimit('Consent Manager');

                if ($maximumConsentManagers !== null) {
                    /*
                     * Disabled Consent Managers still occupy role seats.
                     * Archived users are excluded by the User model's
                     * SoftDeletes scope.
                     */
                    $currentConsentManagers = User::query()
                        ->where(
                            'users.organization_id',
                            $organization->id
                        )
                        ->whereHas(
                            'roles',
                            function ($query) use ($organization): void {
                                $query
                                    ->where(
                                        'roles.organization_id',
                                        $organization->id
                                    )
                                    ->where(
                                        'roles.guard_name',
                                        'web'
                                    )
                                    ->where(
                                        'roles.name',
                                        'Consent Manager'
                                    );
                            }
                        )
                        ->count();

                    if (
                        $currentConsentManagers
                        >= $maximumConsentManagers
                    ) {
                        throw ValidationException::withMessages([
                            'subscription' =>
                                'This organization has reached its Consent '
                                .'Manager role limit of '
                                ."{$maximumConsentManagers}. "
                                .'Archive a Consent Manager or upgrade the '
                                .'subscription before changing this role.',
                        ]);
                    }
                }
            }

            /*
             * A role limit is consumed only when moving into the Auditor
             * role. Editing an existing Auditor must remain allowed.
             */
            if (
                $newRole->name === 'Auditor'
                && $currentRole?->id !== $newRole->id
            ) {
                $subscription = $organization
                    ->subscription()
                    ->with('plan')
                    ->first();

                $maximumAuditors =
                    $subscription?->effectiveRoleLimit('Auditor');

                if ($maximumAuditors !== null) {
                    /*
                     * Disabled Auditors still occupy role seats. Archived
                     * users are excluded by the User model's SoftDeletes
                     * scope.
                     */
                    $currentAuditors = User::query()
                        ->where(
                            'users.organization_id',
                            $organization->id
                        )
                        ->whereHas(
                            'roles',
                            function ($query) use ($organization): void {
                                $query
                                    ->where(
                                        'roles.organization_id',
                                        $organization->id
                                    )
                                    ->where(
                                        'roles.guard_name',
                                        'web'
                                    )
                                    ->where(
                                        'roles.name',
                                        'Auditor'
                                    );
                            }
                        )
                        ->count();

                    if ($currentAuditors >= $maximumAuditors) {
                        throw ValidationException::withMessages([
                            'subscription' =>
                                'This organization has reached its Auditor '
                                ."role limit of {$maximumAuditors}. "
                                .'Archive an Auditor or upgrade the '
                                .'subscription before changing this role.',
                        ]);
                    }
                }
            }

            $user->name = $validated['name'];
            $user->email = $validated['email'];

            if (! empty($validated['password'])) {
                $user->password = $validated['password'];
            }

            $user->save();

            app(PermissionRegistrar::class)
                ->setPermissionsTeamId($organization->id);

            $user->syncRoles([$newRole]);

            app(PermissionRegistrar::class)
                ->forgetCachedPermissions();
        });

        /*
         * Reload the user's roles so the audit log records the new role.
         */
        $user->load('roles');

        $this->activityLogger->log(
            action: 'user.updated',
            description: "Updated user {$user->name}.",
            subject: $user,
            organizationId: $organization->id,
            properties: [
                'old' => $oldValues,
                'new' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name,
                    'is_active' => (bool) $user->is_active,
                ],
            ],
        );

        return redirect()
            ->route(
                $this->organizationUserRouteName('index'),
                $organization
            )
            ->with(
                'success',
                'Organization user updated successfully.'
            );
    }

    /**
     * Enable or disable an organization user account.
     */
    public function updateStatus(
        Request $request,
        Organization $organization,
        User $user
    ): RedirectResponse {
        $this->ensureUserBelongsToOrganization(
            $organization,
            $user
        );

        $validated = $request->validate([
            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        $oldStatus = (bool) $user->is_active;
        $newStatus = (bool) $validated['is_active'];

        /*
         * BILLING_OWNER_DISABLE_PROTECTION
         *
         * Billing ownership must be transferred before this account can
         * be disabled.
         */
        if (
            $oldStatus
            && ! $newStatus
            && $this->isBillingOwner(
                $organization,
                $user
            )
        ) {
            return back()->withErrors([
                'status' =>
                    'This user is the current Billing Owner. '
                    .'Assign another active Billing Owner under Billing '
                    .'before disabling this account.',
            ]);
        }

        /*
         * Prevent the final active Organization Admin from being disabled.
         */
        if (
            $oldStatus
            && ! $newStatus
            && $this->isOrganizationAdmin($organization, $user)
            && $this->activeOrganizationAdminCount($organization) <= 1
        ) {
            return back()->withErrors([
                'status' =>
                    'This user is the last active Organization Admin. '
                    .'Assign another active Organization Admin before '
                    .'disabling this account.',
            ]);
        }

        $user->is_active = $newStatus;
        $user->save();

        if (! $newStatus) {
            /*
             * Disabled pending accounts must not retain a usable
             * invitation.
             */
            PasswordBroker::broker(
                'organization_invitations'
            )->deleteToken($user);
        } elseif (! $user->hasVerifiedEmail()) {
            /*
             * Re-enabling an unfinished account creates a fresh invite.
             */
            $user->load('roles');

            $token = PasswordBroker::broker(
                'organization_invitations'
            )->createToken($user);

            try {
                $user->notify(
                    new OrganizationUserInvitation(
                        token: $token,
                        organizationName: $organization->name,
                        roleName:
                            $user->roles->first()?->name
                            ?? 'Organization User'
                    )
                );
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $message = $newStatus
            ? 'Organization user enabled successfully.'
            : 'Organization user disabled successfully.';

        $this->activityLogger->log(
            action: $newStatus ? 'user.enabled' : 'user.disabled',
            description: $newStatus
                ? "Enabled user {$user->name}."
                : "Disabled user {$user->name}.",
            subject: $user,
            organizationId: $organization->id,
            properties: [
                'old' => [
                    'is_active' => $oldStatus,
                ],
                'new' => [
                    'is_active' => $newStatus,
                ],
            ],
        );

        return redirect()
            ->route(
                $this->organizationUserRouteName('index'),
                $organization
            )
            ->with('success', $message);
    }

    /**
     * Soft-delete an organization user.
     *
     * The account is disabled before deletion so it cannot be used if it is
     * later restored accidentally. The user's roles and historical records
     * remain available.
     */
    public function destroy(
        Organization $organization,
        User $user
    ): RedirectResponse {
        $this->ensureUserBelongsToOrganization(
            $organization,
            $user
        );

        /*
         * BILLING_OWNER_ARCHIVE_PROTECTION
         *
         * Billing ownership must be transferred before this account can
         * be archived.
         */
        if (
            $this->isBillingOwner(
                $organization,
                $user
            )
        ) {
            return back()->withErrors([
                'delete' =>
                    'This user is the current Billing Owner. '
                    .'Assign another active Billing Owner under Billing '
                    .'before archiving this account.',
            ]);
        }

        /*
         * Prevent the final active Organization Admin from being archived.
         */
        if (
            $user->is_active
            && $this->isOrganizationAdmin($organization, $user)
            && $this->activeOrganizationAdminCount($organization) <= 1
        ) {
            return back()->withErrors([
                'delete' =>
                    'This user is the last active Organization Admin. '
                    .'Assign another active Organization Admin before '
                    .'archiving this account.',
            ]);
        }

        DB::transaction(function () use (
            $organization,
            $user
        ): void {
            $oldValues = [
                'is_active' => (bool) $user->is_active,
                'deleted_at' => $user->deleted_at?->toDateTimeString(),
            ];

            /*
             * Archived accounts are disabled before soft deletion.
             */
            $user->is_active = false;
            $user->save();

            $user->delete();

            $this->activityLogger->log(
                action: 'user.archived',
                description: "Archived user {$user->name}.",
                subject: $user,
                organizationId: $organization->id,
                properties: [
                    'old' => $oldValues,
                    'new' => [
                        'is_active' => false,
                        'deleted_at' =>
                            $user->deleted_at?->toDateTimeString(),
                    ],
                ],
            );
        });

        return redirect()
            ->route(
                $this->organizationUserRouteName('index'),
                $organization
            )
            ->with(
                'success',
                'Organization user archived successfully.'
            );
    }

    /**
     * Restore and enable a previously archived organization user.
     *
     * Restoration is intentionally a single action so an Organization Admin
     * does not need to restore the account and enable it separately.
     */
    public function restore(
        Organization $organization,
        User $user
    ): RedirectResponse {
        $this->ensureUserBelongsToOrganization(
            $organization,
            $user
        );

        /*
         * The restore route should only operate on deleted users.
         */
        abort_unless($user->trashed(), 404);

        DB::transaction(function () use (
            $organization,
            $user
        ): void {
            /*
             * Serialize restoration for this organization so concurrent
             * requests cannot exceed the subscription seat limit.
             */
            Organization::query()
                ->whereKey($organization->id)
                ->lockForUpdate()
                ->firstOrFail();

            $subscription = $organization
                ->subscription()
                ->with('plan')
                ->first();

            $maximumUsers = $subscription?->effectiveUserLimit();
            $currentUsers = $organization->users()->count();

            /*
             * Restoring an archived user consumes one organization seat.
             */
            if (
                $maximumUsers !== null
                && $currentUsers >= $maximumUsers
            ) {
                throw ValidationException::withMessages([
                    'subscription' =>
                        "This organization has reached its "
                        ."{$maximumUsers}-user subscription limit. "
                        .'Archive another user or upgrade the subscription '
                        .'before restoring this account.',
                ]);
            }

            app(PermissionRegistrar::class)
                ->setPermissionsTeamId($organization->id);

            $restoredRole = $user
                ->roles()
                ->where(
                    'roles.organization_id',
                    $organization->id
                )
                ->where('roles.guard_name', 'web')
                ->first();

            $maximumStaff = $subscription?->effectiveRoleLimit('Staff');

            if (
                $restoredRole?->name === 'Staff'
                && $maximumStaff !== null
            ) {
                /*
                 * Disabled Staff users occupy role seats. Archived users
                 * remain excluded until they are restored.
                 */
                $currentStaff = User::query()
                    ->where(
                        'users.organization_id',
                        $organization->id
                    )
                    ->whereHas(
                        'roles',
                        function ($query) use ($organization): void {
                            $query
                                ->where(
                                    'roles.organization_id',
                                    $organization->id
                                )
                                ->where(
                                    'roles.guard_name',
                                    'web'
                                )
                                ->where(
                                    'roles.name',
                                    'Staff'
                                );
                        }
                    )
                    ->count();

                if ($currentStaff >= $maximumStaff) {
                    throw ValidationException::withMessages([
                        'subscription' =>
                            'This organization has reached its Staff '
                            ."role limit of {$maximumStaff}. "
                            .'Archive another Staff user or upgrade the '
                            .'subscription before restoring this account.',
                    ]);
                }
            }

            $maximumConsentManagers =
                $subscription?->effectiveRoleLimit('Consent Manager');

            if (
                $restoredRole?->name === 'Consent Manager'
                && $maximumConsentManagers !== null
            ) {
                /*
                 * Disabled Consent Managers occupy role seats. Archived
                 * users remain excluded until they are restored.
                 */
                $currentConsentManagers = User::query()
                    ->where(
                        'users.organization_id',
                        $organization->id
                    )
                    ->whereHas(
                        'roles',
                        function ($query) use ($organization): void {
                            $query
                                ->where(
                                    'roles.organization_id',
                                    $organization->id
                                )
                                ->where(
                                    'roles.guard_name',
                                    'web'
                                )
                                ->where(
                                    'roles.name',
                                    'Consent Manager'
                                );
                        }
                    )
                    ->count();

                if (
                    $currentConsentManagers
                    >= $maximumConsentManagers
                ) {
                    throw ValidationException::withMessages([
                        'subscription' =>
                            'This organization has reached its Consent '
                            .'Manager role limit of '
                            ."{$maximumConsentManagers}. "
                            .'Archive another Consent Manager or upgrade '
                            .'the subscription before restoring this account.',
                    ]);
                }
            }

            $maximumAuditors =
                $subscription?->effectiveRoleLimit('Auditor');

            if (
                $restoredRole?->name === 'Auditor'
                && $maximumAuditors !== null
            ) {
                /*
                 * Disabled Auditors occupy role seats. Archived users
                 * remain excluded until they are restored.
                 */
                $currentAuditors = User::query()
                    ->where(
                        'users.organization_id',
                        $organization->id
                    )
                    ->whereHas(
                        'roles',
                        function ($query) use ($organization): void {
                            $query
                                ->where(
                                    'roles.organization_id',
                                    $organization->id
                                )
                                ->where(
                                    'roles.guard_name',
                                    'web'
                                )
                                ->where(
                                    'roles.name',
                                    'Auditor'
                                );
                        }
                    )
                    ->count();

                if ($currentAuditors >= $maximumAuditors) {
                    throw ValidationException::withMessages([
                        'subscription' =>
                            'This organization has reached its Auditor '
                            ."role limit of {$maximumAuditors}. "
                            .'Archive another Auditor or upgrade the '
                            .'subscription before restoring this account.',
                    ]);
                }
            }

            $oldValues = [
                'is_active' => (bool) $user->is_active,
                'deleted_at' => $user->deleted_at?->toDateTimeString(),
            ];

            $user->restore();

            /*
             * Restoring an archived account also re-enables it immediately.
             */
            $user->is_active = true;
            $user->save();

            $this->activityLogger->log(
                action: 'user.restored',
                description: "Restored and enabled user {$user->name}.",
                subject: $user,
                organizationId: $organization->id,
                properties: [
                    'old' => $oldValues,
                    'new' => [
                        'is_active' => true,
                        'deleted_at' => null,
                    ],
                ],
            );
        });

        return redirect()
            ->route(
                $this->organizationUserRouteName('index'),
                $organization
            )
            ->with(
                'success',
                'Organization user restored and enabled successfully.'
            );
    }

    /**
     * Confirm that the user belongs to the organization in the URL.
     *
     * This prevents a platform administrator from managing a user through
     * the wrong organization route.
     */
    private function ensureUserBelongsToOrganization(
        Organization $organization,
        User $user
    ): void {
        abort_if(
            $user->organization_id !== $organization->id,
            404
        );
    }

    /**
     * Determine whether the user currently holds the Billing Owner role.
     */
    private function isBillingOwner(
        Organization $organization,
        User $user
    ): bool {
        return $organization
            ->subscription()
            ->where(
                'billing_owner_user_id',
                $user->id
            )
            ->exists();
    }

    private function isOrganizationAdmin(
        Organization $organization,
        User $user
    ): bool {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($organization->id);

        return $user
            ->roles()
            ->where(
                'roles.organization_id',
                $organization->id
            )
            ->where(
                'roles.guard_name',
                'web'
            )
            ->where(
                'roles.name',
                'Organization Admin'
            )
            ->exists();
    }

    /**
     * Count active, non-deleted Organization Admin users.
     */
    private function activeOrganizationAdminCount(
        Organization $organization
    ): int {
        return User::query()
            ->where(
                'users.organization_id',
                $organization->id
            )
            ->where(
                'users.is_active',
                true
            )
            ->whereHas(
                'roles',
                function ($query) use ($organization): void {
                    $query
                        ->where(
                            'roles.organization_id',
                            $organization->id
                        )
                        ->where(
                            'roles.guard_name',
                            'web'
                        )
                        ->where(
                            'roles.name',
                            'Organization Admin'
                        );
                }
            )
            ->count();
    }

    /**
     * Resolve the correct user-management route namespace.
     */
    private function organizationUserRouteName(
        string $action
    ): string {
        return request()->routeIs('platform.*')
            ? "platform.organizations.users.{$action}"
            : "organization-users.{$action}";
    }

}
