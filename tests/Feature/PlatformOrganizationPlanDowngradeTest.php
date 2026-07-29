<?php

namespace Tests\Feature;

use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\PlatformRole;
use App\Models\SigningStation;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlatformOrganizationPlanDowngradeTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $organizationAdmin;

    private User $platformAdmin;

    private OrganizationSubscription $subscription;

    private SubscriptionPlan $basicPlan;

    private SubscriptionPlan $growthPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PlatformRoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        $this->post('/register', [
            'organization_name' => 'Downgrade Test Clinic',
            'name' => 'Organization Administrator',
            'email' => 'downgrade-admin@example.com',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
        ]);

        $this->organization = Organization::query()
            ->where('name', 'Downgrade Test Clinic')
            ->firstOrFail();

        $this->organizationAdmin = User::query()
            ->where('email', 'downgrade-admin@example.com')
            ->firstOrFail();

        $this->subscription = $this->organization
            ->subscription()
            ->with('plan')
            ->firstOrFail();

        $this->basicPlan = SubscriptionPlan::query()
            ->where('slug', 'basic')
            ->firstOrFail();

        $this->growthPlan = SubscriptionPlan::query()
            ->where('slug', 'growth')
            ->firstOrFail();

        /*
         * Usage is first created under Growth capacity. The tests then
         * downgrade the organization to Basic.
         */
        $this->subscription->update([
            'subscription_plan_id' => $this->growthPlan->id,
        ]);

        $superAdminRole = PlatformRole::query()
            ->where('slug', 'super-admin')
            ->firstOrFail();

        $this->platformAdmin = User::factory()->create([
            'organization_id' => null,
            'platform_role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);
    }

    public function test_platform_admin_can_downgrade_without_deleting_existing_usage(): void
    {
        $this->populateUsageAboveBasicLimits();

        $response = $this
            ->actingAs($this->platformAdmin)
            ->patch(
                "/platform/organizations/"
                    ."{$this->organization->id}/subscription-plan",
                [
                    'subscription_plan_id' =>
                        $this->basicPlan->id,
                ]
            );

        $response
            ->assertRedirect(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            )
            ->assertSessionHas('success');

        $this->assertSame(
            $this->basicPlan->id,
            $this->subscription
                ->fresh()
                ->subscription_plan_id
        );

        /*
         * The downgrade must never delete, archive, pause, or disable
         * existing organization records automatically.
         */
        $this->assertSame(
            8,
            $this->organization->users()->count()
        );

        $this->assertSame(
            2,
            SigningStation::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->where('active', true)
                ->count()
        );
    }

    public function test_inactive_subscription_plan_cannot_be_assigned(): void
    {
        $inactivePlan = SubscriptionPlan::create([
            'name' => 'Retired Legacy',
            'slug' => 'retired-legacy',
            'description' => 'No longer available.',
            'max_users' => 2,
            'max_consent_managers' => 1,
            'max_staff' => 1,
            'max_auditors' => 1,
            'max_active_kiosks' => 1,
            'is_active' => false,
            'sort_order' => 99,
        ]);

        $response = $this
            ->actingAs($this->platformAdmin)
            ->patch(
                "/platform/organizations/"
                    ."{$this->organization->id}/subscription-plan",
                [
                    'subscription_plan_id' =>
                        $inactivePlan->id,
                ]
            );

        $response->assertSessionHasErrors(
            'subscription_plan_id'
        );

        $this->assertSame(
            $this->growthPlan->id,
            $this->subscription
                ->fresh()
                ->subscription_plan_id
        );
    }

    public function test_platform_page_warns_when_current_usage_exceeds_the_selected_plan(): void
    {
        $this->populateUsageAboveBasicLimits();

        $this->subscription->update([
            'subscription_plan_id' => $this->basicPlan->id,
        ]);

        $response = $this
            ->actingAs($this->platformAdmin)
            ->get(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            );

        $response->assertOk();
        $response->assertSeeText('Change Subscription Plan');
        $response->assertSeeText('Plan capacity exceeded');

        $response->assertSeeTextInOrder([
            'Total users',
            '8 of 5',
            '3 over limit',
            'Consent Managers',
            '2 of 1',
            '1 over limit',
            'Staff',
            '3 of 2',
            '1 over limit',
            'Auditors',
            '2 of 1',
            '1 over limit',
            'Active kiosks',
            '2 of 1',
            '1 over limit',
        ]);
    }

    public function test_organization_subscription_page_shows_true_overages(): void
    {
        $this->populateUsageAboveBasicLimits();

        $this->subscription->update([
            'subscription_plan_id' => $this->basicPlan->id,
        ]);

        $response = $this
            ->actingAs($this->organizationAdmin)
            ->get(
                route('organization-subscription.show')
            );

        $response->assertOk();

        $response->assertSeeTextInOrder([
            'Total users',
            '8 of 5',
            '3 over limit',
            'Consent Managers',
            '2 of 1',
            '1 over limit',
            'Staff',
            '3 of 2',
            '1 over limit',
            'Auditors',
            '2 of 1',
            '1 over limit',
            'Active kiosks',
            '2 of 1',
            '1 over limit',
        ]);
    }

    public function test_inactive_plans_are_not_offered_in_the_plan_form(): void
    {
        SubscriptionPlan::create([
            'name' => 'Hidden Retired Plan',
            'slug' => 'hidden-retired-plan',
            'description' => 'Unavailable for assignment.',
            'max_users' => 1,
            'max_consent_managers' => 1,
            'max_staff' => 1,
            'max_auditors' => 1,
            'max_active_kiosks' => 1,
            'is_active' => false,
            'sort_order' => 100,
        ]);

        $response = $this
            ->actingAs($this->platformAdmin)
            ->get(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            );

        $response->assertOk();
        $response->assertSeeText('Change Subscription Plan');
        $response->assertSeeText('Basic');
        $response->assertSeeText('Growth');
        $response->assertDontSeeText('Hidden Retired Plan');
    }

    private function populateUsageAboveBasicLimits(): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($this->organization->id);

        $consentManagerRole = Role::query()->firstOrCreate([
            'organization_id' => $this->organization->id,
            'guard_name' => 'web',
            'name' => 'Consent Manager',
        ]);

        $staffRole = Role::query()->firstOrCreate([
            'organization_id' => $this->organization->id,
            'guard_name' => 'web',
            'name' => 'Staff',
        ]);

        $auditorRole = Role::query()->firstOrCreate([
            'organization_id' => $this->organization->id,
            'guard_name' => 'web',
            'name' => 'Auditor',
        ]);

        $this->createUsersWithRole(
            $consentManagerRole,
            2
        );

        $this->createUsersWithRole(
            $staffRole,
            3
        );

        $this->createUsersWithRole(
            $auditorRole,
            2
        );

        $template = ConsentTemplate::create([
            'organization_id' => $this->organization->id,
            'title' => 'Downgrade Kiosk Template',
            'description' => 'Plan downgrade test template.',
            'category' => 'Testing',
            'usage_type' =>
                ConsentTemplate::USAGE_SIGNING_STATION,
            'template_schema' => [
                'sections' => [],
            ],
            'has_unpublished_changes' => true,
            'status' => 'draft',
        ]);

        foreach ([
            'First Active Kiosk',
            'Second Active Kiosk',
        ] as $name) {
            SigningStation::create([
                'organization_id' => $this->organization->id,
                'consent_template_id' => $template->id,
                'created_by' => $this->organizationAdmin->id,
                'name' => $name,
                'station_token' => (string) Str::uuid(),
                'active' => true,
                'require_email' => false,
                'require_reference' => false,
                'auto_reset_seconds' => 3,
            ]);
        }
    }

    private function createUsersWithRole(
        Role $role,
        int $count
    ): void {
        foreach (range(1, $count) as $index) {
            $user = User::factory()->create([
                'organization_id' => $this->organization->id,
                'is_active' => true,
            ]);

            $user->assignRole($role);
        }
    }
}
