<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\Organization;
use App\Models\SubscriptionPlan;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class OrganizationRegistrationService
{
    public function __construct(
        private readonly EvaluationStarterTemplateService $starterTemplates,
        private readonly PermissionRegistrar $permissionRegistrar
    ) {
    }

    /**
     * Create a new organization and its founding administrator.
     *
     * The founder email is also the initial organization email so all
     * registration methods share the same default organization identity.
     */
    public function registerFounder(
        string $organizationName,
        string $name,
        string $email,
        string $password,
        ?DateTimeInterface $emailVerifiedAt = null,
        ?string $googleId = null
    ): User {
        $previousTeamId =
            $this->permissionRegistrar
                ->getPermissionsTeamId();

        try {
            return DB::transaction(
                function () use (
                    $organizationName,
                    $name,
                    $email,
                    $password,
                    $emailVerifiedAt,
                    $googleId
                ): User {
                    $basicPlan =
                        SubscriptionPlan::query()
                            ->where(
                                'slug',
                                'basic'
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->first();

                    if ($basicPlan === null) {
                        throw new \RuntimeException(
                            'The active Basic subscription plan is unavailable.'
                        );
                    }

                    $organization =
                        Organization::create([
                            'name' =>
                                $organizationName,

                            'slug' =>
                                Str::slug(
                                    $organizationName
                                ).'-'.uniqid(),

                            /*
                             * The founder email is intentionally the
                             * new organization's initial/default email.
                             */
                            'email' =>
                                $email,
                        ]);

                    $administratorRole = null;

                    foreach (
                        OrganizationRole::cases()
                        as $roleDetails
                    ) {
                        $role =
                            Role::query()
                                ->firstOrCreate([
                                    'organization_id' =>
                                        $organization->id,

                                    'name' =>
                                        $roleDetails->label(),

                                    'guard_name' =>
                                        'web',
                                ]);

                        if (
                            $roleDetails
                            === OrganizationRole::
                                ORGANIZATION_ADMINISTRATOR
                        ) {
                            $administratorRole =
                                $role;
                        }
                    }

                    if ($administratorRole === null) {
                        throw new \RuntimeException(
                            'Organization Admin role could not be created.'
                        );
                    }

                    $user = User::create([
                        'organization_id' =>
                            $organization->id,

                        'platform_role_id' =>
                            null,

                        'name' =>
                            $name,

                        'email' =>
                            $email,

                        'google_id' =>
                            $googleId,

                        'password' =>
                            Hash::make(
                                $password
                            ),

                        'is_active' =>
                            true,
                    ]);

                    if ($emailVerifiedAt !== null) {
                        $user->forceFill([
                            'email_verified_at' =>
                                $emailVerifiedAt,
                        ])->save();
                    }

                    $this->permissionRegistrar
                        ->setPermissionsTeamId(
                            $organization->id
                        );

                    $user->assignRole(
                        $administratorRole
                    );

                    $organization
                        ->subscription()
                        ->create([
                            'subscription_plan_id' =>
                                $basicPlan->id,

                            'billing_owner_user_id' =>
                                $user->id,

                            'requires_plan_selection' =>
                                true,

                            'plan_selected_at' =>
                                null,

                            'status' =>
                                OrganizationSubscriptionStatus::
                                    EVALUATION,

                            'payment_status' =>
                                SubscriptionPaymentStatus::
                                    UNPAID,

                            'starts_at' =>
                                now(),
                        ]);

                    $this->starterTemplates
                        ->provision(
                            $organization
                        );

                    $this->permissionRegistrar
                        ->forgetCachedPermissions();

                    return $user;
                }
            );
        } finally {
            $this->permissionRegistrar
                ->setPermissionsTeamId(
                    $previousTeamId
                );
        }
    }
}
