<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\PlatformRole;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSubscriptionPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $platformAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PlatformRoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        $superAdminRole =
            PlatformRole::query()
                ->where('slug', 'super-admin')
                ->firstOrFail();

        $this->platformAdmin =
            User::factory()->create([
                'organization_id' => null,

                'platform_role_id' =>
                    $superAdminRole->id,

                'is_active' => true,
            ]);
    }

    public function test_platform_admin_can_view_plan_limits_and_pricing_controls(): void
    {
        $response = $this
            ->actingAs($this->platformAdmin)
            ->get(
                route(
                    'platform.subscription-plans.index'
                )
            );

        $response
            ->assertOk()
            ->assertSeeText('Subscription Plans')
            ->assertSeeText('Basic')
            ->assertSeeText('Growth')
            ->assertSeeText('Business')
            ->assertSeeText('Enterprise')
            ->assertSeeText('Monthly rate')
            ->assertSeeText('Annual discount')
            ->assertSeeText('Enable annual billing')
            ->assertSeeText('Total users')
            ->assertSeeText('Consent Managers')
            ->assertSeeText('Active kiosks')
            ->assertSeeText('People and workspace limits')
            ->assertSeeText('Consent usage limits')
            ->assertSeeText('Unlimited when blank')
            ->assertSee(
                'data-plan-limit-layout="spacious"',
                false
            )
            ->assertSee(
                'data-plan-limit-summary="spacious"',
                false
            )
            ->assertSeeText('KES')
            ->assertSee(
                route(
                    'platform.subscription-plans.index'
                ),
                false
            );
    }

    public function test_platform_admin_can_update_rates_discounts_and_limits(): void
    {
        $plan = SubscriptionPlan::query()
            ->where('slug', 'basic')
            ->firstOrFail();

        $response = $this
            ->actingAs($this->platformAdmin)
            ->patch(
                route(
                    'platform.subscription-plans.update',
                    $plan
                ),
                [
                    'editing_plan_id' =>
                        $plan->id,

                    'name' =>
                        'Basic',

                    'description' =>
                        'Updated basic subscription plan.',

                    'monthly_price' =>
                        '10000.00',

                    'currency' =>
                        'kes',

                    'annual_billing_enabled' =>
                        '1',

                    'annual_discount_percent' =>
                        '15.00',

                    'max_users' =>
                        6,

                    'max_consent_managers' =>
                        1,

                    'max_staff' =>
                        2,

                    'max_auditors' =>
                        1,

                    'max_active_kiosks' =>
                        2,

                    'sort_order' =>
                        1,

                    'is_active' =>
                        '1',
                ]
            );

        $response->assertRedirect(
            route(
                'platform.subscription-plans.index'
            )
        );

        $plan->refresh();

        $this->assertSame(
            '10000.00',
            $plan->monthly_price
        );

        $this->assertSame(
            'KES',
            $plan->currency
        );

        $this->assertTrue(
            $plan->annual_billing_enabled
        );

        $this->assertSame(
            '15.00',
            $plan->annual_discount_percent
        );

        $this->assertSame(
            '102000.00',
            $plan->annualPrice()
        );

        $this->assertSame(
            '18000.00',
            $plan->annualSavings()
        );

        $this->assertSame(
            6,
            $plan->max_users
        );

        $this->assertSame(
            2,
            $plan->max_active_kiosks
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'action' =>
                    'platform.subscription_plan_updated',

                'subject_id' =>
                    $plan->id,
            ]
        );
    }

    public function test_role_limits_cannot_exceed_total_user_seats(): void
    {
        $plan = SubscriptionPlan::query()
            ->where('slug', 'basic')
            ->firstOrFail();

        $response = $this
            ->actingAs($this->platformAdmin)
            ->from(
                route(
                    'platform.subscription-plans.index'
                )
            )
            ->patch(
                route(
                    'platform.subscription-plans.update',
                    $plan
                ),
                [
                    'editing_plan_id' =>
                        $plan->id,

                    'name' =>
                        $plan->name,

                    'description' =>
                        $plan->description,

                    'monthly_price' =>
                        '5000.00',

                    'currency' =>
                        'KES',

                    'annual_billing_enabled' =>
                        '1',

                    'annual_discount_percent' =>
                        '10.00',

                    /*
                     * Required seats:
                     * 1 Admin + 1 Manager + 2 Staff
                     * + 1 Auditor = 5.
                     */
                    'max_users' =>
                        4,

                    'max_consent_managers' =>
                        1,

                    'max_staff' =>
                        2,

                    'max_auditors' =>
                        1,

                    'max_active_kiosks' =>
                        1,

                    'sort_order' =>
                        1,

                    'is_active' =>
                        '1',
                ]
            );

        $response
            ->assertRedirect(
                route(
                    'platform.subscription-plans.index'
                )
            )
            ->assertSessionHasErrors(
                'max_users'
            );
    }

    public function test_organization_user_cannot_manage_platform_plans(): void
    {
        $organization =
            Organization::query()->create([
                'name' =>
                    'Restricted Plan Organization',

                'slug' =>
                    'restricted-plan-organization',
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
            ->actingAs($organizationUser)
            ->get(
                route(
                    'platform.subscription-plans.index'
                )
            )
            ->assertForbidden();
    }
}
