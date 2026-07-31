<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PlatformRole;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationBillingOwnerDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_assignment_defaults_to_primary_admin(): void
    {
        $this->seed([
            PlatformRoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        $this->post('/register', [
            'organization_name' =>
                'Default Billing Owner Clinic',
            'name' =>
                'Primary Organization Administrator',
            'email' =>
                'primary-billing-admin@example.com',
            'password' =>
                'StrongPass1!',
            'password_confirmation' =>
                'StrongPass1!',
        ]);

        $organization = Organization::query()
            ->where(
                'name',
                'Default Billing Owner Clinic'
            )
            ->firstOrFail();

        $organizationAdmin = User::query()
            ->where(
                'email',
                'primary-billing-admin@example.com'
            )
            ->firstOrFail();

        $organization
            ->subscription()
            ->delete();

        $platformRole = PlatformRole::query()
            ->where('slug', 'super-admin')
            ->firstOrFail();

        $platformAdmin = User::factory()->create([
            'organization_id' => null,
            'platform_role_id' => $platformRole->id,
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->firstOrFail();

        $response = $this
            ->actingAs($platformAdmin)
            ->post(
                route(
                    'platform.organizations.subscription.store',
                    $organization
                ),
                [
                    'subscription_plan_id' =>
                        $plan->id,
                    'starts_at' =>
                        now()->toDateString(),
                    'trial_ends_at' =>
                        null,
                ]
            );

        $response->assertRedirect(
            route(
                'platform.organizations.show',
                $organization
            )
        );

        $subscription = $organization
            ->subscription()
            ->firstOrFail();

        $this->assertSame(
            $organizationAdmin->id,
            $subscription->billing_owner_user_id
        );
    }

    public function test_platform_form_preselects_primary_admin(): void
    {
        $this->seed([
            PlatformRoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        $this->post('/register', [
            'organization_name' =>
                'Preselected Billing Owner Clinic',
            'name' =>
                'Preselected Organization Administrator',
            'email' =>
                'preselected-billing-admin@example.com',
            'password' =>
                'StrongPass1!',
            'password_confirmation' =>
                'StrongPass1!',
        ]);

        $organization = Organization::query()
            ->where(
                'name',
                'Preselected Billing Owner Clinic'
            )
            ->firstOrFail();

        $organizationAdmin = User::query()
            ->where(
                'email',
                'preselected-billing-admin@example.com'
            )
            ->firstOrFail();

        $organization
            ->subscription()
            ->delete();

        $platformRole = PlatformRole::query()
            ->where('slug', 'super-admin')
            ->firstOrFail();

        $platformAdmin = User::factory()->create([
            'organization_id' => null,
            'platform_role_id' => $platformRole->id,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($platformAdmin)
            ->get(
                route(
                    'platform.organizations.show',
                    $organization
                )
            );

        $response->assertOk();

        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/<option(?=[^>]*value="'
                .$organizationAdmin->id
                .'")(?=[^>]*selected)[^>]*>/s',
            $html
        );
    }

    public function test_active_user_can_be_explicitly_selected_as_billing_owner(): void
    {
        $this->seed([
            PlatformRoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        $this->post('/register', [
            'organization_name' =>
                'Explicit Billing Owner Clinic',
            'name' =>
                'Primary Organization Administrator',
            'email' =>
                'explicit-primary-admin@example.com',
            'password' =>
                'StrongPass1!',
            'password_confirmation' =>
                'StrongPass1!',
        ]);

        $organization = Organization::query()
            ->where(
                'name',
                'Explicit Billing Owner Clinic'
            )
            ->firstOrFail();

        $organization
            ->subscription()
            ->delete();

        $selectedUser = User::factory()->create([
            'organization_id' => $organization->id,
            'platform_role_id' => null,
            'is_active' => true,
        ]);

        $platformRole = PlatformRole::query()
            ->where('slug', 'super-admin')
            ->firstOrFail();

        $platformAdmin = User::factory()->create([
            'organization_id' => null,
            'platform_role_id' => $platformRole->id,
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->firstOrFail();

        $response = $this
            ->actingAs($platformAdmin)
            ->post(
                route(
                    'platform.organizations.subscription.store',
                    $organization
                ),
                [
                    'subscription_plan_id' =>
                        $plan->id,
                    'billing_owner_user_id' =>
                        $selectedUser->id,
                    'starts_at' =>
                        now()->toDateString(),
                    'trial_ends_at' =>
                        null,
                ]
            );

        $response->assertRedirect(
            route(
                'platform.organizations.show',
                $organization
            )
        );

        $this->assertDatabaseHas(
            'organization_subscriptions',
            [
                'organization_id' =>
                    $organization->id,
                'billing_owner_user_id' =>
                    $selectedUser->id,
            ]
        );
    }

}
