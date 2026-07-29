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
use App\Models\PlatformRole;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use App\Services\ActivityLogger;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class PlatformOrganizationSubscriptionInvoiceTest extends TestCase
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
                'Invoice Management Clinic',

            'name' =>
                'Invoice Management Administrator',

            'email' =>
                'invoice-management-admin@example.com',

            'password' =>
                'StrongPass1!',

            'password_confirmation' =>
                'StrongPass1!',
        ]);

        $this->organization = Organization::query()
            ->where(
                'name',
                'Invoice Management Clinic'
            )
            ->firstOrFail();

        $this->organizationAdmin = User::query()
            ->where(
                'email',
                'invoice-management-admin@example.com'
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

    public function test_platform_admin_can_create_draft_invoice(): void
    {
        $response = $this
            ->actingAs($this->platformAdmin)
            ->post(
                $this->indexUrl(),
                $this->invoicePayload([
                    'invoice_number' =>
                        'INV-2026-0001',

                    'subtotal' =>
                        '100.00',

                    'tax_amount' =>
                        '16.00',

                    'currency' =>
                        'usd',

                    'notes' =>
                        'Monthly subscription invoice.',
                ])
            );

        $response
            ->assertRedirect($this->indexUrl())
            ->assertSessionHas('success');

        $invoice = SubscriptionInvoice::query()
            ->where(
                'invoice_number',
                'INV-2026-0001'
            )
            ->sole();

        $this->assertSame(
            $this->subscription->id,
            $invoice->organization_subscription_id
        );

        $this->assertSame(
            $this->organization->id,
            $invoice->organization_id
        );

        $this->assertSame(
            $this->plan->id,
            $invoice->subscription_plan_id
        );

        $this->assertSame(
            SubscriptionInvoiceStatus::DRAFT,
            $invoice->status
        );

        $this->assertSame(
            '100.00',
            $invoice->subtotal
        );

        $this->assertSame(
            '16.00',
            $invoice->tax_amount
        );

        $this->assertSame(
            '116.00',
            $invoice->total_amount
        );

        $this->assertSame(
            'USD',
            $invoice->currency
        );

        $this->assertNull($invoice->issue_date);
        $this->assertNull($invoice->due_date);
        $this->assertNull($invoice->issued_by_user_id);
        $this->assertNull($invoice->paid_at);

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_invoice_created'
            )
            ->sole();

        $this->assertSame(
            $invoice->getMorphClass(),
            $activity->subject_type
        );

        $this->assertSame(
            $invoice->id,
            $activity->subject_id
        );

        $this->assertSame(
            $this->organization->id,
            $activity->organization_id
        );

        $this->assertSame(
            $this->platformAdmin->id,
            $activity->user_id
        );

        $this->assertSame(
            'INV-2026-0001',
            data_get(
                $activity->properties,
                'invoice_number'
            )
        );

        $this->assertSame(
            '116.00',
            data_get(
                $activity->properties,
                'total_amount'
            )
        );
    }

    public function test_platform_admin_can_issue_draft_invoice(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-ISSUE-0001',
        ]);

        $issueDate = now()->toDateString();

        $dueDate = now()
            ->addDays(14)
            ->toDateString();

        $response = $this
            ->actingAs($this->platformAdmin)
            ->patch(
                $this->issueUrl($invoice),
                [
                    'issue_date' =>
                        $issueDate,

                    'due_date' =>
                        $dueDate,
                ]
            );

        $response
            ->assertRedirect(
                $this->showUrl($invoice)
            )
            ->assertSessionHas('success');

        $invoice->refresh();

        $this->assertSame(
            SubscriptionInvoiceStatus::ISSUED,
            $invoice->status
        );

        $this->assertSame(
            $issueDate,
            $invoice->issue_date->toDateString()
        );

        $this->assertSame(
            $dueDate,
            $invoice->due_date->toDateString()
        );

        $this->assertSame(
            $this->platformAdmin->id,
            $invoice->issued_by_user_id
        );

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_invoice_issued'
            )
            ->sole();

        $this->assertSame(
            $invoice->id,
            $activity->subject_id
        );

        $this->assertSame(
            $issueDate,
            data_get(
                $activity->properties,
                'issue_date'
            )
        );

        $this->assertSame(
            $dueDate,
            data_get(
                $activity->properties,
                'due_date'
            )
        );
    }

    public function test_invoice_due_date_must_not_precede_issue_date(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-DATE-INVALID',
        ]);

        $response = $this
            ->actingAs($this->platformAdmin)
            ->from($this->showUrl($invoice))
            ->patch(
                $this->issueUrl($invoice),
                [
                    'issue_date' =>
                        now()->toDateString(),

                    'due_date' =>
                        now()
                            ->subDay()
                            ->toDateString(),
                ]
            );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('due_date');

        $this->assertSame(
            SubscriptionInvoiceStatus::DRAFT,
            $invoice->fresh()->status
        );
    }

    public function test_invalid_invoice_data_is_rejected(): void
    {
        $response = $this
            ->actingAs($this->platformAdmin)
            ->from($this->indexUrl())
            ->post(
                $this->indexUrl(),
                [
                    'invoice_number' => '',
                    'subtotal' => '-1',
                    'tax_amount' => '-1',
                    'currency' => 'US',
                    'notes' => str_repeat('x', 5001),
                ]
            );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors([
                'invoice_number',
                'subtotal',
                'tax_amount',
                'currency',
                'notes',
            ]);

        $this->assertDatabaseCount(
            'subscription_invoices',
            0
        );
    }

    public function test_duplicate_invoice_number_is_rejected(): void
    {
        $this->createInvoice([
            'invoice_number' =>
                'INV-DUPLICATE-0001',
        ]);

        $response = $this
            ->actingAs($this->platformAdmin)
            ->from($this->indexUrl())
            ->post(
                $this->indexUrl(),
                $this->invoicePayload([
                    'invoice_number' =>
                        'INV-DUPLICATE-0001',
                ])
            );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors(
                'invoice_number'
            );

        $this->assertSame(
            1,
            SubscriptionInvoice::query()
                ->where(
                    'invoice_number',
                    'INV-DUPLICATE-0001'
                )
                ->count()
        );
    }

    public function test_organization_user_cannot_manage_invoices(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-ORG-FORBIDDEN',
        ]);

        $this
            ->actingAs($this->organizationAdmin)
            ->post(
                $this->indexUrl(),
                $this->invoicePayload()
            )
            ->assertForbidden();

        $this
            ->actingAs($this->organizationAdmin)
            ->patch(
                $this->issueUrl($invoice),
                [
                    'issue_date' =>
                        now()->toDateString(),

                    'due_date' =>
                        now()
                            ->addDays(14)
                            ->toDateString(),
                ]
            )
            ->assertForbidden();
    }

    public function test_invoice_history_is_tenant_scoped(): void
    {
        $visible = $this->createInvoice([
            'invoice_number' =>
                'INV-VISIBLE-0001',
        ]);

        $hidden =
            $this->createOtherOrganizationInvoice(
                'INV-HIDDEN-0001'
            );

        $response = $this
            ->actingAs($this->platformAdmin)
            ->get($this->indexUrl());

        $response->assertOk();

        $response->assertSeeText(
            'Subscription Invoices'
        );

        $response->assertSeeText(
            'Create Invoice'
        );

        $response->assertSeeText(
            $visible->invoice_number
        );

        $response->assertDontSeeText(
            $hidden->invoice_number
        );

        $response->assertSee(
            'name="invoice_number"',
            false
        );

        $response->assertSee(
            'name="subtotal"',
            false
        );

        $response->assertSee(
            'name="tax_amount"',
            false
        );
    }

    public function test_invoice_page_displays_details(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-SHOW-0001',

            'status' =>
                SubscriptionInvoiceStatus::ISSUED,

            'issue_date' =>
                now()->toDateString(),

            'due_date' =>
                now()->addDays(14)->toDateString(),

            'subtotal' =>
                '1000.00',

            'tax_amount' =>
                '160.00',

            'total_amount' =>
                '1160.00',

            'currency' =>
                'USD',

            'notes' =>
                'Annual subscription invoice.',

            'issued_by_user_id' =>
                $this->platformAdmin->id,
        ]);

        $response = $this
            ->actingAs($this->platformAdmin)
            ->get($this->showUrl($invoice));

        $response->assertOk();

        $response->assertSeeText(
            'Subscription Invoice'
        );

        $response->assertSeeText(
            'INV-SHOW-0001'
        );

        $response->assertSeeText(
            '1,160.00'
        );

        $response->assertSeeText('USD');

        $response->assertSeeText(
            'Annual subscription invoice.'
        );

        $response->assertSeeText(
            $this->organization->name
        );

        $response->assertSeeText(
            $this->plan->name
        );
    }

    public function test_invoice_page_cannot_cross_organization_boundary(): void
    {
        $owned = $this->createInvoice([
            'invoice_number' =>
                'INV-BOUNDARY-CONTROL',
        ]);

        $other =
            $this->createOtherOrganizationInvoice(
                'INV-OTHER-ORG-0001'
            );

        $this
            ->actingAs($this->platformAdmin)
            ->get($this->showUrl($owned))
            ->assertOk();

        $this
            ->actingAs($this->platformAdmin)
            ->get($this->showUrl($other))
            ->assertNotFound();
    }

    public function test_successful_full_payment_marks_invoice_paid(): void
    {
        $invoice = $this->createIssuedInvoice([
            'invoice_number' =>
                'INV-PAID-0001',

            'total_amount' =>
                '116.00',
        ]);

        $paidAt = now()->startOfMinute();

        $response = $this
            ->actingAs($this->platformAdmin)
            ->post(
                $this->transactionUrl(),
                $this->transactionPayload([
                    'reference' =>
                        'PAY-INVOICE-0001',

                    'subscription_invoice_id' =>
                        $invoice->id,

                    'amount' =>
                        '116.00',

                    'paid_at' =>
                        $paidAt->format(
                            'Y-m-d\TH:i'
                        ),
                ])
            );

        $response
            ->assertRedirect(
                $this->transactionUrl()
            )
            ->assertSessionHas('success');

        $transaction = SubscriptionTransaction::query()
            ->where(
                'reference',
                'PAY-INVOICE-0001'
            )
            ->sole();

        $this->assertSame(
            $invoice->id,
            $transaction->subscription_invoice_id
        );

        $invoice->refresh();

        $this->assertSame(
            SubscriptionInvoiceStatus::PAID,
            $invoice->status
        );

        $this->assertSame(
            $paidAt->toDateTimeString(),
            $invoice->paid_at->toDateTimeString()
        );

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_invoice_paid'
            )
            ->sole();

        $this->assertSame(
            $invoice->id,
            $activity->subject_id
        );

        $this->assertSame(
            '116.00',
            data_get(
                $activity->properties,
                'paid_total'
            )
        );
    }

    public function test_partial_payments_mark_invoice_paid_only_when_total_is_covered(): void
    {
        $invoice = $this->createIssuedInvoice([
            'invoice_number' =>
                'INV-PARTIAL-0001',

            'total_amount' =>
                '100.00',
        ]);

        $this
            ->actingAs($this->platformAdmin)
            ->post(
                $this->transactionUrl(),
                $this->transactionPayload([
                    'reference' =>
                        'PAY-PARTIAL-0001',

                    'subscription_invoice_id' =>
                        $invoice->id,

                    'amount' =>
                        '40.00',
                ])
            )
            ->assertRedirect(
                $this->transactionUrl()
            );

        $this->assertSame(
            SubscriptionInvoiceStatus::ISSUED,
            $invoice->fresh()->status
        );

        $this
            ->actingAs($this->platformAdmin)
            ->post(
                $this->transactionUrl(),
                $this->transactionPayload([
                    'reference' =>
                        'PAY-PARTIAL-0002',

                    'subscription_invoice_id' =>
                        $invoice->id,

                    'amount' =>
                        '60.00',
                ])
            )
            ->assertRedirect(
                $this->transactionUrl()
            );

        $invoice->refresh();

        $this->assertSame(
            SubscriptionInvoiceStatus::PAID,
            $invoice->status
        );

        $this->assertNotNull($invoice->paid_at);

        $paidTotal = $invoice
            ->transactions()
            ->where(
                'status',
                SubscriptionTransactionStatus::SUCCESSFUL->value
            )
            ->sum('amount');

        $this->assertSame(
            '100.00',
            number_format(
                (float) $paidTotal,
                2,
                '.',
                ''
            )
        );
    }

    public function test_failed_transaction_does_not_pay_invoice(): void
    {
        $invoice = $this->createIssuedInvoice([
            'invoice_number' =>
                'INV-FAILED-PAYMENT',

            'total_amount' =>
                '100.00',
        ]);

        $this
            ->actingAs($this->platformAdmin)
            ->post(
                $this->transactionUrl(),
                $this->transactionPayload([
                    'reference' =>
                        'PAY-INVOICE-FAILED',

                    'subscription_invoice_id' =>
                        $invoice->id,

                    'status' =>
                        SubscriptionTransactionStatus::FAILED
                            ->value,

                    'paid_at' => null,
                ])
            )
            ->assertRedirect(
                $this->transactionUrl()
            );

        $transaction = SubscriptionTransaction::query()
            ->where(
                'reference',
                'PAY-INVOICE-FAILED'
            )
            ->sole();

        $this->assertSame(
            $invoice->id,
            $transaction->subscription_invoice_id
        );

        $invoice->refresh();

        $this->assertSame(
            SubscriptionInvoiceStatus::ISSUED,
            $invoice->status
        );

        $this->assertNull($invoice->paid_at);
    }

    public function test_invoice_link_must_match_organization_and_currency(): void
    {
        $otherInvoice =
            $this->createOtherOrganizationInvoice(
                'INV-OTHER-LINK-0001',
                SubscriptionInvoiceStatus::ISSUED
            );

        $response = $this
            ->actingAs($this->platformAdmin)
            ->from($this->transactionUrl())
            ->post(
                $this->transactionUrl(),
                $this->transactionPayload([
                    'reference' =>
                        'PAY-OTHER-INVOICE',

                    'subscription_invoice_id' =>
                        $otherInvoice->id,
                ])
            );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors(
                'subscription_invoice_id'
            );

        $invoice = $this->createIssuedInvoice([
            'invoice_number' =>
                'INV-CURRENCY-0001',

            'currency' =>
                'USD',
        ]);

        $response = $this
            ->actingAs($this->platformAdmin)
            ->from($this->transactionUrl())
            ->post(
                $this->transactionUrl(),
                $this->transactionPayload([
                    'reference' =>
                        'PAY-CURRENCY-MISMATCH',

                    'subscription_invoice_id' =>
                        $invoice->id,

                    'currency' =>
                        'KES',
                ])
            );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors(
                'subscription_invoice_id'
            );

        $this->assertDatabaseMissing(
            'subscription_transactions',
            [
                'reference' =>
                    'PAY-OTHER-INVOICE',
            ]
        );

        $this->assertDatabaseMissing(
            'subscription_transactions',
            [
                'reference' =>
                    'PAY-CURRENCY-MISMATCH',
            ]
        );
    }

    public function test_invoice_creation_rolls_back_when_audit_fails(): void
    {
        $this->mock(
            ActivityLogger::class,
            function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('log')
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            'Simulated invoice audit failure.'
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
                    $this->indexUrl(),
                    $this->invoicePayload([
                        'invoice_number' =>
                            'INV-ROLLBACK-0001',
                    ])
                );
        } catch (RuntimeException $exception) {
            $exceptionWasThrown = true;

            $this->assertSame(
                'Simulated invoice audit failure.',
                $exception->getMessage()
            );
        }

        $this->assertTrue(
            $exceptionWasThrown,
            'The simulated invoice audit failure was not thrown.'
        );

        $this->assertDatabaseMissing(
            'subscription_invoices',
            [
                'invoice_number' =>
                    'INV-ROLLBACK-0001',
            ]
        );
    }

    public function test_organization_page_links_to_subscription_invoices(): void
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
            'Invoices'
        );

        $response->assertSee(
            $this->indexUrl(),
            false
        );
    }

    private function indexUrl(): string
    {
        return "/platform/organizations/"
            ."{$this->organization->id}"
            ."/subscription-invoices";
    }

    private function showUrl(
        SubscriptionInvoice $invoice
    ): string {
        return $this->indexUrl()
            ."/{$invoice->id}";
    }

    private function issueUrl(
        SubscriptionInvoice $invoice
    ): string {
        return $this->showUrl($invoice)
            .'/issue';
    }

    private function transactionUrl(): string
    {
        return "/platform/organizations/"
            ."{$this->organization->id}"
            ."/subscription-transactions";
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function invoicePayload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'invoice_number' =>
                    'INV-DEFAULT-0001',

                'subtotal' =>
                    '100.00',

                'tax_amount' =>
                    '0.00',

                'currency' =>
                    'USD',

                'notes' => null,
            ],
            $overrides
        );
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function transactionPayload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'reference' =>
                    'PAY-INVOICE-DEFAULT',

                'subscription_invoice_id' =>
                    null,

                'type' =>
                    SubscriptionTransactionType::PAYMENT
                        ->value,

                'status' =>
                    SubscriptionTransactionStatus::SUCCESSFUL
                        ->value,

                'amount' =>
                    '100.00',

                'currency' =>
                    'USD',

                'payment_method' =>
                    'Card',

                'paid_at' =>
                    now()
                        ->startOfMinute()
                        ->format('Y-m-d\TH:i'),

                'period_starts_at' => null,
                'period_ends_at' => null,

                'notes' =>
                    'Payment linked to an invoice.',
            ],
            $overrides
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createInvoice(
        array $overrides = []
    ): SubscriptionInvoice {
        return SubscriptionInvoice::query()->create(
            array_merge(
                [
                    'organization_subscription_id' =>
                        $this->subscription->id,

                    'organization_id' =>
                        $this->organization->id,

                    'subscription_plan_id' =>
                        $this->plan->id,

                    'invoice_number' =>
                        'INV-DIRECT-0001',

                    'status' =>
                        SubscriptionInvoiceStatus::DRAFT,

                    'issue_date' => null,
                    'due_date' => null,

                    'subtotal' =>
                        '100.00',

                    'tax_amount' =>
                        '0.00',

                    'total_amount' =>
                        '100.00',

                    'currency' =>
                        'USD',

                    'notes' => null,

                    'issued_by_user_id' =>
                        null,

                    'paid_at' => null,
                    'voided_at' => null,
                    'cancelled_at' => null,
                ],
                $overrides
            )
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createIssuedInvoice(
        array $overrides = []
    ): SubscriptionInvoice {
        return $this->createInvoice(
            array_merge(
                [
                    'status' =>
                        SubscriptionInvoiceStatus::ISSUED,

                    'issue_date' =>
                        now()->toDateString(),

                    'due_date' =>
                        now()
                            ->addDays(14)
                            ->toDateString(),

                    'issued_by_user_id' =>
                        $this->platformAdmin->id,
                ],
                $overrides
            )
        );
    }

    private function createOtherOrganizationInvoice(
        string $invoiceNumber,
        SubscriptionInvoiceStatus $status =
            SubscriptionInvoiceStatus::DRAFT
    ): SubscriptionInvoice {
        $organization = Organization::query()->create([
            'name' =>
                "Other {$invoiceNumber} Organization",

            'slug' =>
                strtolower(
                    str_replace(
                        '_',
                        '-',
                        $invoiceNumber
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

        return SubscriptionInvoice::query()->create([
            'organization_subscription_id' =>
                $subscription->id,

            'organization_id' =>
                $organization->id,

            'subscription_plan_id' =>
                $this->plan->id,

            'invoice_number' =>
                $invoiceNumber,

            'status' =>
                $status,

            'issue_date' =>
                $status === SubscriptionInvoiceStatus::DRAFT
                    ? null
                    : now()->toDateString(),

            'due_date' =>
                $status === SubscriptionInvoiceStatus::DRAFT
                    ? null
                    : now()
                        ->addDays(14)
                        ->toDateString(),

            'subtotal' =>
                '100.00',

            'tax_amount' =>
                '0.00',

            'total_amount' =>
                '100.00',

            'currency' =>
                'USD',

            'notes' => null,

            'issued_by_user_id' =>
                $status === SubscriptionInvoiceStatus::DRAFT
                    ? null
                    : $this->platformAdmin->id,

            'paid_at' => null,
            'voided_at' => null,
            'cancelled_at' => null,
        ]);
    }
}
