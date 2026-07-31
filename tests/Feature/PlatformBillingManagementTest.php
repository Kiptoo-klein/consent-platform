<?php

namespace Tests\Feature;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionInvoiceStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\OrganizationSubscriptionPlanRequest;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformBillingManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $platformAdmin;

    private SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PlatformRoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        $superAdminRole =
            \App\Models\PlatformRole::query()
                ->where('slug', 'super-admin')
                ->firstOrFail();

        $this->platformAdmin =
            User::factory()->create([
                'organization_id' => null,
                'platform_role_id' =>
                    $superAdminRole->id,
                'is_active' => true,
            ]);

        $this->plan =
            SubscriptionPlan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->firstOrFail();
    }

    public function test_platform_owner_sees_billing_management_menu_and_overview(): void
    {
        [
            $organization,
            $subscription,
        ] = $this->createBillingAccount(
            'Central Billing Clinic',
            SubscriptionPaymentStatus::UNPAID,
            OrganizationSubscriptionStatus::TRIALING
        );

        SubscriptionInvoice::query()->create([
            'organization_subscription_id' =>
                $subscription->id,

            'organization_id' =>
                $organization->id,

            'subscription_plan_id' =>
                $this->plan->id,

            'invoice_number' =>
                'CENTRAL-BILLING-0001',

            'status' =>
                SubscriptionInvoiceStatus::ISSUED,

            'issue_date' =>
                now()->toDateString(),

            'due_date' =>
                now()->addDays(7)->toDateString(),

            'subtotal' => '100.00',
            'tax_amount' => '0.00',
            'total_amount' => '100.00',
            'currency' => 'USD',
        ]);

        $response = $this
            ->actingAs($this->platformAdmin)
            ->get(
                route('platform.billing.index')
            );

        $response
            ->assertOk()
            ->assertSeeText('Billing Management')
            ->assertSeeText('Manual payment workflow')
            ->assertSeeText('Central Billing Clinic')
            ->assertSeeText('Payment attention')
            ->assertSeeText('1 outstanding')
            ->assertSeeText('Invoices')
            ->assertSeeText('Payments')
            ->assertSee(
                route(
                    'platform.organizations.subscription-invoices.index',
                    $organization
                ),
                false
            )
            ->assertSee(
                route(
                    'platform.organizations.subscription-transactions.index',
                    $organization
                ),
                false
            )
            ->assertSee(
                route('platform.billing.index'),
                false
            );
    }

    public function test_platform_billing_management_can_filter_accounts(): void
    {
        $this->createBillingAccount(
            'Visible Billing Clinic',
            SubscriptionPaymentStatus::PAID,
            OrganizationSubscriptionStatus::ACTIVE
        );

        $this->createBillingAccount(
            'Hidden Billing Clinic',
            SubscriptionPaymentStatus::UNPAID,
            OrganizationSubscriptionStatus::TRIALING
        );

        $response = $this
            ->actingAs($this->platformAdmin)
            ->get(
                route(
                    'platform.billing.index',
                    [
                        'search' =>
                            'Visible Billing',

                        'payment_status' =>
                            SubscriptionPaymentStatus::
                                PAID->value,
                    ]
                )
            );

        $response
            ->assertOk()
            ->assertSeeText('Visible Billing Clinic')
            ->assertDontSeeText('Hidden Billing Clinic');
    }

    public function test_organization_user_cannot_access_platform_billing_management(): void
    {
        [
            $organization,
        ] = $this->createBillingAccount(
            'Restricted Billing Clinic',
            SubscriptionPaymentStatus::PAID,
            OrganizationSubscriptionStatus::ACTIVE
        );

        $organizationUser =
            User::factory()->create([
                'organization_id' =>
                    $organization->id,

                'platform_role_id' => null,
                'is_active' => true,
            ]);

        $this
            ->actingAs($organizationUser)
            ->get(
                route('platform.billing.index')
            )
            ->assertForbidden();
    }


    public function test_platform_billing_lists_pending_plan_requests(): void
    {
        [
            $organization,
            $subscription,
        ] = $this->createBillingAccount(
            'Pending Request Clinic',
            SubscriptionPaymentStatus::UNPAID,
            OrganizationSubscriptionStatus::TRIALING
        );

        $requestedPlan =
            SubscriptionPlan::query()
                ->whereKeyNot(
                    $this->plan->id
                )
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->firstOrFail();

        $requester =
            User::factory()->create([
                'organization_id' =>
                    $organization->id,

                'platform_role_id' =>
                    null,

                'name' =>
                    'Plan Request Owner',

                'email' =>
                    'plan-request-owner@example.com',

                'is_active' =>
                    true,
            ]);

        $invoice =
            SubscriptionInvoice::query()
                ->create([
                    'organization_subscription_id' =>
                        $subscription->id,

                    'organization_id' =>
                        $organization->id,

                    'subscription_plan_id' =>
                        $requestedPlan->id,

                    'invoice_number' =>
                        'PLAN-REQUEST-0001',

                    'status' =>
                        SubscriptionInvoiceStatus::DRAFT,

                    'issue_date' => null,
                    'due_date' => null,

                    'subtotal' =>
                        '108000.00',

                    'tax_amount' =>
                        '0.00',

                    'total_amount' =>
                        '108000.00',

                    'currency' =>
                        'KES',

                    'notes' =>
                        'Prepared from a pending plan request.',

                    'issued_by_user_id' =>
                        null,

                    'paid_at' => null,
                    'voided_at' => null,
                    'cancelled_at' => null,
                ]);

        OrganizationSubscriptionPlanRequest::
            query()
            ->create([
                'organization_id' =>
                    $organization->id,

                'organization_subscription_id' =>
                    $subscription->id,

                'current_subscription_plan_id' =>
                    $this->plan->id,

                'requested_subscription_plan_id' =>
                    $requestedPlan->id,

                'requested_by_user_id' =>
                    $requester->id,

                'subscription_invoice_id' =>
                    $invoice->id,

                'billing_cycle' =>
                    OrganizationSubscriptionPlanRequest::
                        BILLING_CYCLE_ANNUAL,

                'status' =>
                    OrganizationSubscriptionPlanRequest::
                        STATUS_PENDING,

                'monthly_price_snapshot' =>
                    '10000.00',

                'annual_discount_percent_snapshot' =>
                    '10.00',

                'amount_snapshot' =>
                    '108000.00',

                'currency' =>
                    'KES',

                'requested_at' =>
                    now(),

                'resolved_at' =>
                    null,

                'resolved_by_user_id' =>
                    null,
            ]);

        $this
            ->actingAs($this->platformAdmin)
            ->get(
                route('platform.billing.index')
            )
            ->assertOk()
            ->assertSeeText(
                'Pending Plan Requests'
            )
            ->assertSeeText(
                'Pending Request Clinic'
            )
            ->assertSeeText(
                $requestedPlan->name
            )
            ->assertSeeText(
                'Annual billing'
            )
            ->assertSeeText(
                'KES 108,000.00'
            )
            ->assertSeeText(
                'Plan Request Owner'
            )
            ->assertSeeText(
                'PLAN-REQUEST-0001'
            )
            ->assertSeeText(
                'Review Invoice'
            )
            ->assertSee(
                route(
                    'platform.organizations.subscription-invoices.show',
                    [
                        $organization,
                        $invoice,
                    ]
                ),
                false
            );
    }

    /**
     * @return array{
     *     0: Organization,
     *     1: OrganizationSubscription
     * }
     */
    private function createBillingAccount(
        string $organizationName,
        SubscriptionPaymentStatus $paymentStatus,
        OrganizationSubscriptionStatus $status
    ): array {
        $organization =
            Organization::query()->create([
                'name' => $organizationName,

                'slug' => Str::slug(
                    $organizationName
                    .'-'
                    .Str::lower(
                        Str::random(8)
                    )
                ),
            ]);

        $billingOwner =
            User::factory()->create([
                'organization_id' =>
                    $organization->id,

                'platform_role_id' => null,
                'is_active' => true,
            ]);

        $subscription =
            OrganizationSubscription::query()
                ->create([
                    'organization_id' =>
                        $organization->id,

                    'subscription_plan_id' =>
                        $this->plan->id,

                    'billing_owner_user_id' =>
                        $billingOwner->id,

                    'status' =>
                        $status,

                    'payment_status' =>
                        $paymentStatus,

                    'starts_at' =>
                        now()->subDay(),

                    'trial_ends_at' =>
                        $status
                            === OrganizationSubscriptionStatus::
                                TRIALING
                                ? now()->addDays(7)
                                : null,

                    'current_period_starts_at' =>
                        $paymentStatus
                            === SubscriptionPaymentStatus::
                                PAID
                                ? now()->subDay()
                                : null,

                    'current_period_ends_at' =>
                        $paymentStatus
                            === SubscriptionPaymentStatus::
                                PAID
                                ? now()->addMonth()
                                : null,
                ]);

        return [
            $organization,
            $subscription,
        ];
    }
}
