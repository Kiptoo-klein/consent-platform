<?php

namespace Tests\Feature;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionInvoiceStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionTransactionStatus;
use App\Enums\SubscriptionTransactionType;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\OrganizationSubscriptionPlanRequest;
use App\Models\PlatformRole;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use App\Services\ActivityLogger;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class PlatformInvoicePaymentRecordingTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private OrganizationSubscription $subscription;

    private SubscriptionPlan $currentPlan;

    private SubscriptionPlan $requestedPlan;

    private SubscriptionInvoice $invoice;

    private OrganizationSubscriptionPlanRequest $planRequest;

    private User $billingUser;

    private User $auditor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PlatformRoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        Carbon::setTestNow(
            Carbon::parse(
                '2026-01-31 14:45:30'
            )
        );

        $this->currentPlan =
            SubscriptionPlan::query()
                ->where(
                    'name',
                    'Basic'
                )
                ->firstOrFail();

        $this->requestedPlan =
            SubscriptionPlan::query()
                ->where(
                    'name',
                    'Growth'
                )
                ->firstOrFail();

        $this->organization =
            Organization::query()->create([
                'name' =>
                    'Invoice Payment Clinic',

                'slug' =>
                    'invoice-payment-clinic',
            ]);

        $this->subscription =
            OrganizationSubscription::query()
                ->create([
                    'organization_id' =>
                        $this->organization->id,

                    'subscription_plan_id' =>
                        $this->currentPlan->id,

                    'billing_owner_user_id' =>
                        null,

                    'status' =>
                        OrganizationSubscriptionStatus::
                            EXPIRED,

                    'payment_status' =>
                        SubscriptionPaymentStatus::
                            UNPAID,

                    'billing_cycle' =>
                        'monthly',

                    'starts_at' =>
                        null,

                    'trial_ends_at' =>
                        null,

                    'current_period_starts_at' =>
                        null,

                    'current_period_ends_at' =>
                        null,

                    'cancelled_at' =>
                        now()->subDay(),

                    'ends_at' =>
                        now()->subMinute(),
                ]);

        $this->billingUser =
            $this->platformUser(
                'billing'
            );

        $this->auditor =
            $this->platformUser(
                'platform-auditor'
            );

        $this->invoice =
            SubscriptionInvoice::query()
                ->create([
                    'organization_subscription_id' =>
                        $this->subscription->id,

                    'organization_id' =>
                        $this->organization->id,

                    'subscription_plan_id' =>
                        $this->requestedPlan->id,

                    'invoice_number' =>
                        'INV-PAYMENT-0001',

                    'status' =>
                        SubscriptionInvoiceStatus::
                            ISSUED,

                    'issue_date' =>
                        today()->subDay(),

                    'due_date' =>
                        today()->addDays(7),

                    'subtotal' =>
                        '10000.00',

                    'tax_amount' =>
                        '0.00',

                    'total_amount' =>
                        '10000.00',

                    'currency' =>
                        'KES',

                    'notes' =>
                        'Monthly Growth plan invoice.',

                    'issued_by_user_id' =>
                        $this->billingUser->id,

                    'paid_at' =>
                        null,

                    'voided_at' =>
                        null,

                    'cancelled_at' =>
                        null,
                ]);

        $this->planRequest =
            OrganizationSubscriptionPlanRequest::
                query()
                ->create([
                    'organization_id' =>
                        $this->organization->id,

                    'organization_subscription_id' =>
                        $this->subscription->id,

                    'current_subscription_plan_id' =>
                        $this->currentPlan->id,

                    'requested_subscription_plan_id' =>
                        $this->requestedPlan->id,

                    'requested_by_user_id' =>
                        null,

                    'subscription_invoice_id' =>
                        $this->invoice->id,

                    'billing_cycle' =>
                        OrganizationSubscriptionPlanRequest::
                            BILLING_CYCLE_MONTHLY,

                    'status' =>
                        OrganizationSubscriptionPlanRequest::
                            STATUS_PENDING,

                    'monthly_price_snapshot' =>
                        '10000.00',

                    'annual_discount_percent_snapshot' =>
                        '10.00',

                    'amount_snapshot' =>
                        '10000.00',

                    'currency' =>
                        'KES',

                    'requested_at' =>
                        now()->subDay(),

                    'resolved_at' =>
                        null,

                    'resolved_by_user_id' =>
                        null,
                ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_issued_invoice_displays_prefilled_payment_information_without_invoice_or_date_inputs(): void
    {
        $this
            ->actingAs(
                $this->billingUser
            )
            ->get(
                $this->invoiceUrl()
            )
            ->assertOk()
            ->assertSeeText(
                'Record Payment'
            )
            ->assertSee(
                'data-invoice-payment-form',
                false
            )
            ->assertSeeText(
                'Monthly'
            )
            ->assertSeeText(
                'KES 10,000.00'
            )
            ->assertSeeText(
                'Jan 31, 2026 14:45:30'
            )
            ->assertSeeText(
                'Feb 28, 2026 14:45:30'
            )
            ->assertDontSee(
                'name="subscription_invoice_id"',
                false
            )
            ->assertDontSee(
                'name="amount"',
                false
            )
            ->assertDontSee(
                'name="currency"',
                false
            )
            ->assertDontSee(
                'name="paid_at"',
                false
            )
            ->assertDontSee(
                'name="period_starts_at"',
                false
            )
            ->assertDontSee(
                'name="period_ends_at"',
                false
            );
    }

    public function test_monthly_invoice_payment_uses_server_time_and_activates_requested_plan(): void
    {
        $this
            ->actingAs(
                $this->billingUser
            )
            ->post(
                $this->paymentUrl(),
                [
                    'reference' =>
                        'MPESA-INV-0001',

                    'payment_method' =>
                        'M-Pesa',

                    'notes' =>
                        'Confirmed in the M-Pesa portal.',

                    /*
                     * These fields must be ignored because the
                     * server derives them from the invoice.
                     */
                    'amount' =>
                        '1.00',

                    'currency' =>
                        'USD',

                    'paid_at' =>
                        '1999-01-01T00:00',

                    'period_starts_at' =>
                        '1999-01-01T00:00',

                    'period_ends_at' =>
                        '2099-01-01T00:00',

                    'subscription_plan_id' =>
                        $this->currentPlan->id,
                ]
            )
            ->assertRedirect(
                $this->invoiceUrl()
            )
            ->assertSessionHas(
                'success'
            );

        $transaction =
            SubscriptionTransaction::query()
                ->where(
                    'reference',
                    'MPESA-INV-0001'
                )
                ->sole();

        $this->assertSame(
            $this->invoice->id,
            $transaction->subscription_invoice_id
        );

        $this->assertSame(
            $this->requestedPlan->id,
            $transaction->subscription_plan_id
        );

        $this->assertSame(
            SubscriptionTransactionType::PAYMENT,
            $transaction->type
        );

        $this->assertSame(
            SubscriptionTransactionStatus::SUCCESSFUL,
            $transaction->status
        );

        $this->assertSame(
            '10000.00',
            $transaction->amount
        );

        $this->assertSame(
            'KES',
            $transaction->currency
        );

        $this->assertSame(
            '2026-01-31 14:45:30',
            $transaction
                ->paid_at
                ->toDateTimeString()
        );

        $this->assertSame(
            '2026-01-31 14:45:30',
            $transaction
                ->period_starts_at
                ->toDateTimeString()
        );

        $this->assertSame(
            '2026-02-28 14:45:30',
            $transaction
                ->period_ends_at
                ->toDateTimeString()
        );

        $invoice =
            $this->invoice->fresh();

        $this->assertSame(
            SubscriptionInvoiceStatus::PAID,
            $invoice->status
        );

        $this->assertSame(
            '2026-01-31 14:45:30',
            $invoice
                ->paid_at
                ->toDateTimeString()
        );

        $subscription =
            $this->subscription->fresh();

        $this->assertSame(
            $this->requestedPlan->id,
            $subscription->subscription_plan_id
        );

        $this->assertSame(
            'monthly',
            $subscription->billing_cycle
        );

        $this->assertSame(
            OrganizationSubscriptionStatus::ACTIVE,
            $subscription->status
        );

        $this->assertSame(
            SubscriptionPaymentStatus::PAID,
            $subscription->payment_status
        );

        $this->assertSame(
            '2026-01-31 14:45:30',
            $subscription
                ->current_period_starts_at
                ->toDateTimeString()
        );

        $this->assertSame(
            '2026-02-28 14:45:30',
            $subscription
                ->current_period_ends_at
                ->toDateTimeString()
        );

        $this->assertNull(
            $subscription->trial_ends_at
        );

        $this->assertNull(
            $subscription->cancelled_at
        );

        $this->assertNull(
            $subscription->ends_at
        );

        $this->assertTrue(
            $subscription->allowsOrganizationAccess()
        );

        $planRequest =
            $this->planRequest->fresh();

        $this->assertSame(
            OrganizationSubscriptionPlanRequest::
                STATUS_APPROVED,
            $planRequest->status
        );

        $this->assertSame(
            $this->billingUser->id,
            $planRequest->resolved_by_user_id
        );

        $this->assertSame(
            '2026-01-31 14:45:30',
            $planRequest
                ->resolved_at
                ->toDateTimeString()
        );

        foreach ([
            'organization.subscription_transaction_recorded',
            'organization.subscription_invoice_paid',
            'organization.subscription_plan_request_approved',
        ] as $action) {
            $this->assertDatabaseHas(
                'activity_logs',
                [
                    'organization_id' =>
                        $this->organization->id,

                    'action' =>
                        $action,
                ]
            );
        }
    }

    public function test_annual_invoice_payment_handles_leap_day_without_date_overflow(): void
    {
        Carbon::setTestNow(
            Carbon::parse(
                '2028-02-29 10:15:45'
            )
        );

        $this->invoice->update([
            'subtotal' =>
                '108000.00',

            'total_amount' =>
                '108000.00',
        ]);

        $this->planRequest->update([
            'billing_cycle' =>
                OrganizationSubscriptionPlanRequest::
                    BILLING_CYCLE_ANNUAL,

            'amount_snapshot' =>
                '108000.00',
        ]);

        $this
            ->actingAs(
                $this->billingUser
            )
            ->post(
                $this->paymentUrl(),
                [
                    'reference' =>
                        'BANK-ANNUAL-0001',

                    'payment_method' =>
                        'Bank transfer',
                ]
            )
            ->assertRedirect(
                $this->invoiceUrl()
            );

        $transaction =
            SubscriptionTransaction::query()
                ->where(
                    'reference',
                    'BANK-ANNUAL-0001'
                )
                ->sole();

        $this->assertSame(
            '108000.00',
            $transaction->amount
        );

        $this->assertSame(
            '2028-02-29 10:15:45',
            $transaction
                ->period_starts_at
                ->toDateTimeString()
        );

        $this->assertSame(
            '2029-02-28 10:15:45',
            $transaction
                ->period_ends_at
                ->toDateTimeString()
        );

        $subscription =
            $this->subscription->fresh();

        $this->assertSame(
            'annual',
            $subscription->billing_cycle
        );

        $this->assertSame(
            '2029-02-28 10:15:45',
            $subscription
                ->current_period_ends_at
                ->toDateTimeString()
        );
    }

    public function test_draft_invoice_cannot_receive_payment(): void
    {
        $this->invoice->update([
            'status' =>
                SubscriptionInvoiceStatus::DRAFT,

            'issue_date' =>
                null,

            'due_date' =>
                null,
        ]);

        $this
            ->actingAs(
                $this->billingUser
            )
            ->from(
                $this->invoiceUrl()
            )
            ->post(
                $this->paymentUrl(),
                [
                    'reference' =>
                        'DRAFT-PAYMENT-0001',

                    'payment_method' =>
                        'M-Pesa',
                ]
            )
            ->assertRedirect(
                $this->invoiceUrl()
            )
            ->assertSessionHasErrors([
                'invoice',
            ]);

        $this->assertDatabaseMissing(
            'subscription_transactions',
            [
                'reference' =>
                    'DRAFT-PAYMENT-0001',
            ]
        );

        $this->assertSame(
            SubscriptionInvoiceStatus::DRAFT,
            $this->invoice->fresh()->status
        );

        $this->assertSame(
            OrganizationSubscriptionPlanRequest::
                STATUS_PENDING,
            $this->planRequest->fresh()->status
        );
    }

    public function test_platform_auditor_can_view_invoice_but_cannot_record_payment(): void
    {
        $this
            ->actingAs(
                $this->auditor
            )
            ->get(
                $this->invoiceUrl()
            )
            ->assertOk()
            ->assertDontSee(
                'data-invoice-payment-form',
                false
            );

        $this
            ->actingAs(
                $this->auditor
            )
            ->post(
                $this->paymentUrl(),
                [
                    'reference' =>
                        'AUDITOR-PAYMENT-0001',

                    'payment_method' =>
                        'Bank transfer',
                ]
            )
            ->assertForbidden();

        $this->assertDatabaseMissing(
            'subscription_transactions',
            [
                'reference' =>
                    'AUDITOR-PAYMENT-0001',
            ]
        );
    }

    public function test_invoice_payment_cannot_cross_organization_boundary(): void
    {
        $otherOrganization =
            Organization::query()->create([
                'name' =>
                    'Other Invoice Clinic',

                'slug' =>
                    'other-invoice-clinic',
            ]);

        OrganizationSubscription::query()
            ->create([
                'organization_id' =>
                    $otherOrganization->id,

                'subscription_plan_id' =>
                    $this->currentPlan->id,

                'status' =>
                    OrganizationSubscriptionStatus::
                        EXPIRED,

                'payment_status' =>
                    SubscriptionPaymentStatus::
                        UNPAID,

                'billing_cycle' =>
                    'monthly',
            ]);

        $this
            ->actingAs(
                $this->billingUser
            )
            ->post(
                route(
                    'platform.organizations.subscription-invoices.payment.store',
                    [
                        $otherOrganization,
                        $this->invoice,
                    ]
                ),
                [
                    'reference' =>
                        'CROSS-ORG-PAYMENT-0001',

                    'payment_method' =>
                        'M-Pesa',
                ]
            )
            ->assertNotFound();

        $this->assertDatabaseMissing(
            'subscription_transactions',
            [
                'reference' =>
                    'CROSS-ORG-PAYMENT-0001',
            ]
        );
    }

    public function test_invoice_payment_and_activation_roll_back_when_audit_logging_fails(): void
    {
        $this->mock(
            ActivityLogger::class,
            function (
                MockInterface $mock
            ): void {
                $mock
                    ->shouldReceive('log')
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            'Simulated invoice payment audit failure.'
                        )
                    );
            }
        );

        $this->withoutExceptionHandling();

        $exceptionWasThrown = false;

        try {
            $this
                ->actingAs(
                    $this->billingUser
                )
                ->post(
                    $this->paymentUrl(),
                    [
                        'reference' =>
                            'ROLLBACK-PAYMENT-0001',

                        'payment_method' =>
                            'M-Pesa',
                    ]
                );
        } catch (RuntimeException $exception) {
            $exceptionWasThrown = true;

            $this->assertSame(
                'Simulated invoice payment audit failure.',
                $exception->getMessage()
            );
        }

        $this->assertTrue(
            $exceptionWasThrown,
            'The simulated audit failure was not thrown.'
        );

        $this->assertDatabaseMissing(
            'subscription_transactions',
            [
                'reference' =>
                    'ROLLBACK-PAYMENT-0001',
            ]
        );

        $this->assertSame(
            SubscriptionInvoiceStatus::ISSUED,
            $this->invoice->fresh()->status
        );

        $this->assertNull(
            $this->invoice->fresh()->paid_at
        );

        $this->assertSame(
            OrganizationSubscriptionPlanRequest::
                STATUS_PENDING,
            $this->planRequest->fresh()->status
        );

        $subscription =
            $this->subscription->fresh();

        $this->assertSame(
            $this->currentPlan->id,
            $subscription->subscription_plan_id
        );

        $this->assertSame(
            OrganizationSubscriptionStatus::EXPIRED,
            $subscription->status
        );

        $this->assertSame(
            SubscriptionPaymentStatus::UNPAID,
            $subscription->payment_status
        );

        $this->assertDatabaseCount(
            'activity_logs',
            0
        );
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

    private function invoiceUrl(): string
    {
        return route(
            'platform.organizations.subscription-invoices.show',
            [
                $this->organization,
                $this->invoice,
            ]
        );
    }

    private function paymentUrl(): string
    {
        return route(
            'platform.organizations.subscription-invoices.payment.store',
            [
                $this->organization,
                $this->invoice,
            ]
        );
    }
}
