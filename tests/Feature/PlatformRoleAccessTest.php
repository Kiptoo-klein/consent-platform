<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PlatformRole;
use App\Models\User;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $billing;

    private User $support;

    private User $auditor;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            PlatformRoleSeeder::class
        );

        $this->superAdmin =
            $this->platformUser(
                'super-admin'
            );

        $this->billing =
            $this->platformUser(
                'billing'
            );

        $this->support =
            $this->platformUser(
                'support'
            );

        $this->auditor =
            $this->platformUser(
                'platform-auditor'
            );

        $identifier =
            Str::lower(
                Str::random(10)
            );

        $this->organization =
            Organization::query()->create([
                'name' =>
                    "Role Access Organization {$identifier}",

                'slug' =>
                    "role-access-{$identifier}",
            ]);
    }

    public function test_all_four_roles_can_access_platform_overview(): void
    {
        foreach (
            $this->platformUsers()
            as $platformUser
        ) {
            $this
                ->actingAs(
                    $platformUser
                )
                ->get(
                    route(
                        'platform.dashboard'
                    )
                )
                ->assertOk();

            $this
                ->actingAs(
                    $platformUser
                )
                ->get(
                    route(
                        'platform.organizations.index'
                    )
                )
                ->assertOk();

            $this
                ->actingAs(
                    $platformUser
                )
                ->get(
                    route(
                        'platform.organizations.show',
                        $this->organization
                    )
                )
                ->assertOk();
        }
    }

    public function test_billing_role_can_access_billing_management(): void
    {
        foreach ([
            'platform.billing.index',
            'platform.subscription-plans.index',
            'platform.subscription-invoice-reminder-settings.index',
            'platform.subscription-payment-settings.index',
        ] as $routeName) {
            $this
                ->actingAs(
                    $this->billing
                )
                ->get(
                    route(
                        $routeName
                    )
                )
                ->assertOk();
        }

        $this
            ->actingAs(
                $this->billing
            )
            ->get(
                route(
                    'platform.staff.index'
                )
            )
            ->assertForbidden();

        $this
            ->actingAs(
                $this->billing
            )
            ->get(
                route(
                    'platform.activity-logs.index'
                )
            )
            ->assertForbidden();
    }

    public function test_support_role_has_read_only_organization_access(): void
    {
        $this
            ->actingAs(
                $this->support
            )
            ->get(
                route(
                    'platform.organizations.users.index',
                    $this->organization
                )
            )
            ->assertOk();

        $this
            ->actingAs(
                $this->support
            )
            ->get(
                route(
                    'platform.organizations.users.create',
                    $this->organization
                )
            )
            ->assertForbidden();

        $this
            ->actingAs(
                $this->support
            )
            ->get(
                route(
                    'platform.billing.index'
                )
            )
            ->assertForbidden();

        $this
            ->actingAs(
                $this->support
            )
            ->get(
                route(
                    'platform.staff.index'
                )
            )
            ->assertForbidden();
    }

    public function test_platform_auditor_has_read_only_audit_access(): void
    {
        $this
            ->actingAs(
                $this->auditor
            )
            ->get(
                route(
                    'platform.billing.index'
                )
            )
            ->assertOk();

        $this
            ->actingAs(
                $this->auditor
            )
            ->get(
                route(
                    'platform.activity-logs.index'
                )
            )
            ->assertOk();

        $this
            ->actingAs(
                $this->auditor
            )
            ->get(
                route(
                    'platform.security.status'
                )
            )
            ->assertOk();

        $this
            ->actingAs(
                $this->auditor
            )
            ->patch(
                route(
                    'platform.subscription-payment-settings.update'
                ),
                []
            )
            ->assertForbidden();

        $this
            ->actingAs(
                $this->auditor
            )
            ->get(
                route(
                    'platform.staff.index'
                )
            )
            ->assertForbidden();
    }

    public function test_super_admin_retains_full_platform_access(): void
    {
        foreach ([
            'platform.staff.index',
            'platform.subscription-payment-settings.index',
            'platform.activity-logs.index',
            'platform.email-diagnostics.index',
            'platform.production-readiness.index',
        ] as $routeName) {
            $this
                ->actingAs(
                    $this->superAdmin
                )
                ->get(
                    route(
                        $routeName
                    )
                )
                ->assertOk();
        }
    }


    public function test_platform_navigation_is_filtered_by_role(): void
    {
        $superAdminResponse =
            $this
                ->actingAs(
                    $this->superAdmin
                )
                ->get(
                    route(
                        'platform.dashboard'
                    )
                )
                ->assertOk();

        foreach ([
            'platform.dashboard',
            'platform.organizations.index',
            'platform.billing.index',
            'platform.subscription-plans.index',
            'platform.subscription-invoice-reminder-settings.index',
            'platform.subscription-payment-settings.index',
            'platform.staff.index',
            'platform.activity-logs.index',
            'platform.security.status',
            'platform.email-diagnostics.index',
            'platform.production-readiness.index',
        ] as $routeName) {
            $superAdminResponse->assertSee(
                'data-navigation-route="'
                .$routeName
                .'"',
                false
            );
        }

        $billingResponse =
            $this
                ->actingAs(
                    $this->billing
                )
                ->get(
                    route(
                        'platform.dashboard'
                    )
                )
                ->assertOk();

        foreach ([
            'platform.dashboard',
            'platform.organizations.index',
            'platform.billing.index',
            'platform.subscription-plans.index',
            'platform.subscription-invoice-reminder-settings.index',
            'platform.subscription-payment-settings.index',
        ] as $routeName) {
            $billingResponse->assertSee(
                'data-navigation-route="'
                .$routeName
                .'"',
                false
            );
        }

        foreach ([
            'platform.staff.index',
            'platform.activity-logs.index',
            'platform.security.status',
            'platform.email-diagnostics.index',
            'platform.production-readiness.index',
        ] as $routeName) {
            $billingResponse->assertDontSee(
                'data-navigation-route="'
                .$routeName
                .'"',
                false
            );
        }

        $supportResponse =
            $this
                ->actingAs(
                    $this->support
                )
                ->get(
                    route(
                        'platform.dashboard'
                    )
                )
                ->assertOk();

        foreach ([
            'platform.dashboard',
            'platform.organizations.index',
        ] as $routeName) {
            $supportResponse->assertSee(
                'data-navigation-route="'
                .$routeName
                .'"',
                false
            );
        }

        foreach ([
            'platform.billing.index',
            'platform.subscription-plans.index',
            'platform.subscription-invoice-reminder-settings.index',
            'platform.subscription-payment-settings.index',
            'platform.staff.index',
            'platform.activity-logs.index',
            'platform.security.status',
            'platform.email-diagnostics.index',
            'platform.production-readiness.index',
        ] as $routeName) {
            $supportResponse->assertDontSee(
                'data-navigation-route="'
                .$routeName
                .'"',
                false
            );
        }

        $auditorResponse =
            $this
                ->actingAs(
                    $this->auditor
                )
                ->get(
                    route(
                        'platform.dashboard'
                    )
                )
                ->assertOk();

        foreach ([
            'platform.dashboard',
            'platform.organizations.index',
            'platform.billing.index',
            'platform.activity-logs.index',
            'platform.security.status',
        ] as $routeName) {
            $auditorResponse->assertSee(
                'data-navigation-route="'
                .$routeName
                .'"',
                false
            );
        }

        foreach ([
            'platform.subscription-plans.index',
            'platform.subscription-invoice-reminder-settings.index',
            'platform.subscription-payment-settings.index',
            'platform.staff.index',
            'platform.email-diagnostics.index',
            'platform.production-readiness.index',
        ] as $routeName) {
            $auditorResponse->assertDontSee(
                'data-navigation-route="'
                .$routeName
                .'"',
                false
            );
        }
    }


    public function test_platform_dashboard_actions_and_statistics_follow_role_access(): void
    {
        $superAdminResponse =
            $this
                ->actingAs(
                    $this->superAdmin
                )
                ->get(
                    route(
                        'platform.dashboard'
                    )
                )
                ->assertOk();

        foreach ([
            'organizations',
            'billing',
            'activity',
            'security',
        ] as $action) {
            $superAdminResponse->assertSee(
                'data-dashboard-action="'
                .$action
                .'"',
                false
            );
        }

        foreach ([
            'organizations',
            'users',
            'activity',
        ] as $section) {
            $superAdminResponse->assertSee(
                'data-dashboard-section="'
                .$section
                .'"',
                false
            );
        }

        $billingResponse =
            $this
                ->actingAs(
                    $this->billing
                )
                ->get(
                    route(
                        'platform.dashboard'
                    )
                )
                ->assertOk();

        foreach ([
            'organizations',
            'billing',
        ] as $action) {
            $billingResponse->assertSee(
                'data-dashboard-action="'
                .$action
                .'"',
                false
            );
        }

        foreach ([
            'activity',
            'security',
        ] as $action) {
            $billingResponse->assertDontSee(
                'data-dashboard-action="'
                .$action
                .'"',
                false
            );
        }

        $billingResponse
            ->assertSee(
                'data-dashboard-section="organizations"',
                false
            )
            ->assertDontSee(
                'data-dashboard-section="users"',
                false
            )
            ->assertDontSee(
                'data-dashboard-section="activity"',
                false
            );

        $supportResponse =
            $this
                ->actingAs(
                    $this->support
                )
                ->get(
                    route(
                        'platform.dashboard'
                    )
                )
                ->assertOk();

        $supportResponse
            ->assertSee(
                'data-dashboard-action="organizations"',
                false
            )
            ->assertDontSee(
                'data-dashboard-action="billing"',
                false
            )
            ->assertDontSee(
                'data-dashboard-action="activity"',
                false
            )
            ->assertDontSee(
                'data-dashboard-action="security"',
                false
            )
            ->assertSee(
                'data-dashboard-section="organizations"',
                false
            )
            ->assertSee(
                'data-dashboard-section="users"',
                false
            )
            ->assertDontSee(
                'data-dashboard-section="activity"',
                false
            );

        $auditorResponse =
            $this
                ->actingAs(
                    $this->auditor
                )
                ->get(
                    route(
                        'platform.dashboard'
                    )
                )
                ->assertOk();

        foreach ([
            'organizations',
            'billing',
            'activity',
            'security',
        ] as $action) {
            $auditorResponse->assertSee(
                'data-dashboard-action="'
                .$action
                .'"',
                false
            );
        }

        foreach ([
            'organizations',
            'users',
            'activity',
        ] as $section) {
            $auditorResponse->assertSee(
                'data-dashboard-section="'
                .$section
                .'"',
                false
            );
        }
    }

    public function test_organization_user_cannot_access_platform_routes(): void
    {
        $organizationUser =
            User::factory()->create([
                'organization_id' =>
                    $this->organization->id,

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
                    'platform.dashboard'
                )
            )
            ->assertForbidden();
    }

    /**
     * @return array<int, User>
     */
    private function platformUsers(): array
    {
        return [
            $this->superAdmin,
            $this->billing,
            $this->support,
            $this->auditor,
        ];
    }

    private function platformUser(
        string $roleSlug
    ): User {
        $role =
            PlatformRole::query()
                ->where(
                    'slug',
                    $roleSlug
                )
                ->firstOrFail();

        return User::factory()->create([
            'organization_id' =>
                null,

            'platform_role_id' =>
                $role->id,

            'is_active' =>
                true,
        ]);
    }
}
