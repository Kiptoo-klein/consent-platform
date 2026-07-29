<?php

namespace Tests\Feature;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionTransactionStatus;
use App\Enums\SubscriptionTransactionType;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\PlatformRole;
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

class PlatformOrganizationSubscriptionTransactionTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $organizationAdmin;

    private User $platformAdmin;

    private OrganizationSubscription $subscription;

    private SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PlatformRoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        $this->post('/register', [
            'organization_name' =>
                'Payment History Clinic',

            'name' =>
                'Payment History Administrator',

            'email' =>
                'payment-history-admin@example.com',

            'password' =>
                'StrongPass1!',

            'password_confirmation' =>
                'StrongPass1!',
        ]);

        $this->organization = Organization::query()
            ->where(
                'name',
                'Payment History Clinic'
            )
            ->firstOrFail();

        $this->organizationAdmin = User::query()
            ->where(
                'email',
                'payment-history-admin@example.com'
            )
            ->firstOrFail();

        $this->subscription = $this->organization
            ->subscription()
            ->firstOrFail();

        $this->plan = $this->subscription
            ->plan()
            ->firstOrFail();

        $superAdminRole = PlatformRole::query()
            ->where('slug', 'super-admin')
            ->firstOrFail();

        $this->platformAdmin = User::factory()->create([
            'organization_id' => null,
            'platform_role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);

        $this->subscription->update([
            'status' =>
                OrganizationSubscriptionStatus::ACTIVE,

            'payment_status' =>
                SubscriptionPaymentStatus::UNPAID,

            'trial_ends_at' => null,

            'current_period_starts_at' =>
                now()->subDay()->startOfMinute(),

            'current_period_ends_at' =>
                now()->addMonth()->startOfMinute(),

            'cancelled_at' => null,

            'ends_at' => null,
        ]);
    }

    public function test_platform_admin_can_record_successful_payment(): void
    {
        $paidAt = now()->startOfMinute();

        $response = $this
            ->actingAs($this->platformAdmin)
            ->post(
                $this->historyUrl(),
                $this->transactionPayload([
                    'reference' => 'PAY-2026-0001',

                    'type' =>
                        SubscriptionTransactionType::PAYMENT
                            ->value,

                    'status' =>
                        SubscriptionTransactionStatus::SUCCESSFUL
                            ->value,

                    'amount' => '249.50',
                    'currency' => 'usd',
                    'payment_method' => 'Card',
                    'paid_at' =>
                        $paidAt->format('Y-m-d\TH:i'),
                ])
            );

        $response
            ->assertRedirect($this->historyUrl())
            ->assertSessionHas('success');

        $transaction = SubscriptionTransaction::query()
            ->where('reference', 'PAY-2026-0001')
            ->sole();

        $this->assertSame(
            $this->subscription->id,
            $transaction->organization_subscription_id
        );

        $this->assertSame(
            $this->organization->id,
            $transaction->organization_id
        );

        $this->assertSame(
            $this->plan->id,
            $transaction->subscription_plan_id
        );

        $this->assertSame(
            $this->platformAdmin->id,
            $transaction->recorded_by_user_id
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
            '249.50',
            $transaction->amount
        );

        $this->assertSame(
            'USD',
            $transaction->currency
        );

        $this->assertSame(
            $paidAt->toDateTimeString(),
            $transaction->paid_at->toDateTimeString()
        );

        $subscription = $this->subscription->fresh();

        $this->assertSame(
            SubscriptionPaymentStatus::PAID,
            $subscription->payment_status
        );

        $this->assertSame(
            OrganizationSubscriptionStatus::ACTIVE,
            $subscription->status
        );

        $this->assertTrue(
            $subscription->allowsOrganizationAccess()
        );

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_transaction_recorded'
            )
            ->sole();

        $this->assertSame(
            $transaction->getMorphClass(),
            $activity->subject_type
        );

        $this->assertSame(
            $transaction->id,
            $activity->subject_id
        );

        $this->assertSame(
            $this->platformAdmin->id,
            $activity->user_id
        );

        $this->assertSame(
            'PAY-2026-0001',
            data_get(
                $activity->properties,
                'reference'
            )
        );

        $this->assertSame(
            '249.50',
            data_get(
                $activity->properties,
                'amount'
            )
        );

        $this->assertSame(
            'USD',
            data_get(
                $activity->properties,
                'currency'
            )
        );
    }

    public function test_successful_renewal_transaction_reactivates_subscription(): void
    {
        $periodStart = now()
            ->addMinute()
            ->startOfMinute();

        $periodEnd = $periodStart
            ->copy()
            ->addMonth();

        $this->subscription->update([
            'status' =>
                OrganizationSubscriptionStatus::EXPIRED,

            'payment_status' =>
                SubscriptionPaymentStatus::PAST_DUE,

            'trial_ends_at' =>
                now()->subMonth(),

            'current_period_starts_at' =>
                now()->subMonth(),

            'current_period_ends_at' =>
                now()->subMinute(),

            'cancelled_at' =>
                now()->subDay(),

            'ends_at' =>
                now()->subMinute(),
        ]);

        $this
            ->actingAs($this->platformAdmin)
            ->post(
                $this->historyUrl(),
                $this->transactionPayload([
                    'reference' => 'REN-2026-0001',

                    'type' =>
                        SubscriptionTransactionType::RENEWAL
                            ->value,

                    'status' =>
                        SubscriptionTransactionStatus::SUCCESSFUL
                            ->value,

                    'period_starts_at' =>
                        $periodStart->format('Y-m-d\TH:i'),

                    'period_ends_at' =>
                        $periodEnd->format('Y-m-d\TH:i'),
                ])
            )
            ->assertRedirect($this->historyUrl())
            ->assertSessionHas('success');

        $transaction = SubscriptionTransaction::query()
            ->where('reference', 'REN-2026-0001')
            ->sole();

        $this->assertSame(
            SubscriptionTransactionType::RENEWAL,
            $transaction->type
        );

        $this->assertSame(
            $periodStart->toDateTimeString(),
            $transaction
                ->period_starts_at
                ->toDateTimeString()
        );

        $this->assertSame(
            $periodEnd->toDateTimeString(),
            $transaction
                ->period_ends_at
                ->toDateTimeString()
        );

        $subscription = $this->subscription->fresh();

        $this->assertSame(
            OrganizationSubscriptionStatus::ACTIVE,
            $subscription->status
        );

        $this->assertSame(
            SubscriptionPaymentStatus::PAID,
            $subscription->payment_status
        );

        $this->assertSame(
            $periodStart->toDateTimeString(),
            $subscription
                ->current_period_starts_at
                ->toDateTimeString()
        );

        $this->assertSame(
            $periodEnd->toDateTimeString(),
            $subscription
                ->current_period_ends_at
                ->toDateTimeString()
        );

        $this->assertNull($subscription->trial_ends_at);
        $this->assertNull($subscription->cancelled_at);
        $this->assertNull($subscription->ends_at);

        $this->assertTrue(
            $subscription->allowsOrganizationAccess()
        );
    }

    public function test_failed_transaction_does_not_grant_access(): void
    {
        $this
            ->actingAs($this->platformAdmin)
            ->post(
                $this->historyUrl(),
                $this->transactionPayload([
                    'reference' => 'PAY-FAILED-0001',

                    'status' =>
                        SubscriptionTransactionStatus::FAILED
                            ->value,

                    'paid_at' => null,
                ])
            )
            ->assertRedirect($this->historyUrl());

        $transaction = SubscriptionTransaction::query()
            ->where(
                'reference',
                'PAY-FAILED-0001'
            )
            ->sole();

        $this->assertSame(
            SubscriptionTransactionStatus::FAILED,
            $transaction->status
        );

        $this->assertNull($transaction->paid_at);

        $subscription = $this->subscription->fresh();

        $this->assertSame(
            SubscriptionPaymentStatus::UNPAID,
            $subscription->payment_status
        );

        $this->assertFalse(
            $subscription->allowsOrganizationAccess()
        );
    }

    public function test_successful_renewal_requires_period_dates(): void
    {
        $response = $this
            ->actingAs($this->platformAdmin)
            ->from($this->historyUrl())
            ->post(
                $this->historyUrl(),
                $this->transactionPayload([
                    'reference' => 'REN-MISSING-DATES',

                    'type' =>
                        SubscriptionTransactionType::RENEWAL
                            ->value,

                    'status' =>
                        SubscriptionTransactionStatus::SUCCESSFUL
                            ->value,

                    'period_starts_at' => null,
                    'period_ends_at' => null,
                ])
            );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors([
                'period_starts_at',
                'period_ends_at',
            ]);

        $this->assertDatabaseMissing(
            'subscription_transactions',
            [
                'reference' =>
                    'REN-MISSING-DATES',
            ]
        );
    }

    public function test_duplicate_transaction_reference_is_rejected(): void
    {
        $this->createTransaction([
            'reference' => 'PAY-DUPLICATE-0001',
        ]);

        $response = $this
            ->actingAs($this->platformAdmin)
            ->from($this->historyUrl())
            ->post(
                $this->historyUrl(),
                $this->transactionPayload([
                    'reference' =>
                        'PAY-DUPLICATE-0001',
                ])
            );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('reference');

        $this->assertSame(
            1,
            SubscriptionTransaction::query()
                ->where(
                    'reference',
                    'PAY-DUPLICATE-0001'
                )
                ->count()
        );
    }

    public function test_invalid_transaction_data_is_rejected(): void
    {
        $periodStart = now()
            ->addMonth()
            ->startOfMinute();

        $periodEnd = $periodStart
            ->copy()
            ->subMinute();

        $response = $this
            ->actingAs($this->platformAdmin)
            ->from($this->historyUrl())
            ->post(
                $this->historyUrl(),
                $this->transactionPayload([
                    'reference' => '',

                    'type' => 'unsupported',

                    'status' => 'unsupported',

                    'amount' => '0',

                    'currency' => 'US',

                    'period_starts_at' =>
                        $periodStart->format('Y-m-d\TH:i'),

                    'period_ends_at' =>
                        $periodEnd->format('Y-m-d\TH:i'),
                ])
            );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors([
                'reference',
                'type',
                'status',
                'amount',
                'currency',
                'period_ends_at',
            ]);

        $this->assertDatabaseCount(
            'subscription_transactions',
            0
        );
    }

    public function test_organization_user_cannot_record_transactions(): void
    {
        $this
            ->actingAs($this->organizationAdmin)
            ->post(
                $this->historyUrl(),
                $this->transactionPayload()
            )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'subscription_transactions',
            0
        );
    }

    public function test_transaction_and_subscription_update_roll_back_when_audit_fails(): void
    {
        $before = $this->subscription->fresh();

        $this->mock(
            ActivityLogger::class,
            function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('log')
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            'Simulated transaction audit failure.'
                        )
                    );
            }
        );

        $exceptionWasThrown = false;

        $this->withoutExceptionHandling();

        try {
            $this
                ->actingAs($this->platformAdmin)
                ->post(
                    $this->historyUrl(),
                    $this->transactionPayload([
                        'reference' =>
                            'PAY-ROLLBACK-0001',
                    ])
                );
        } catch (RuntimeException $exception) {
            $exceptionWasThrown = true;

            $this->assertSame(
                'Simulated transaction audit failure.',
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
                    'PAY-ROLLBACK-0001',
            ]
        );

        $subscription = $this->subscription->fresh();

        $this->assertSame(
            $before->status,
            $subscription->status
        );

        $this->assertSame(
            $before->payment_status,
            $subscription->payment_status
        );
    }

    public function test_history_page_lists_only_organization_transactions(): void
    {
        $visible = $this->createTransaction([
            'reference' => 'PAY-VISIBLE-0001',
        ]);

        $otherOrganization =
            Organization::query()->create([
                'name' =>
                    'Other Payment History Clinic',

                'slug' =>
                    'other-payment-history-clinic',
            ]);

        $otherSubscription =
            OrganizationSubscription::query()->create([
                'organization_id' =>
                    $otherOrganization->id,

                'subscription_plan_id' =>
                    $this->plan->id,

                'billing_owner_user_id' => null,

                'status' =>
                    OrganizationSubscriptionStatus::ACTIVE,

                'payment_status' =>
                    SubscriptionPaymentStatus::PAID,

                'starts_at' =>
                    now()->subMonth(),

                'trial_ends_at' => null,

                'current_period_starts_at' =>
                    now()->subDay(),

                'current_period_ends_at' =>
                    now()->addMonth(),

                'cancelled_at' => null,

                'ends_at' => null,
            ]);

        $hidden = SubscriptionTransaction::query()->create([
            'organization_subscription_id' =>
                $otherSubscription->id,

            'organization_id' =>
                $otherOrganization->id,

            'subscription_plan_id' =>
                $this->plan->id,

            'reference' =>
                'PAY-HIDDEN-0001',

            'type' =>
                SubscriptionTransactionType::PAYMENT,

            'status' =>
                SubscriptionTransactionStatus::SUCCESSFUL,

            'amount' => '100.00',
            'currency' => 'USD',
            'payment_method' => 'Card',
            'paid_at' => now(),
            'recorded_by_user_id' =>
                $this->platformAdmin->id,
        ]);

        $response = $this
            ->actingAs($this->platformAdmin)
            ->get($this->historyUrl());

        $response->assertOk();

        $response->assertSeeText(
            'Subscription Payment History'
        );

        $response->assertSeeText(
            $visible->reference
        );

        $response->assertDontSeeText(
            $hidden->reference
        );

        $response->assertSeeText(
            'Record Transaction'
        );

        $response->assertSee(
            'name="reference"',
            false
        );

        $response->assertSee(
            'name="amount"',
            false
        );
    }

    public function test_receipt_page_displays_transaction_details(): void
    {
        $transaction = $this->createTransaction([
            'reference' => 'PAY-RECEIPT-0001',

            'amount' => '1250.75',

            'currency' => 'USD',

            'payment_method' =>
                'Bank transfer',

            'notes' =>
                'Annual payment confirmation.',
        ]);

        $response = $this
            ->actingAs($this->platformAdmin)
            ->get(
                $this->receiptUrl($transaction)
            );

        $response->assertOk();

        $response->assertSeeText(
            'Subscription Receipt'
        );

        $response->assertSeeText(
            'PAY-RECEIPT-0001'
        );

        $response->assertSeeText(
            '1,250.75'
        );

        $response->assertSeeText(
            'USD'
        );

        $response->assertSeeText(
            'Bank transfer'
        );

        $response->assertSeeText(
            'Annual payment confirmation.'
        );

        $response->assertSeeText(
            $this->organization->name
        );

        $response->assertSeeText(
            $this->plan->name
        );
    }

    public function test_receipt_cannot_cross_organization_boundary(): void
    {
        /*
         * Prove the receipt endpoint works for a transaction owned by
         * the organization before testing the cross-organization 404.
         */
        $ownedTransaction = $this->createTransaction([
            'reference' =>
                'PAY-BOUNDARY-CONTROL-0001',
        ]);

        $this
            ->actingAs($this->platformAdmin)
            ->get(
                $this->receiptUrl($ownedTransaction)
            )
            ->assertOk();

        $otherOrganization =
            Organization::query()->create([
                'name' =>
                    'Other Payment History Clinic',

                'slug' =>
                    'other-payment-history-clinic',
            ]);

        $otherSubscription =
            OrganizationSubscription::query()->create([
                'organization_id' =>
                    $otherOrganization->id,

                'subscription_plan_id' =>
                    $this->plan->id,

                'billing_owner_user_id' => null,

                'status' =>
                    OrganizationSubscriptionStatus::ACTIVE,

                'payment_status' =>
                    SubscriptionPaymentStatus::PAID,

                'current_period_starts_at' =>
                    now()->subDay(),

                'current_period_ends_at' =>
                    now()->addMonth(),
            ]);

        $transaction =
            SubscriptionTransaction::query()->create([
                'organization_subscription_id' =>
                    $otherSubscription->id,

                'organization_id' =>
                    $otherOrganization->id,

                'subscription_plan_id' =>
                    $this->plan->id,

                'reference' =>
                    'PAY-OTHER-ORG-0001',

                'type' =>
                    SubscriptionTransactionType::PAYMENT,

                'status' =>
                    SubscriptionTransactionStatus::SUCCESSFUL,

                'amount' => '100.00',
                'currency' => 'USD',
                'paid_at' => now(),

                'recorded_by_user_id' =>
                    $this->platformAdmin->id,
            ]);

        $this
            ->actingAs($this->platformAdmin)
            ->get(
                $this->receiptUrl($transaction)
            )
            ->assertNotFound();
    }

    public function test_organization_page_links_to_payment_history(): void
    {
        $response = $this
            ->actingAs($this->platformAdmin)
            ->get(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            );

        $response->assertOk();

        $response->assertSeeText(
            'Payment History'
        );

        $response->assertSee(
            $this->historyUrl(),
            false
        );
    }

    private function historyUrl(): string
    {
        return "/platform/organizations/"
            ."{$this->organization->id}"
            ."/subscription-transactions";
    }

    private function receiptUrl(
        SubscriptionTransaction $transaction
    ): string {
        return $this->historyUrl()
            ."/{$transaction->id}";
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function transactionPayload(
        array $overrides = []
    ): array {
        $paidAt = now()->startOfMinute();

        return array_merge(
            [
                'reference' =>
                    'PAY-DEFAULT-0001',

                'type' =>
                    SubscriptionTransactionType::PAYMENT
                        ->value,

                'status' =>
                    SubscriptionTransactionStatus::SUCCESSFUL
                        ->value,

                'amount' => '100.00',
                'currency' => 'USD',
                'payment_method' => 'Card',

                'paid_at' =>
                    $paidAt->format('Y-m-d\TH:i'),

                'period_starts_at' => null,
                'period_ends_at' => null,

                'notes' =>
                    'Recorded by Platform Administration.',
            ],
            $overrides
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createTransaction(
        array $overrides = []
    ): SubscriptionTransaction {
        return SubscriptionTransaction::query()->create(
            array_merge(
                [
                    'organization_subscription_id' =>
                        $this->subscription->id,

                    'organization_id' =>
                        $this->organization->id,

                    'subscription_plan_id' =>
                        $this->plan->id,

                    'reference' =>
                        'PAY-DIRECT-0001',

                    'type' =>
                        SubscriptionTransactionType::PAYMENT,

                    'status' =>
                        SubscriptionTransactionStatus::SUCCESSFUL,

                    'amount' => '100.00',
                    'currency' => 'USD',
                    'payment_method' => 'Card',
                    'paid_at' => now(),

                    'period_starts_at' => null,
                    'period_ends_at' => null,

                    'notes' => null,

                    'recorded_by_user_id' =>
                        $this->platformAdmin->id,
                ],
                $overrides
            )
        );
    }
}
