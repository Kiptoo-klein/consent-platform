<?php

namespace Tests\Feature;

use App\Enums\SubscriptionInvoiceStatus;
use App\Models\Organization;
use App\Models\PlatformRole;
use App\Models\OrganizationSubscriptionPlanRequest;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NewOrganizationSubscriptionOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_waits_for_platform_to_issue_invoice_before_payment(): void
    {
        $this->seed([
            PlatformRoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        $response = $this->post('/register', [
            'organization_name' =>
                'Onboarding Clinic',

            'name' =>
                'Onboarding Administrator',

            'email' =>
                'onboarding-admin@example.com',

            'password' =>
                'Password123!',

            'password_confirmation' =>
                'Password123!',
        ]);

        $this->verifyAuthenticatedUser();

        $response->assertRedirect(
            route('verification.notice')
        );

        $organization =
            Organization::query()
                ->where(
                    'name',
                    'Onboarding Clinic'
                )
                ->firstOrFail();

        $administrator =
            User::query()
                ->where(
                    'email',
                    'onboarding-admin@example.com'
                )
                ->firstOrFail();

        $subscription =
            $organization
                ->subscription()
                ->firstOrFail();

        app(
            PermissionRegistrar::class
        )->setPermissionsTeamId(
            $organization->id
        );

        $this->assertTrue(
            $administrator
                ->fresh()
                ->hasRole(
                    'Organization Admin'
                )
        );

        $this->assertSame(
            $administrator->id,
            $subscription
                ->billing_owner_user_id
        );

        $this->assertTrue(
            $subscription
                ->requiresPlanSelection()
        );

        $this->assertNull(
            $subscription
                ->plan_selected_at
        );

        $this
            ->actingAs(
                $administrator
            )
            ->get(
                route(
                    'organization-subscription-plans.index'
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Subscription Plans'
            )
            ->assertSeeText(
                'No plan selected'
            )
            ->assertSeeText(
                'Nothing has been preselected'
            )
            ->assertDontSeeText(
                'Current monthly plan'
            )
            ->assertSee(
                route(
                    'organization-billing.index'
                ),
                false
            );

        $growthPlan =
            SubscriptionPlan::query()
                ->where(
                    'slug',
                    'growth'
                )
                ->firstOrFail();

        $growthPlan->update([
            'monthly_price' =>
                '10000.00',

            'currency' =>
                'KES',
        ]);

        $this
            ->actingAs(
                $administrator
            )
            ->post(
                route(
                    'organization-subscription-plans.request',
                    $growthPlan
                ),
                [
                    'billing_cycle' =>
                        'monthly',
                ]
            )
            ->assertRedirect(
                route(
                    'organization-subscription-plans.index'
                )
            );

        $planRequest =
            OrganizationSubscriptionPlanRequest::
                query()
                ->sole();

        $this->assertNull(
            $planRequest
                ->current_subscription_plan_id
        );

        $invoice =
            $planRequest
                ->invoice()
                ->firstOrFail();

        $this->assertSame(
            SubscriptionInvoiceStatus::DRAFT,
            $invoice->status
        );

        $organizationPaymentUrl =
            "/subscription/plans/requests/"
            ."{$planRequest->id}/payment";

        $this
            ->actingAs(
                $administrator
            )
            ->get(
                route(
                    'organization-subscription-plans.index'
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Pending plan change'
            )
            ->assertSeeText(
                'Cancel Request'
            )
            ->assertDontSeeText(
                'Continue to Payment'
            )
            ->assertDontSee(
                $organizationPaymentUrl,
                false
            );

        $this
            ->actingAs(
                $administrator
            )
            ->post(
                $organizationPaymentUrl
            )
            ->assertNotFound();

        $this->assertSame(
            SubscriptionInvoiceStatus::DRAFT,
            $invoice->fresh()->status
        );

        $superAdminRole =
            PlatformRole::query()
                ->where(
                    'slug',
                    'super-admin'
                )
                ->firstOrFail();

        $platformAdmin =
            User::factory()->create([
                'organization_id' =>
                    null,

                'platform_role_id' =>
                    $superAdminRole->id,

                'is_active' =>
                    true,
            ]);

        $issueDate =
            today();

        $dueDate =
            today()->addDays(7);

        $this
            ->actingAs(
                $platformAdmin
            )
            ->patch(
                route(
                    'platform.organizations.subscription-invoices.issue',
                    [
                        $organization,
                        $invoice,
                    ]
                ),
                [
                    'issue_date' =>
                        $issueDate->toDateString(),

                    'due_date' =>
                        $dueDate->toDateString(),
                ]
            )
            ->assertRedirect(
                route(
                    'platform.organizations.subscription-invoices.show',
                    [
                        $organization,
                        $invoice,
                    ]
                )
            );

        $invoice->refresh();


        $this->assertSame(
            SubscriptionInvoiceStatus::ISSUED,
            $invoice->status
        );

        $this->assertNotNull(
            $invoice->issue_date
        );

        $this->assertNotNull(
            $invoice->due_date
        );

        $this->assertIsArray(
            $invoice
                ->payment_details_snapshot
        );

        $this
            ->actingAs(
                $administrator
            )
            ->get(
                route(
                    'organization-billing.index'
                )
            )
            ->assertOk()
            ->assertSeeText(
                $invoice->invoice_number
            );

        $this
            ->actingAs(
                $administrator
            )
            ->get(
                route(
                    'organization-billing.invoices.show',
                    $invoice
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Subscription Invoice'
            )
            ->assertSeeText(
                $invoice->invoice_number
            )
            ->assertSeeText(
                'Download PDF'
            );
    }

    public function test_new_organization_can_explicitly_choose_basic_monthly(): void
    {
        $this->seed([
            PlatformRoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        $this->post('/register', [
            'organization_name' =>
                'Basic Choice Clinic',

            'name' =>
                'Basic Choice Administrator',

            'email' =>
                'basic-choice-admin@example.com',

            'password' =>
                'Password123!',

            'password_confirmation' =>
                'Password123!',
        ]);

        $this->verifyAuthenticatedUser();

        $administrator =
            User::query()
                ->where(
                    'email',
                    'basic-choice-admin@example.com'
                )
                ->firstOrFail();

        $basicPlan =
            SubscriptionPlan::query()
                ->where('slug', 'basic')
                ->firstOrFail();

        $basicPlan->update([
            'monthly_price' =>
                '5000.00',

            'currency' =>
                'KES',
        ]);

        $this
            ->actingAs(
                $administrator
            )
            ->post(
                route(
                    'organization-subscription-plans.request',
                    $basicPlan
                ),
                [
                    'billing_cycle' =>
                        'monthly',
                ]
            )
            ->assertRedirect(
                route(
                    'organization-subscription-plans.index'
                )
            )
            ->assertSessionHas(
                'success'
            );

        $planRequest =
            OrganizationSubscriptionPlanRequest::
                query()
                ->sole();

        $this->assertNull(
            $planRequest
                ->current_subscription_plan_id
        );

        $this->assertSame(
            $basicPlan->id,
            $planRequest
                ->requested_subscription_plan_id
        );
    }

    public function test_organization_user_cannot_issue_draft_invoice(): void
    {
        $this->seed([
            PlatformRoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        $this->post('/register', [
            'organization_name' =>
                'Protected Billing Clinic',

            'name' =>
                'Protected Billing Administrator',

            'email' =>
                'protected-billing-admin@example.com',

            'password' =>
                'Password123!',

            'password_confirmation' =>
                'Password123!',
        ]);

        $this->verifyAuthenticatedUser();

        $organization =
            Organization::query()
                ->where(
                    'name',
                    'Protected Billing Clinic'
                )
                ->firstOrFail();

        $administrator =
            User::query()
                ->where(
                    'email',
                    'protected-billing-admin@example.com'
                )
                ->firstOrFail();

        $growthPlan =
            SubscriptionPlan::query()
                ->where(
                    'slug',
                    'growth'
                )
                ->firstOrFail();

        $growthPlan->update([
            'monthly_price' =>
                '10000.00',

            'currency' =>
                'KES',
        ]);

        $this
            ->actingAs(
                $administrator
            )
            ->post(
                route(
                    'organization-subscription-plans.request',
                    $growthPlan
                ),
                [
                    'billing_cycle' =>
                        'monthly',
                ]
            )
            ->assertRedirect();

        $planRequest =
            OrganizationSubscriptionPlanRequest::
                query()
                ->sole();

        $ordinaryUser =
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
                $ordinaryUser
            )
            ->post(
                "/subscription/plans/requests/{$planRequest->id}/payment"
            )
            ->assertNotFound();

        $this->assertSame(
            SubscriptionInvoiceStatus::DRAFT,
            SubscriptionInvoice::query()
                ->sole()
                ->status
        );
    }
}
