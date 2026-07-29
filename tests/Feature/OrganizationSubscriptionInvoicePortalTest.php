<?php

namespace Tests\Feature;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionInvoiceStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrganizationSubscriptionInvoicePortalTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $billingOwner;

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
                'Invoice Portal Clinic',

            'name' =>
                'Invoice Portal Administrator',

            'email' =>
                'invoice-portal-admin@example.com',

            'password' =>
                'StrongPass1!',

            'password_confirmation' =>
                'StrongPass1!',
        ]);

        $this->organization = Organization::query()
            ->where(
                'name',
                'Invoice Portal Clinic'
            )
            ->firstOrFail();

        $this->billingOwner = User::query()
            ->where(
                'email',
                'invoice-portal-admin@example.com'
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
                $this->billingOwner->id,

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

    public function test_billing_owner_can_view_non_draft_invoices(): void
    {
        $issued = $this->createInvoice([
            'invoice_number' =>
                'PORTAL-INVOICE-ISSUED',

            'status' =>
                SubscriptionInvoiceStatus::ISSUED,
        ]);

        $paid = $this->createInvoice([
            'invoice_number' =>
                'PORTAL-INVOICE-PAID',

            'status' =>
                SubscriptionInvoiceStatus::PAID,

            'paid_at' =>
                now()->startOfMinute(),
        ]);

        $overdue = $this->createInvoice([
            'invoice_number' =>
                'PORTAL-INVOICE-OVERDUE',

            'status' =>
                SubscriptionInvoiceStatus::OVERDUE,

            'due_date' =>
                now()->subDay()->toDateString(),
        ]);

        $voided = $this->createInvoice([
            'invoice_number' =>
                'PORTAL-INVOICE-VOID',

            'status' =>
                SubscriptionInvoiceStatus::VOIDED,

            'voided_at' =>
                now()->startOfMinute(),
        ]);

        $cancelled = $this->createInvoice([
            'invoice_number' =>
                'PORTAL-INVOICE-CANCELLED',

            'status' =>
                SubscriptionInvoiceStatus::CANCELLED,

            'cancelled_at' =>
                now()->startOfMinute(),
        ]);

        $draft = $this->createInvoice([
            'invoice_number' =>
                'PORTAL-INVOICE-DRAFT',

            'status' =>
                SubscriptionInvoiceStatus::DRAFT,

            'issue_date' => null,
            'due_date' => null,
        ]);

        $response = $this
            ->actingAs($this->billingOwner)
            ->get($this->portalUrl());

        $response->assertOk();

        $response->assertSeeText(
            'Subscription Invoices'
        );

        foreach (
            [
                $issued,
                $paid,
                $overdue,
                $voided,
                $cancelled,
            ] as $invoice
        ) {
            $response->assertSeeText(
                $invoice->invoice_number
            );

            $response->assertSee(
                $this->invoiceUrl($invoice),
                false
            );
        }

        $response->assertDontSeeText(
            $draft->invoice_number
        );
    }

    public function test_invoice_history_is_tenant_scoped(): void
    {
        $visible = $this->createInvoice([
            'invoice_number' =>
                'PORTAL-INVOICE-VISIBLE',
        ]);

        $hidden =
            $this->createOtherOrganizationInvoice(
                'PORTAL-INVOICE-HIDDEN'
            );

        $response = $this
            ->actingAs($this->billingOwner)
            ->get($this->portalUrl());

        $response->assertOk();

        $response->assertSeeText(
            $visible->invoice_number
        );

        $response->assertDontSeeText(
            $hidden->invoice_number
        );
    }

    public function test_billing_owner_can_view_html_invoice(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'PORTAL-INVOICE-SHOW',

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
        ]);

        $response = $this
            ->actingAs($this->billingOwner)
            ->get(
                $this->invoiceUrl($invoice)
            );

        $response->assertOk();

        $response->assertSeeText(
            'Subscription Invoice'
        );

        $response->assertSeeText(
            'PORTAL-INVOICE-SHOW'
        );

        $response->assertSeeText('1,160.00');
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

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_invoice_viewed'
            )
            ->sole();

        $this->assertSame(
            $this->organization->id,
            $activity->organization_id
        );

        $this->assertSame(
            $this->billingOwner->id,
            $activity->user_id
        );

        $this->assertSame(
            $invoice->getMorphClass(),
            $activity->subject_type
        );

        $this->assertSame(
            $invoice->id,
            $activity->subject_id
        );

        $this->assertSame(
            $invoice->invoice_number,
            data_get(
                $activity->properties,
                'invoice_number'
            )
        );
    }

    public function test_billing_owner_cannot_view_draft_invoice(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'PORTAL-DRAFT-FORBIDDEN',

            'status' =>
                SubscriptionInvoiceStatus::DRAFT,

            'issue_date' => null,
            'due_date' => null,
        ]);

        $this
            ->actingAs($this->billingOwner)
            ->get(
                $this->invoiceUrl($invoice)
            )
            ->assertForbidden();

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'organization.subscription_invoice_viewed',

                'subject_id' =>
                    $invoice->id,
            ]
        );
    }

    public function test_billing_owner_cannot_view_other_organization_invoice(): void
    {
        $invoice =
            $this->createOtherOrganizationInvoice(
                'PORTAL-OTHER-INVOICE'
            );

        $this
            ->actingAs($this->billingOwner)
            ->get(
                $this->invoiceUrl($invoice)
            )
            ->assertForbidden();

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'organization.subscription_invoice_viewed',

                'subject_id' =>
                    $invoice->id,
            ]
        );
    }

    public function test_billing_owner_can_download_pdf_invoice(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'PORTAL-PDF-0001',

            'subtotal' =>
                '700.00',

            'tax_amount' =>
                '99.50',

            'total_amount' =>
                '799.50',

            'currency' =>
                'USD',
        ]);

        $response = $this
            ->actingAs($this->billingOwner)
            ->get(
                $this->downloadUrl($invoice)
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
            'subscription_invoice_PORTAL-PDF-0001.pdf',
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
                'organization.subscription_invoice_downloaded'
            )
            ->sole();

        $this->assertSame(
            $this->organization->id,
            $activity->organization_id
        );

        $this->assertSame(
            $this->billingOwner->id,
            $activity->user_id
        );

        $this->assertSame(
            $invoice->invoice_number,
            data_get(
                $activity->properties,
                'invoice_number'
            )
        );

        $this->assertSame(
            'subscription_invoice_PORTAL-PDF-0001.pdf',
            data_get(
                $activity->properties,
                'download_filename'
            )
        );
    }

    public function test_pdf_invoice_template_contains_invoice_details(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'PORTAL-PDF-CONTENT',

            'subtotal' =>
                '400.00',

            'tax_amount' =>
                '50.25',

            'total_amount' =>
                '450.25',

            'currency' =>
                'USD',

            'notes' =>
                'Quarterly subscription invoice.',
        ]);

        $invoice->load([
            'organization',
            'plan',
            'issuedBy',
            'transactions',
        ]);

        $html = view(
            'pdfs.subscription-invoice',
            [
                'invoice' =>
                    $invoice,
            ]
        )->render();

        $this->assertStringContainsString(
            'Subscription Invoice',
            $html
        );

        $this->assertStringContainsString(
            'PORTAL-PDF-CONTENT',
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
            'Quarterly subscription invoice.',
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

    public function test_billing_owner_cannot_download_other_organization_invoice(): void
    {
        $invoice =
            $this->createOtherOrganizationInvoice(
                'PORTAL-OTHER-PDF'
            );

        $this
            ->actingAs($this->billingOwner)
            ->get(
                $this->downloadUrl($invoice)
            )
            ->assertForbidden();

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'organization.subscription_invoice_downloaded',

                'subject_id' =>
                    $invoice->id,
            ]
        );
    }

    public function test_non_billing_user_cannot_view_invoice(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'PORTAL-NON-OWNER-INVOICE',
        ]);

        $nonBillingUser = User::factory()->create([
            'organization_id' =>
                $this->organization->id,

            'is_active' =>
                true,
        ]);

        $this
            ->actingAs($nonBillingUser)
            ->get(
                $this->invoiceUrl($invoice)
            )
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_invoice_portal(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'PORTAL-GUEST-INVOICE',
        ]);

        $this->post('/logout');

        $this->assertGuest();

        $this
            ->get(
                $this->invoiceUrl($invoice)
            )
            ->assertRedirect('/login');

        $this
            ->get(
                $this->downloadUrl($invoice)
            )
            ->assertRedirect('/login');
    }

    private function portalUrl(): string
    {
        return '/subscription/billing';
    }

    private function invoiceUrl(
        SubscriptionInvoice $invoice
    ): string {
        return $this->portalUrl()
            .'/invoices/'
            .$invoice->id;
    }

    private function downloadUrl(
        SubscriptionInvoice $invoice
    ): string {
        return $this->invoiceUrl($invoice)
            .'/download';
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
                        'PORTAL-INVOICE-DEFAULT',

                    'status' =>
                        SubscriptionInvoiceStatus::ISSUED,

                    'issue_date' =>
                        now()->toDateString(),

                    'due_date' =>
                        now()
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

                    'issued_by_user_id' => null,
                    'paid_at' => null,
                    'voided_at' => null,
                    'cancelled_at' => null,
                ],
                $overrides
            )
        );
    }

    private function createOtherOrganizationInvoice(
        string $invoiceNumber
    ): SubscriptionInvoice {
        $organization = Organization::query()->create([
            'name' =>
                "Other {$invoiceNumber} Organization",

            'slug' =>
                Str::slug(
                    "other-{$invoiceNumber}-"
                    .Str::lower(Str::random(8))
                ),
        ]);

        $billingOwner = User::factory()->create([
            'organization_id' =>
                $organization->id,

            'is_active' =>
                true,
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
                    now()->subDay()->startOfMinute(),

                'current_period_ends_at' =>
                    now()->addMonth()->startOfMinute(),

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
                SubscriptionInvoiceStatus::ISSUED,

            'issue_date' =>
                now()->toDateString(),

            'due_date' =>
                now()
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

            'issued_by_user_id' => null,
            'paid_at' => null,
            'voided_at' => null,
            'cancelled_at' => null,
        ]);
    }
}
