<?php

namespace Tests\Feature;

use App\Enums\SubscriptionInvoiceStatus;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\OrganizationSubscriptionPlanRequest;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSubscriptionPlanSelectionTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $organizationAdmin;

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
            'organization_name' =>
                'Plan Selection Clinic',

            'name' =>
                'Plan Selection Administrator',

            'email' =>
                'plan-selection-admin@example.com',

            'password' =>
                'Password123!',

            'password_confirmation' =>
                'Password123!',
        ]);

        $this->organization =
            Organization::query()
                ->where(
                    'name',
                    'Plan Selection Clinic'
                )
                ->firstOrFail();

        $this->organizationAdmin =
            User::query()
                ->where(
                    'email',
                    'plan-selection-admin@example.com'
                )
                ->firstOrFail();

        $this->subscription =
            $this->organization
                ->subscription()
                ->firstOrFail();

        $this->basicPlan =
            SubscriptionPlan::query()
                ->where('slug', 'basic')
                ->firstOrFail();

        $this->growthPlan =
            SubscriptionPlan::query()
                ->where('slug', 'growth')
                ->firstOrFail();

        $this->basicPlan->update([
            'monthly_price' =>
                '5000.00',

            'currency' =>
                'KES',

            'annual_billing_enabled' =>
                true,

            'annual_discount_percent' =>
                '10.00',
        ]);

        $this->growthPlan->update([
            'monthly_price' =>
                '10000.00',

            'currency' =>
                'KES',

            'annual_billing_enabled' =>
                true,

            'annual_discount_percent' =>
                '10.00',
        ]);

        $this->subscription->update([
            'subscription_plan_id' =>
                $this->basicPlan->id,

            'billing_cycle' =>
                'monthly',

            'requires_plan_selection' =>
                false,

            'plan_selected_at' =>
                now(),
        ]);
    }

    public function test_new_unpaid_organization_can_view_subscription_plans(): void
    {
        $this
            ->actingAs(
                $this->organizationAdmin
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
                'Current subscription'
            )
            ->assertSeeText('Basic')
            ->assertSeeText('Growth')
            ->assertSeeText(
                'Choose Monthly'
            )
            ->assertSeeText(
                'Choose Annual'
            )
            ->assertSee(
                route(
                    'organization-subscription-plans.index'
                ),
                false
            );
    }

    public function test_organization_admin_can_create_monthly_plan_request_and_draft_invoice(): void
    {
        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->post(
                route(
                    'organization-subscription-plans.request',
                    $this->growthPlan
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

        $this->assertSame(
            $this->growthPlan->id,
            $planRequest
                ->requested_subscription_plan_id
        );

        $this->assertSame(
            'monthly',
            $planRequest->billing_cycle
        );

        $this->assertSame(
            '10000.00',
            $planRequest->amount_snapshot
        );

        $this->assertSame(
            'KES',
            $planRequest->currency
        );

        $this->assertDatabaseHas(
            'subscription_invoices',
            [
                'id' =>
                    $planRequest
                        ->subscription_invoice_id,

                'organization_id' =>
                    $this->organization->id,

                'subscription_plan_id' =>
                    $this->growthPlan->id,

                'subtotal' =>
                    '10000.00',

                'tax_amount' =>
                    '0.00',

                'total_amount' =>
                    '10000.00',

                'currency' =>
                    'KES',

                'status' =>
                    SubscriptionInvoiceStatus::
                        DRAFT->value,
            ]
        );

        $invoice =
            $planRequest
                ->invoice()
                ->firstOrFail();

        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->get(
                route(
                    'organization-subscription-plans.index'
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Awaiting Platform Billing review'
            )
            ->assertSeeText(
                'Draft invoice prepared'
            )
            ->assertSeeText(
                $invoice->invoice_number
            )
            ->assertSeeText(
                'KES 10,000.00'
            )
            ->assertSeeText(
                'Draft - not yet payable'
            )
            ->assertSeeText(
                'Your current subscription remains active'
            );

        $this->subscription->refresh();

        $this->assertSame(
            $this->basicPlan->id,
            $this->subscription
                ->subscription_plan_id
        );
    }

    public function test_annual_request_uses_the_configured_discount(): void
    {
        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->post(
                route(
                    'organization-subscription-plans.request',
                    $this->growthPlan
                ),
                [
                    'billing_cycle' =>
                        'annual',
                ]
            )
            ->assertRedirect();

        $request =
            OrganizationSubscriptionPlanRequest::
                query()
                ->sole();

        $this->assertSame(
            'annual',
            $request->billing_cycle
        );

        /*
         * KES 10,000 × 12 = KES 120,000.
         * A 10% discount produces KES 108,000.
         */
        $this->assertSame(
            '108000.00',
            $request->amount_snapshot
        );

        $this->assertSame(
            '10.00',
            $request
                ->annual_discount_percent_snapshot
        );

        $this->assertDatabaseHas(
            'subscription_invoices',
            [
                'id' =>
                    $request
                        ->subscription_invoice_id,

                'total_amount' =>
                    '108000.00',

                'currency' =>
                    'KES',

                'status' =>
                    SubscriptionInvoiceStatus::
                        DRAFT->value,
            ]
        );
    }

    public function test_normal_organization_user_cannot_access_subscription_plans(): void
    {
        $normalUser =
            User::factory()->create([
                'organization_id' =>
                    $this->organization->id,

                'platform_role_id' =>
                    null,

                'is_active' =>
                    true,
            ]);

        $this
            ->actingAs($normalUser)
            ->get(
                route(
                    'organization-subscription-plans.index'
                )
            )
            ->assertForbidden();

        $this
            ->actingAs($normalUser)
            ->post(
                route(
                    'organization-subscription-plans.request',
                    $this->growthPlan
                ),
                [
                    'billing_cycle' =>
                        'monthly',
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'organization_subscription_plan_requests',
            0
        );

        $this->assertDatabaseCount(
            'subscription_invoices',
            0
        );
    }

    public function test_current_billing_owner_can_access_subscription_plans(): void
    {
        $billingOwner =
            User::factory()->create([
                'organization_id' =>
                    $this->organization->id,

                'platform_role_id' =>
                    null,

                'is_active' =>
                    true,
            ]);

        $this->subscription->update([
            'billing_owner_user_id' =>
                $billingOwner->id,
        ]);

        $this
            ->actingAs($billingOwner)
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
                'Choose Monthly'
            );
    }

    public function test_duplicate_pending_request_does_not_create_duplicate_invoice(): void
    {
        $url = route(
            'organization-subscription-plans.request',
            $this->growthPlan
        );

        $payload = [
            'billing_cycle' =>
                'monthly',
        ];

        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->post($url, $payload)
            ->assertRedirect();

        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->post($url, $payload)
            ->assertRedirect();

        $this->assertDatabaseCount(
            'organization_subscription_plan_requests',
            1
        );

        $this->assertDatabaseCount(
            'subscription_invoices',
            1
        );
    }

    public function test_organization_admin_can_cancel_pending_plan_request_and_draft_invoice(): void
    {
        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->post(
                route(
                    'organization-subscription-plans.request',
                    $this->growthPlan
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

        $invoice =
            $planRequest
                ->invoice()
                ->firstOrFail();

        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->delete(
                route(
                    'organization-subscription-plans.cancel',
                    $planRequest
                )
            )
            ->assertRedirect(
                route(
                    'organization-subscription-plans.index'
                )
            )
            ->assertSessionHas('success');

        $planRequest->refresh();
        $invoice->refresh();

        $this->assertSame(
            OrganizationSubscriptionPlanRequest::
                STATUS_CANCELLED,
            $planRequest->status
        );

        $this->assertNotNull(
            $planRequest->resolved_at
        );

        $this->assertSame(
            $this->organizationAdmin->id,
            $planRequest
                ->resolved_by_user_id
        );

        $this->assertSame(
            SubscriptionInvoiceStatus::CANCELLED,
            $invoice->status
        );

        $this->assertNotNull(
            $invoice->cancelled_at
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'organization_id' =>
                    $this->organization->id,

                'action' =>
                    'organization.subscription_plan_request_cancelled',

                'subject_id' =>
                    $planRequest->id,
            ]
        );

        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->post(
                route(
                    'organization-subscription-plans.request',
                    $this->growthPlan
                ),
                [
                    'billing_cycle' =>
                        'monthly',
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseCount(
            'organization_subscription_plan_requests',
            2
        );

        $this->assertDatabaseHas(
            'organization_subscription_plan_requests',
            [
                'organization_id' =>
                    $this->organization->id,

                'status' =>
                    OrganizationSubscriptionPlanRequest::
                        STATUS_PENDING,
            ]
        );
    }

    public function test_current_billing_owner_can_cancel_pending_plan_request(): void
    {
        $billingOwner =
            User::factory()->create([
                'organization_id' =>
                    $this->organization->id,

                'platform_role_id' =>
                    null,

                'is_active' =>
                    true,
            ]);

        $this->subscription->update([
            'billing_owner_user_id' =>
                $billingOwner->id,
        ]);

        $this
            ->actingAs($billingOwner)
            ->post(
                route(
                    'organization-subscription-plans.request',
                    $this->growthPlan
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

        $this
            ->actingAs($billingOwner)
            ->delete(
                route(
                    'organization-subscription-plans.cancel',
                    $planRequest
                )
            )
            ->assertRedirect(
                route(
                    'organization-subscription-plans.index'
                )
            );

        $this->assertDatabaseHas(
            'organization_subscription_plan_requests',
            [
                'id' =>
                    $planRequest->id,

                'status' =>
                    OrganizationSubscriptionPlanRequest::
                        STATUS_CANCELLED,

                'resolved_by_user_id' =>
                    $billingOwner->id,
            ]
        );
    }

    public function test_normal_organization_user_cannot_cancel_plan_request(): void
    {
        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->post(
                route(
                    'organization-subscription-plans.request',
                    $this->growthPlan
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

        $normalUser =
            User::factory()->create([
                'organization_id' =>
                    $this->organization->id,

                'platform_role_id' =>
                    null,

                'is_active' =>
                    true,
            ]);

        $this
            ->actingAs($normalUser)
            ->delete(
                route(
                    'organization-subscription-plans.cancel',
                    $planRequest
                )
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'organization_subscription_plan_requests',
            [
                'id' =>
                    $planRequest->id,

                'status' =>
                    OrganizationSubscriptionPlanRequest::
                        STATUS_PENDING,
            ]
        );
    }

    public function test_issued_invoice_prevents_direct_organization_cancellation(): void
    {
        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->post(
                route(
                    'organization-subscription-plans.request',
                    $this->growthPlan
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

        $invoice =
            $planRequest
                ->invoice()
                ->firstOrFail();

        $invoice->update([
            'status' =>
                SubscriptionInvoiceStatus::ISSUED,

            'issue_date' =>
                today(),

            'due_date' =>
                today()->addDays(7),
        ]);

        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->from(
                route(
                    'organization-subscription-plans.index'
                )
            )
            ->delete(
                route(
                    'organization-subscription-plans.cancel',
                    $planRequest
                )
            )
            ->assertRedirect(
                route(
                    'organization-subscription-plans.index'
                )
            )
            ->assertSessionHasErrors(
                'plan_request'
            );

        $planRequest->refresh();
        $invoice->refresh();

        $this->assertSame(
            OrganizationSubscriptionPlanRequest::
                STATUS_PENDING,
            $planRequest->status
        );

        $this->assertSame(
            SubscriptionInvoiceStatus::ISSUED,
            $invoice->status
        );

        $this->assertNull(
            $invoice->cancelled_at
        );
    }


    public function test_pending_draft_request_page_shows_cancel_request_button(): void
    {
        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->post(
                route(
                    'organization-subscription-plans.request',
                    $this->growthPlan
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

        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->get(
                route(
                    'organization-subscription-plans.index'
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Cancel Request'
            )
            ->assertSee(
                route(
                    'organization-subscription-plans.cancel',
                    $planRequest
                ),
                false
            )
            ->assertSee(
                "Cancel this plan request?",
                false
            );
    }

    public function test_issued_invoice_page_hides_direct_cancel_request_button(): void
    {
        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->post(
                route(
                    'organization-subscription-plans.request',
                    $this->growthPlan
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

        $planRequest
            ->invoice()
            ->firstOrFail()
            ->update([
                'status' =>
                    SubscriptionInvoiceStatus::ISSUED,

                'issue_date' =>
                    today(),

                'due_date' =>
                    today()->addDays(7),
            ]);

        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->get(
                route(
                    'organization-subscription-plans.index'
                )
            )
            ->assertOk()
            ->assertDontSeeText(
                'Cancel Request'
            )
            ->assertDontSee(
                route(
                    'organization-subscription-plans.cancel',
                    $planRequest
                ),
                false
            );
    }


    public function test_issued_plan_request_page_shows_invoice_and_payment_instructions(): void
    {
        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->post(
                route(
                    'organization-subscription-plans.request',
                    $this->growthPlan
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

        $invoice =
            $planRequest
                ->invoice()
                ->firstOrFail();

        $dueDate =
            today()->addDays(7);

        $invoice->update([
            'status' =>
                SubscriptionInvoiceStatus::ISSUED,

            'issue_date' =>
                today(),

            'due_date' =>
                $dueDate,

            'payment_details_snapshot' => [
                'mpesa_enabled' =>
                    true,

                'mpesa_type' =>
                    'paybill',

                'mpesa_business_number' =>
                    '400200',

                'mpesa_account_reference_instructions' =>
                    'Use the invoice number as the account reference.',

                'mpesa_instructions' =>
                    'Keep the confirmation message.',

                'bank_enabled' =>
                    true,

                'bank_name' =>
                    'Example Commercial Bank',

                'bank_account_name' =>
                    'eConsent Holdings',

                'bank_account_number' =>
                    '0102030405',

                'bank_branch' =>
                    'Nairobi',

                'bank_swift_code' =>
                    'EXAMPLEKX',

                'bank_reference_instructions' =>
                    'Use the invoice number as the transfer reference.',

                'bank_instructions' =>
                    'Bank charges are paid by the sender.',

                'billing_contact_email' =>
                    'billing@example.com',

                'billing_contact_phone' =>
                    '+254700000000',

                'additional_instructions' =>
                    'Send payment confirmation after payment.',
            ],
        ]);

        $this
            ->actingAs(
                $this->organizationAdmin
            )
            ->get(
                route(
                    'organization-subscription-plans.index'
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Invoice issued — payment required'
            )
            ->assertSeeText(
                'Payment Instructions'
            )
            ->assertSeeText(
                $invoice->invoice_number
            )
            ->assertSeeText(
                $dueDate->format('M d, Y')
            )
            ->assertSeeText(
                'M-Pesa'
            )
            ->assertSeeText(
                'Paybill'
            )
            ->assertSeeText(
                '400200'
            )
            ->assertSeeText(
                'Example Commercial Bank'
            )
            ->assertSeeText(
                '0102030405'
            )
            ->assertSeeText(
                'billing@example.com'
            )
            ->assertSeeText(
                'View Invoice'
            )
            ->assertSeeText(
                'Download PDF'
            )
            ->assertSee(
                route(
                    'organization-billing.invoices.show',
                    $invoice
                ),
                false
            )
            ->assertSee(
                route(
                    'organization-billing.invoices.download',
                    $invoice
                ),
                false
            )
            ->assertDontSeeText(
                'Cancel Request'
            )
            ->assertDontSeeText(
                'Draft - not yet payable'
            );
    }


}
