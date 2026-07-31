<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\PlatformRole;
use App\Models\User;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformStaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private PlatformRole $superAdminRole;

    private PlatformRole $billingRole;

    private PlatformRole $supportRole;

    private PlatformRole $auditorRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PlatformRoleSeeder::class
        );

        $this->superAdminRole =
            $this->role(
                'super-admin'
            );

        $this->billingRole =
            $this->role(
                'billing'
            );

        $this->supportRole =
            $this->role(
                'support'
            );

        $this->auditorRole =
            $this->role(
                'platform-auditor'
            );

        $this->superAdmin =
            User::factory()->create([
                'organization_id' =>
                    null,

                'platform_role_id' =>
                    $this
                        ->superAdminRole
                        ->id,

                'name' =>
                    'Primary Super Admin',

                'email' =>
                    'primary-admin@example.com',

                'is_active' =>
                    true,
            ]);
    }

    public function test_super_admin_can_view_platform_staff_page(): void
    {
        $this
            ->actingAs(
                $this->superAdmin
            )
            ->get(
                route(
                    'platform.staff.index'
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Platform Staff'
            )
            ->assertSeeText(
                'Add Platform Staff'
            )
            ->assertSeeText(
                'Super Admin'
            )
            ->assertSeeText(
                'Billing'
            )
            ->assertSeeText(
                'Support'
            )
            ->assertSeeText(
                'Platform Auditor'
            )
            ->assertSee(
                route(
                    'platform.staff.create'
                ),
                false
            );
    }


    public function test_super_admin_role_has_prominent_red_warning(): void
    {
        $this
            ->actingAs(
                $this->superAdmin
            )
            ->get(
                route(
                    'platform.staff.create'
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Warning: unrestricted platform access'
            )
            ->assertSeeText(
                'Assign this role only to fully trusted personnel.'
            )
            ->assertSee(
                'id="super-admin-role-warning"',
                false
            )
            ->assertSee(
                'border-color:#dc2626',
                false
            );

        $this
            ->actingAs(
                $this->superAdmin
            )
            ->get(
                route(
                    'platform.staff.edit',
                    $this->superAdmin
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Warning: unrestricted platform access'
            )
            ->assertSeeText(
                'The final active Super Admin cannot be demoted or disabled.'
            )
            ->assertSee(
                'background-color:#fef2f2',
                false
            )
            ->assertSee(
                'color:#7f1d1d',
                false
            );
    }


    public function test_platform_staff_is_limited_to_four_accounts_including_disabled_accounts(): void
    {
        $this->createStaff(
            $this->billingRole
        );

        $this->createStaff(
            $this->supportRole,
            [
                'is_active' =>
                    false,
            ]
        );

        $this->createStaff(
            $this->auditorRole
        );

        $this->assertSame(
            4,
            User::query()
                ->whereNull(
                    'organization_id'
                )
                ->whereNotNull(
                    'platform_role_id'
                )
                ->count()
        );

        $this
            ->actingAs(
                $this->superAdmin
            )
            ->get(
                route(
                    'platform.staff.index'
                )
            )
            ->assertOk()
            ->assertSeeText(
                '4 of 4 accounts used'
            )
            ->assertSeeText(
                'Platform staff limit reached.'
            )
            ->assertSeeText(
                'Maximum reached'
            );

        $this
            ->actingAs(
                $this->superAdmin
            )
            ->get(
                route(
                    'platform.staff.create'
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Platform staff limit reached.'
            )
            ->assertSeeText(
                'Maximum of Four Reached'
            );

        $this
            ->actingAs(
                $this->superAdmin
            )
            ->from(
                route(
                    'platform.staff.create'
                )
            )
            ->post(
                route(
                    'platform.staff.store'
                ),
                [
                    'name' =>
                        'Fifth Platform User',

                    'email' =>
                        'fifth-platform-user@example.com',

                    'platform_role_id' =>
                        $this->billingRole->id,

                    'password' =>
                        'StrongPass1!',

                    'password_confirmation' =>
                        'StrongPass1!',
                ]
            )
            ->assertRedirect(
                route(
                    'platform.staff.create'
                )
            )
            ->assertSessionHasErrors([
                'platform_staff',
            ]);

        $this->assertDatabaseMissing(
            'users',
            [
                'email' =>
                    'fifth-platform-user@example.com',
            ]
        );

        $this->assertSame(
            4,
            User::query()
                ->whereNull(
                    'organization_id'
                )
                ->whereNotNull(
                    'platform_role_id'
                )
                ->count()
        );
    }

    public function test_super_admin_can_create_billing_staff_account(): void
    {
        $this
            ->actingAs(
                $this->superAdmin
            )
            ->post(
                route(
                    'platform.staff.store'
                ),
                [
                    'name' =>
                        'Billing Specialist',

                    'email' =>
                        'billing-staff@example.com',

                    'platform_role_id' =>
                        $this->billingRole->id,

                    'password' =>
                        'StrongPass1!',

                    'password_confirmation' =>
                        'StrongPass1!',
                ]
            )
            ->assertRedirect(
                route(
                    'platform.staff.index'
                )
            )
            ->assertSessionHas(
                'success'
            );

        $billingStaff =
            User::query()
                ->where(
                    'email',
                    'billing-staff@example.com'
                )
                ->sole();

        $this->assertNull(
            $billingStaff->organization_id
        );

        $this->assertSame(
            $this->billingRole->id,
            $billingStaff
                ->platform_role_id
        );

        $this->assertTrue(
            $billingStaff->is_active
        );

        $activity =
            ActivityLog::query()
                ->where(
                    'action',
                    'platform.staff_created'
                )
                ->sole();

        $this->assertSame(
            $billingStaff->id,
            $activity->subject_id
        );

        $this->assertSame(
            'billing',
            data_get(
                $activity->properties,
                'new.role'
            )
        );
    }

    public function test_super_admin_can_create_support_staff_account(): void
    {
        $this
            ->actingAs(
                $this->superAdmin
            )
            ->post(
                route(
                    'platform.staff.store'
                ),
                [
                    'name' =>
                        'Support Specialist',

                    'email' =>
                        'support-staff@example.com',

                    'platform_role_id' =>
                        $this->supportRole->id,

                    'password' =>
                        'StrongPass1!',

                    'password_confirmation' =>
                        'StrongPass1!',
                ]
            )
            ->assertRedirect(
                route(
                    'platform.staff.index'
                )
            );

        $this->assertDatabaseHas(
            'users',
            [
                'email' =>
                    'support-staff@example.com',

                'organization_id' =>
                    null,

                'platform_role_id' =>
                    $this->supportRole->id,

                'is_active' =>
                    true,
            ]
        );
    }

    public function test_super_admin_can_change_platform_staff_role(): void
    {
        $staffMember =
            $this->createStaff(
                $this->billingRole
            );

        $this
            ->actingAs(
                $this->superAdmin
            )
            ->patch(
                route(
                    'platform.staff.update',
                    $staffMember
                ),
                [
                    'name' =>
                        'Updated Staff Member',

                    'email' =>
                        $staffMember->email,

                    'platform_role_id' =>
                        $this->auditorRole->id,

                    'password' =>
                        '',

                    'password_confirmation' =>
                        '',
                ]
            )
            ->assertRedirect(
                route(
                    'platform.staff.index'
                )
            );

        $staffMember->refresh();

        $this->assertSame(
            'Updated Staff Member',
            $staffMember->name
        );

        $this->assertSame(
            $this->auditorRole->id,
            $staffMember
                ->platform_role_id
        );
    }

    public function test_super_admin_can_disable_and_enable_staff_account(): void
    {
        $staffMember =
            $this->createStaff(
                $this->supportRole
            );

        $this
            ->actingAs(
                $this->superAdmin
            )
            ->patch(
                route(
                    'platform.staff.status',
                    $staffMember
                ),
                [
                    'is_active' =>
                        '0',
                ]
            )
            ->assertRedirect(
                route(
                    'platform.staff.index'
                )
            );

        $this->assertFalse(
            $staffMember
                ->fresh()
                ->is_active
        );

        $this
            ->actingAs(
                $this->superAdmin
            )
            ->patch(
                route(
                    'platform.staff.status',
                    $staffMember
                ),
                [
                    'is_active' =>
                        '1',
                ]
            )
            ->assertRedirect(
                route(
                    'platform.staff.index'
                )
            );

        $this->assertTrue(
            $staffMember
                ->fresh()
                ->is_active
        );
    }

    public function test_final_active_super_admin_cannot_be_demoted(): void
    {
        $this
            ->actingAs(
                $this->superAdmin
            )
            ->from(
                route(
                    'platform.staff.edit',
                    $this->superAdmin
                )
            )
            ->patch(
                route(
                    'platform.staff.update',
                    $this->superAdmin
                ),
                [
                    'name' =>
                        $this->superAdmin->name,

                    'email' =>
                        $this->superAdmin->email,

                    'platform_role_id' =>
                        $this->billingRole->id,

                    'password' =>
                        '',

                    'password_confirmation' =>
                        '',
                ]
            )
            ->assertRedirect(
                route(
                    'platform.staff.edit',
                    $this->superAdmin
                )
            )
            ->assertSessionHasErrors([
                'platform_role_id',
            ]);

        $this->assertSame(
            $this->superAdminRole->id,
            $this->superAdmin
                ->fresh()
                ->platform_role_id
        );
    }

    public function test_final_active_super_admin_cannot_be_disabled(): void
    {
        $secondAdmin =
            $this->createStaff(
                $this->superAdminRole,
                [
                    'is_active' =>
                        false,
                ]
            );

        $this
            ->actingAs(
                $this->superAdmin
            )
            ->patch(
                route(
                    'platform.staff.status',
                    $secondAdmin
                ),
                [
                    'is_active' =>
                        '1',
                ]
            )
            ->assertRedirect();

        $this
            ->actingAs(
                $secondAdmin->fresh()
            )
            ->patch(
                route(
                    'platform.staff.status',
                    $this->superAdmin
                ),
                [
                    'is_active' =>
                        '0',
                ]
            )
            ->assertRedirect();

        $this->assertFalse(
            $this->superAdmin
                ->fresh()
                ->is_active
        );

        $this
            ->actingAs(
                $secondAdmin->fresh()
            )
            ->patch(
                route(
                    'platform.staff.status',
                    $secondAdmin
                ),
                [
                    'is_active' =>
                        '0',
                ]
            )
            ->assertSessionHasErrors([
                'status',
            ]);

        $this->assertTrue(
            $secondAdmin
                ->fresh()
                ->is_active
        );
    }

    public function test_organization_user_cannot_manage_platform_staff(): void
    {
        $organization =
            Organization::query()->create([
                'name' =>
                    'Restricted Organization',

                'slug' =>
                    'restricted-organization',
            ]);

        $organizationUser =
            User::factory()->create([
                'organization_id' =>
                    $organization->id,

                'platform_role_id' =>
                    null,

                'is_active' =>
                    true,
            ]);

        $this
            ->actingAs(
                $organizationUser
            )
            ->get(
                route(
                    'platform.staff.index'
                )
            )
            ->assertForbidden();

        $this
            ->actingAs(
                $organizationUser
            )
            ->post(
                route(
                    'platform.staff.store'
                ),
                [
                    'name' =>
                        'Invalid Staff',

                    'email' =>
                        'invalid@example.com',

                    'platform_role_id' =>
                        $this->supportRole->id,

                    'password' =>
                        'StrongPass1!',

                    'password_confirmation' =>
                        'StrongPass1!',
                ]
            )
            ->assertForbidden();
    }

    private function role(
        string $slug
    ): PlatformRole {
        return PlatformRole::query()
            ->where(
                'slug',
                $slug
            )
            ->firstOrFail();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createStaff(
        PlatformRole $role,
        array $overrides = []
    ): User {
        return User::factory()->create(
            array_merge(
                [
                    'organization_id' =>
                        null,

                    'platform_role_id' =>
                        $role->id,

                    'is_active' =>
                        true,
                ],
                $overrides
            )
        );
    }
}
