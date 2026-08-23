<?php

namespace Tests\Feature;

use App\Http\Controllers\Platform\PlatformOrganizationUserController;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OrganizationBillingOwnerAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $organizationAdmin;

    private OrganizationSubscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            SubscriptionPlanSeeder::class
        );

        $this->post('/register', [
            'organization_name' =>
                'Billing Role Clinic',

            'name' =>
                'Billing Role Administrator',

            'email' =>
                'billing-role-admin@example.com',

            'password' =>
                'StrongPass1!',

            'password_confirmation' =>
                'StrongPass1!',
        ]);

        $this->verifyAuthenticatedUser();

        $this->organization = Organization::query()
            ->where(
                'name',
                'Billing Role Clinic'
            )
            ->firstOrFail();

        $this->organizationAdmin = User::query()
            ->where(
                'email',
                'billing-role-admin@example.com'
            )
            ->firstOrFail();

        $this->subscription = $this
            ->organization
            ->subscription()
            ->firstOrFail();
    }

    public function test_billing_page_displays_separate_billing_role(): void
    {
        $this
            ->actingAs($this->organizationAdmin)
            ->get(
                route('organization-billing.index')
            )
            ->assertOk()
            ->assertSeeText('Billing Role')
            ->assertSeeText('Billing Owner')
            ->assertSeeText('Single assignment')
            ->assertSeeText(
                'Their normal organization role will not be changed.'
            )
            ->assertSeeText(
                $this->organizationAdmin->name
            );
    }

    public function test_admin_can_assign_active_user_as_billing_owner(): void
    {
        $staff = $this->createOrganizationUser(
            'assigned-billing-owner@example.com'
        );

        $response = $this
            ->actingAs($this->organizationAdmin)
            ->patch(
                route(
                    'organization-billing.owner.update'
                ),
                [
                    'billing_owner_user_id' =>
                        $staff->id,
                ]
            );

        $response->assertRedirect(
            route('organization-billing.index')
        );

        $this->assertSame(
            $staff->id,
            $this->subscription
                ->fresh()
                ->billing_owner_user_id
        );

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(
                $this->organization->id
            );

        $this->assertTrue(
            $staff->hasRole('Staff')
        );

        $this->assertFalse(
            $staff->hasRole('Organization Admin')
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'organization_id' =>
                    $this->organization->id,

                'action' =>
                    'organization.billing_owner_assigned',

                'subject_id' =>
                    $this->subscription->id,
            ]
        );

        $this
            ->actingAs($staff)
            ->get(
                route('organization-billing.index')
            )
            ->assertOk();

        /*
         * Organization Admin retains billing oversight after assignment.
         */
        $this
            ->actingAs($this->organizationAdmin)
            ->get(
                route('organization-billing.index')
            )
            ->assertOk();
    }

    public function test_unassigned_user_cannot_access_billing(): void
    {
        $billingOwner = $this->createOrganizationUser(
            'billing-owner-access@example.com'
        );

        $otherUser = $this->createOrganizationUser(
            'other-billing-user@example.com'
        );

        $this->subscription->update([
            'billing_owner_user_id' =>
                $billingOwner->id,
        ]);

        $this
            ->actingAs($billingOwner)
            ->get(
                route('organization-billing.index')
            )
            ->assertOk();

        $this
            ->actingAs($otherUser)
            ->get(
                route('organization-billing.index')
            )
            ->assertForbidden();
    }

    public function test_non_admin_owner_cannot_reassign_role(): void
    {
        $billingOwner = $this->createOrganizationUser(
            'non-admin-owner@example.com'
        );

        $replacement = $this->createOrganizationUser(
            'non-admin-replacement@example.com'
        );

        $this->subscription->update([
            'billing_owner_user_id' =>
                $billingOwner->id,
        ]);

        $this
            ->actingAs($billingOwner)
            ->patch(
                route(
                    'organization-billing.owner.update'
                ),
                [
                    'billing_owner_user_id' =>
                        $replacement->id,
                ]
            )
            ->assertForbidden();

        $this->assertSame(
            $billingOwner->id,
            $this->subscription
                ->fresh()
                ->billing_owner_user_id
        );
    }

    public function test_inactive_and_foreign_users_are_rejected(): void
    {
        $inactive = $this->createOrganizationUser(
            'inactive-billing-owner@example.com',
            false
        );

        $this
            ->actingAs($this->organizationAdmin)
            ->patch(
                route(
                    'organization-billing.owner.update'
                ),
                [
                    'billing_owner_user_id' =>
                        $inactive->id,
                ]
            )
            ->assertSessionHasErrors(
                'billing_owner_user_id'
            );

        $otherOrganization =
            Organization::query()->create([
                'name' =>
                    'Foreign Billing Organization',

                'slug' =>
                    'foreign-billing-'
                    .Str::lower(Str::random(10)),
            ]);

        $foreignUser = User::factory()->create([
            'organization_id' =>
                $otherOrganization->id,

            'platform_role_id' => null,
            'is_active' => true,
        ]);

        $this
            ->actingAs($this->organizationAdmin)
            ->patch(
                route(
                    'organization-billing.owner.update'
                ),
                [
                    'billing_owner_user_id' =>
                        $foreignUser->id,
                ]
            )
            ->assertSessionHasErrors(
                'billing_owner_user_id'
            );
    }

    public function test_current_owner_cannot_be_disabled_or_archived(): void
    {
        $billingOwner = $this->createOrganizationUser(
            'protected-billing-owner@example.com'
        );

        $this->subscription->update([
            'billing_owner_user_id' =>
                $billingOwner->id,
        ]);

        $statusRoute = route(
            $this->organizationUserRouteFor(
                'updateStatus'
            ),
            [
                'organization' =>
                    $this->organization,

                'user' =>
                    $billingOwner,
            ]
        );

        $this
            ->actingAs($this->organizationAdmin)
            ->patch(
                $statusRoute,
                [
                    'is_active' => false,
                ]
            )
            ->assertSessionHasErrors('status');

        $this->assertTrue(
            (bool) $billingOwner
                ->fresh()
                ->is_active
        );

        $archiveRoute = route(
            $this->organizationUserRouteFor(
                'destroy'
            ),
            [
                'organization' =>
                    $this->organization,

                'user' =>
                    $billingOwner,
            ]
        );

        $this
            ->actingAs($this->organizationAdmin)
            ->delete($archiveRoute)
            ->assertSessionHasErrors('delete');

        $this->assertFalse(
            $billingOwner
                ->fresh()
                ->trashed()
        );
    }

    private function createOrganizationUser(
        string $email,
        bool $isActive = true
    ): User {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId(
                $this->organization->id
            );

        $staffRole = Role::query()
            ->where(
                'organization_id',
                $this->organization->id
            )
            ->where('guard_name', 'web')
            ->where('name', 'Staff')
            ->firstOrFail();

        $user = User::factory()->create([
            'organization_id' =>
                $this->organization->id,

            'platform_role_id' => null,
            'email' => $email,
            'is_active' => $isActive,
        ]);

        $user->assignRole($staffRole);

        return $user;
    }

    private function organizationUserRouteFor(
        string $method
    ): string {
        $action =
            PlatformOrganizationUserController::class
            ."@{$method}";

        foreach (
            RouteFacade::getRoutes()
            as $route
        ) {
            $name = $route->getName();

            if (
                is_string($name)
                && str_starts_with(
                    $name,
                    'organization-users.'
                )
                && $route->getActionName() === $action
            ) {
                return $name;
            }
        }

        $this->fail(
            "Organization user route for {$method} was not found."
        );
    }
}
