<?php

namespace Tests\Feature;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionTransactionStatus;
use App\Enums\SubscriptionTransactionType;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSubscriptionBillingPortalTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $organizationAdmin;

    private OrganizationSubscription $subscription;

    private SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(
            SubscriptionPlanSeeder::class
        );

        $this->post('/register', [
            'organization_name' =>
                'Billing Portal Clinic',

            'name' =>
                'Billing Portal Administrator',

            'email' =>
                'billing-portal-admin@example.com',

            'password' =>
                'StrongPass1!',

            'password_confirmation' =>
                'StrongPass1!',
        ]);

        $this->organization = Organization::query()
            ->where(
                'name',
                'Billing Portal Clinic'
            )
            ->firstOrFail();

        $this->organizationAdmin = User::query()
            ->where(
                'email',
                'billing-portal-admin@example.com'
            )
            ->firstOrFail();

        $this->subscription = $this->organization
            ->subscription()
            ->firstOrFail();

        $this->plan = $this->subscription
            ->plan()
            ->firstOrFail();

        $this->subscription->update([
            'billing_owner_user_id' =>
                $this->organizationAdmin->id,

            'status' =>
                OrganizationSubscriptionStatus::ACTIVE,

            'payment_status' =>
                SubscriptionPaymentStatus::PAID,

            'trial_ends_at' => null,

            'current_period_starts_at' =>
                now()->subDay()->startOfMinute(),

            'current_period_ends_at' =>
                now()->addMonth()->startOfMinute(),

            'cancelled_at' => null,
            'ends_at' => null,
        ]);
    }

    public function test_billing_owner_can_view_billing_portal(): void
    {
        $successful = $this->createTransaction([
            'reference' =>
                'PORTAL-SUCCESSFUL-0001',

            'type' =>
                SubscriptionTransactionType::PAYMENT,

            'status' =>
                SubscriptionTransactionStatus::SUCCESSFUL,
        ]);

        $pending = $this->createTransaction([
            'reference' =>
                'PORTAL-PENDING-0001',

            'type' =>
                SubscriptionTransactionType::ADJUSTMENT,

            'status' =>
                SubscriptionTransactionStatus::PENDING,

            'paid_at' => null,
        ]);

        $failed = $this->createTransaction([
            'reference' =>
                'PORTAL-FAILED-0001',

            'status' =>
                SubscriptionTransactionStatus::FAILED,

            'paid_at' => null,
        ]);

        $refunded = $this->createTransaction([
            'reference' =>
                'PORTAL-REFUNDED-0001',

            'type' =>
                SubscriptionTransactionType::REFUND,

            'status' =>
                SubscriptionTransactionStatus::REFUNDED,
        ]);

        $response = $this
            ->actingAs($this->organizationAdmin)
            ->get($this->portalUrl());

        $response->assertOk();

        $response->assertSeeText(
            'Billing & Receipts'
        );

        $response->assertSeeText(
            $this->organization->name
        );

        $response->assertSeeText(
            $this->plan->name
        );

        $response->assertSeeText('Paid');
        $response->assertSeeText('Active');

        foreach (
            [
                $successful,
                $pending,
                $failed,
                $refunded,
            ] as $transaction
        ) {
            $response->assertSeeText(
                $transaction->reference
            );
        }

        $response->assertSeeText('Successful');
        $response->assertSeeText('Pending');
        $response->assertSeeText('Failed');
        $response->assertSeeText('Refunded');

        /*
         * Organization users have read-only billing access.
         */
        $response->assertDontSeeText(
            'Record Transaction'
        );

        $response->assertDontSee(
            'name="reference"',
            false
        );

        $response->assertDontSee(
            "/platform/organizations/"
                ."{$this->organization->id}"
                ."/subscription-transactions",
            false
        );
    }

    public function test_latest_successful_payment_has_persistent_receipt_banner(): void
    {
        $older =
            $this->createTransaction([
                'reference' =>
                    'PORTAL-BANNER-OLDER',

                'status' =>
                    SubscriptionTransactionStatus::SUCCESSFUL,

                'amount' =>
                    '100.00',

                'paid_at' =>
                    now()->subDay(),
            ]);

        $latest =
            $this->createTransaction([
                'reference' =>
                    'PORTAL-BANNER-LATEST',

                'status' =>
                    SubscriptionTransactionStatus::SUCCESSFUL,

                'amount' =>
                    '275.50',

                'paid_at' =>
                    now(),
            ]);

        $this->createTransaction([
            'reference' =>
                'PORTAL-BANNER-PENDING',

            'status' =>
                SubscriptionTransactionStatus::PENDING,

            'paid_at' =>
                now()->addMinute(),
        ]);

        $response =
            $this
                ->actingAs(
                    $this->organizationAdmin
                )
                ->get(
                    $this->portalUrl()
                );

        $response->assertOk();

        $response->assertSeeText(
            'Payment confirmed'
        );

        $response->assertSeeText(
            'Your receipt is ready'
        );

        $response->assertSeeText(
            'PORTAL-BANNER-LATEST'
        );

        $response->assertSeeText(
            '275.50'
        );

        $response->assertSee(
            'data-payment-confirmed-banner',
            false
        );

        $response->assertSee(
            'data-transaction-id="'
                .$latest->id
                .'"',
            false
        );

        $response->assertDontSee(
            'data-transaction-id="'
                .$older->id
                .'"',
            false
        );

        $response->assertSee(
            route(
                'organization-billing.receipts.show',
                $latest
            ),
            false
        );

        $response->assertSee(
            route(
                'organization-billing.receipts.download',
                $latest
            ),
            false
        );
    }

    public function test_billing_portal_shows_clear_empty_state(): void
    {
        $response = $this
            ->actingAs($this->organizationAdmin)
            ->get($this->portalUrl());

        $response->assertOk();

        $response->assertSeeText(
            'No subscription transactions have been recorded.'
        );
    }

    public function test_non_billing_organization_user_cannot_view_portal(): void
    {
        $nonBillingUser = User::factory()->create([
            'organization_id' =>
                $this->organization->id,

            'platform_role_id' => null,

            'is_active' => true,
        ]);

        $this
            ->actingAs($nonBillingUser)
            ->get($this->portalUrl())
            ->assertForbidden();
    }

    public function test_billing_history_is_tenant_scoped(): void
    {
        $visible = $this->createTransaction([
            'reference' =>
                'PORTAL-VISIBLE-0001',
        ]);

        $hidden =
            $this->createOtherOrganizationTransaction(
                'PORTAL-HIDDEN-0001'
            );

        $response = $this
            ->actingAs($this->organizationAdmin)
            ->get($this->portalUrl());

        $response->assertOk();

        $response->assertSeeText(
            $visible->reference
        );

        $response->assertDontSeeText(
            $hidden->reference
        );
    }

    public function test_billing_owner_can_view_html_receipt(): void
    {
        $transaction = $this->createTransaction([
            'reference' =>
                'PORTAL-RECEIPT-0001',

            'amount' =>
                '1250.75',

            'currency' =>
                'USD',

            'payment_method' =>
                'Bank transfer',

            'notes' =>
                'Annual subscription payment.',
        ]);

        $response = $this
            ->actingAs($this->organizationAdmin)
            ->get(
                $this->receiptUrl(
                    $transaction
                )
            );

        $response->assertOk();

        $response->assertSeeText(
            'Subscription Receipt'
        );

        $response->assertSeeText(
            'PORTAL-RECEIPT-0001'
        );

        $response->assertSeeText(
            '1,250.75'
        );

        $response->assertSeeText('USD');

        $response->assertSeeText(
            'Bank transfer'
        );

        $response->assertSeeText(
            'Annual subscription payment.'
        );

        $response->assertSeeText(
            $this->organization->name
        );

        $response->assertSeeText(
            $this->plan->name
        );

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_receipt_viewed'
            )
            ->sole();

        $this->assertSame(
            $this->organization->id,
            $activity->organization_id
        );

        $this->assertSame(
            $this->organizationAdmin->id,
            $activity->user_id
        );

        $this->assertSame(
            $transaction->getMorphClass(),
            $activity->subject_type
        );

        $this->assertSame(
            $transaction->id,
            $activity->subject_id
        );

        $this->assertSame(
            $transaction->reference,
            data_get(
                $activity->properties,
                'reference'
            )
        );
    }

    public function test_billing_owner_cannot_view_other_organization_receipt(): void
    {
        $transaction =
            $this->createOtherOrganizationTransaction(
                'PORTAL-OTHER-RECEIPT-0001'
            );

        $this
            ->actingAs($this->organizationAdmin)
            ->get(
                $this->receiptUrl(
                    $transaction
                )
            )
            ->assertForbidden();

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'organization.subscription_receipt_viewed',

                'subject_id' =>
                    $transaction->id,
            ]
        );
    }

    public function test_billing_owner_can_download_pdf_receipt(): void
    {
        $transaction = $this->createTransaction([
            'reference' =>
                'PORTAL-PDF-0001',

            'amount' =>
                '799.50',

            'currency' =>
                'USD',

            'payment_method' =>
                'Card',
        ]);

        $response = $this
            ->actingAs($this->organizationAdmin)
            ->get(
                $this->downloadUrl(
                    $transaction
                )
            );

        $response->assertOk();

        $response->assertHeader(
            'Content-Type',
            'application/pdf'
        );

        $disposition = $response->headers->get(
            'Content-Disposition'
        );

        $this->assertNotNull($disposition);

        $this->assertStringContainsString(
            'subscription_receipt_PORTAL-PDF-0001.pdf',
            $disposition
        );

        $content = $response->getContent();

        $this->assertIsString($content);

        $this->assertStringStartsWith(
            '%PDF-',
            $content
        );

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_receipt_downloaded'
            )
            ->sole();

        $this->assertSame(
            $this->organization->id,
            $activity->organization_id
        );

        $this->assertSame(
            $this->organizationAdmin->id,
            $activity->user_id
        );

        $this->assertSame(
            $transaction->reference,
            data_get(
                $activity->properties,
                'reference'
            )
        );

        $this->assertSame(
            'subscription_receipt_PORTAL-PDF-0001.pdf',
            data_get(
                $activity->properties,
                'download_filename'
            )
        );
    }

    public function test_pdf_receipt_template_contains_transaction_details(): void
    {
        $transaction = $this->createTransaction([
            'reference' =>
                'PORTAL-PDF-CONTENT-0001',

            'amount' =>
                '450.25',

            'currency' =>
                'USD',

            'payment_method' =>
                'Mobile payment',

            'notes' =>
                'Quarterly billing payment.',
        ]);

        $transaction->load([
            'organization',
            'plan',
            'recordedBy',
        ]);

        $html = view(
            'pdfs.subscription-receipt',
            [
                'transaction' =>
                    $transaction,
            ]
        )->render();

        $this->assertStringContainsString(
            'Subscription Receipt',
            $html
        );

        $this->assertStringContainsString(
            'PORTAL-PDF-CONTENT-0001',
            $html
        );

        $this->assertStringContainsString(
            '450.25',
            $html
        );

        $this->assertStringContainsString(
            'USD',
            $html
        );

        $this->assertStringContainsString(
            'Mobile payment',
            $html
        );

        $this->assertStringContainsString(
            'Quarterly billing payment.',
            $html
        );

        $this->assertStringContainsString(
            $this->organization->name,
            $html
        );

        $this->assertStringContainsString(
            $this->plan->name,
            $html
        );
    }

    public function test_billing_owner_cannot_download_other_organization_receipt(): void
    {
        $transaction =
            $this->createOtherOrganizationTransaction(
                'PORTAL-OTHER-PDF-0001'
            );

        $this
            ->actingAs($this->organizationAdmin)
            ->get(
                $this->downloadUrl(
                    $transaction
                )
            )
            ->assertForbidden();

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'organization.subscription_receipt_downloaded',

                'subject_id' =>
                    $transaction->id,
            ]
        );
    }

    public function test_subscription_page_links_to_billing_portal(): void
    {
        $response = $this
            ->actingAs($this->organizationAdmin)
            ->get('/organization/subscription');

        $response->assertOk();

        $response->assertSeeText(
            'Billing & Receipts'
        );

        $response->assertSee(
            $this->portalUrl(),
            false
        );
    }

    public function test_guest_is_redirected_from_billing_portal(): void
    {
        /*
         * Registration in setUp authenticates the Organization Admin.
         * Explicitly end that session before testing guest access.
         */
        $this->post('/logout');

        $this->assertGuest();

        $this
            ->get($this->portalUrl())
            ->assertRedirect('/login');
    }

    private function portalUrl(): string
    {
        return '/subscription/billing';
    }

    private function receiptUrl(
        SubscriptionTransaction $transaction
    ): string {
        return $this->portalUrl()
            .'/transactions/'
            .$transaction->id;
    }

    private function downloadUrl(
        SubscriptionTransaction $transaction
    ): string {
        return $this->receiptUrl($transaction)
            .'/download';
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
                        'PORTAL-DEFAULT-0001',

                    'type' =>
                        SubscriptionTransactionType::PAYMENT,

                    'status' =>
                        SubscriptionTransactionStatus::SUCCESSFUL,

                    'amount' =>
                        '100.00',

                    'currency' =>
                        'USD',

                    'payment_method' =>
                        'Card',

                    'paid_at' =>
                        now()->startOfMinute(),

                    'period_starts_at' =>
                        $this->subscription
                            ->current_period_starts_at,

                    'period_ends_at' =>
                        $this->subscription
                            ->current_period_ends_at,

                    'notes' => null,

                    'recorded_by_user_id' =>
                        null,
                ],
                $overrides
            )
        );
    }

    private function createOtherOrganizationTransaction(
        string $reference
    ): SubscriptionTransaction {
        $organization = Organization::query()->create([
            'name' =>
                "Other {$reference} Organization",

            'slug' =>
                strtolower(
                    str_replace(
                        '_',
                        '-',
                        $reference
                    )
                ).'-organization',
        ]);

        $billingOwner = User::factory()->create([
            'organization_id' =>
                $organization->id,

            'platform_role_id' => null,

            'is_active' => true,
        ]);

        $subscription =
            OrganizationSubscription::query()->create([
                'organization_id' =>
                    $organization->id,

                'subscription_plan_id' =>
                    $this->plan->id,

                'billing_owner_user_id' =>
                    $billingOwner->id,

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

        return SubscriptionTransaction::query()->create([
            'organization_subscription_id' =>
                $subscription->id,

            'organization_id' =>
                $organization->id,

            'subscription_plan_id' =>
                $this->plan->id,

            'reference' =>
                $reference,

            'type' =>
                SubscriptionTransactionType::PAYMENT,

            'status' =>
                SubscriptionTransactionStatus::SUCCESSFUL,

            'amount' =>
                '100.00',

            'currency' =>
                'USD',

            'payment_method' =>
                'Card',

            'paid_at' =>
                now(),

            'period_starts_at' =>
                $subscription->current_period_starts_at,

            'period_ends_at' =>
                $subscription->current_period_ends_at,

            'notes' => null,

            'recorded_by_user_id' =>
                null,
        ]);
    }
}
