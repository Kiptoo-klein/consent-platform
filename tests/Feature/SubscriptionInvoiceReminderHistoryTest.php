<?php

namespace Tests\Feature;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionInvoiceStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\PlatformRole;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionInvoiceNotification;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionInvoiceReminderHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $billingOwner;

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
                'Reminder History Clinic',

            'name' =>
                'Reminder History Administrator',

            'email' =>
                'reminder-history-admin@example.com',

            'password' =>
                'StrongPass1!',

            'password_confirmation' =>
                'StrongPass1!',
        ]);

        $this->verifyAuthenticatedUser();

        $this->organization = Organization::query()
            ->where(
                'name',
                'Reminder History Clinic'
            )
            ->firstOrFail();

        $this->billingOwner = User::query()
            ->where(
                'email',
                'reminder-history-admin@example.com'
            )
            ->firstOrFail();

        $this->subscription = $this->organization
            ->subscription()
            ->firstOrFail();

        $this->plan = $this->subscription
            ->plan()
            ->firstOrFail();

        $superAdminRole = PlatformRole::query()
            ->where(
                'slug',
                'super-admin'
            )
            ->firstOrFail();

        $this->platformAdmin =
            User::factory()->create([
                'organization_id' => null,

                'platform_role_id' =>
                    $superAdminRole->id,

                'is_active' => true,
            ]);

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

    public function test_billing_owner_sees_scoped_reminder_history_without_failure_details(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'REMINDER-HISTORY-PORTAL',
        ]);

        $oldest = $this->createNotification(
            $invoice,
            [
                'reminder_key' =>
                    SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS,

                'status' =>
                    SubscriptionInvoiceNotification::STATUS_SENT,

                'recipient_email' =>
                    'oldest-reminder@example.com',

                'created_at' =>
                    now()->subMinutes(3),
            ]
        );

        $middle = $this->createNotification(
            $invoice,
            [
                'reminder_key' =>
                    SubscriptionInvoiceNotification::REMINDER_DUE_IN_1_DAY,

                'status' =>
                    SubscriptionInvoiceNotification::STATUS_PROCESSING,

                'recipient_email' =>
                    'processing-reminder@example.com',

                'created_at' =>
                    now()->subMinutes(2),
            ]
        );

        $newest = $this->createNotification(
            $invoice,
            [
                'reminder_key' =>
                    SubscriptionInvoiceNotification::REMINDER_OVERDUE_1_DAY,

                'status' =>
                    SubscriptionInvoiceNotification::STATUS_FAILED,

                'recipient_email' =>
                    'failed-reminder@example.com',

                'error_message' =>
                    'SMTP credential rejected: secret diagnostic.',

                'created_at' =>
                    now()->subMinute(),
            ]
        );

        $otherInvoice = $this->createInvoice([
            'invoice_number' =>
                'REMINDER-HISTORY-OTHER-INVOICE',
        ]);

        $this->createNotification(
            $otherInvoice,
            [
                'recipient_email' =>
                    'hidden-other-invoice@example.com',
            ]
        );

        [
            $foreignInvoice,
            $foreignOwner,
        ] = $this->createOtherOrganizationInvoice();

        $this->createNotification(
            $foreignInvoice,
            [
                'recipient_user_id' =>
                    $foreignOwner->id,

                'recipient_email' =>
                    'hidden-other-organization@example.com',
            ]
        );

        $response = $this
            ->actingAs($this->billingOwner)
            ->get(
                $this->organizationInvoiceUrl(
                    $invoice
                )
            );

        $response->assertOk();

        $response->assertSeeText(
            'Reminder History'
        );

        $response->assertSeeText(
            'Due in 3 days'
        );

        $response->assertSeeText(
            'Due in 1 day'
        );

        $response->assertSeeText(
            '1 day overdue'
        );

        $response->assertSeeText('Sent');
        $response->assertSeeText('Processing');
        $response->assertSeeText('Failed');

        $response->assertSeeText(
            $oldest->recipient_email
        );

        $response->assertSeeText(
            $middle->recipient_email
        );

        $response->assertSeeText(
            $newest->recipient_email
        );

        $response->assertSeeInOrder([
            $newest->recipient_email,
            $middle->recipient_email,
            $oldest->recipient_email,
        ]);

        $response->assertSeeText(
            $newest
                ->created_at
                ->copy()
                ->timezone(
                    config('app.display_timezone')
                )
                ->format(
                    'M d, Y H:i'
                )
        );

        $response->assertDontSeeText(
            'SMTP credential rejected: secret diagnostic.'
        );

        $response->assertDontSeeText(
            'hidden-other-invoice@example.com'
        );

        $response->assertDontSeeText(
            'hidden-other-organization@example.com'
        );
    }

    public function test_billing_owner_sees_empty_reminder_history_state(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'REMINDER-HISTORY-EMPTY-PORTAL',
        ]);

        $this
            ->actingAs($this->billingOwner)
            ->get(
                $this->organizationInvoiceUrl(
                    $invoice
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Reminder History'
            )
            ->assertSeeText(
                'No reminder attempts have been recorded for this invoice.'
            );
    }

    public function test_billing_owner_history_is_limited_to_latest_twenty_attempts(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'REMINDER-HISTORY-LIMIT',
        ]);

        foreach (range(1, 21) as $number) {
            $this->createNotification(
                $invoice,
                [
                    'recipient_email' =>
                        sprintf(
                            'history-%02d@example.com',
                            $number
                        ),

                    'created_at' =>
                        now()->subMinutes(
                            22 - $number
                        ),
                ]
            );
        }

        $response = $this
            ->actingAs($this->billingOwner)
            ->get(
                $this->organizationInvoiceUrl(
                    $invoice
                )
            );

        $response->assertOk();

        $response->assertDontSeeText(
            'history-01@example.com'
        );

        $response->assertSeeText(
            'history-02@example.com'
        );

        $response->assertSeeText(
            'history-21@example.com'
        );

        $response->assertSeeInOrder([
            'history-21@example.com',
            'history-02@example.com',
        ]);
    }

    public function test_platform_admin_sees_reminder_failure_details(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'REMINDER-HISTORY-PLATFORM',
        ]);

        $notification = $this->createNotification(
            $invoice,
            [
                'reminder_key' =>
                    SubscriptionInvoiceNotification::REMINDER_OVERDUE_7_DAYS,

                'status' =>
                    SubscriptionInvoiceNotification::STATUS_FAILED,

                'recipient_email' =>
                    'platform-failure@example.com',

                'error_message' =>
                    'Transport connection timed out after 30 seconds.',
            ]
        );

        $response = $this
            ->actingAs($this->platformAdmin)
            ->get(
                $this->platformInvoiceUrl(
                    $invoice
                )
            );

        $response->assertOk();

        $response->assertSeeText(
            'Reminder History'
        );

        $response->assertSeeText(
            '7 days overdue'
        );

        $response->assertSeeText('Failed');

        $response->assertSeeText(
            $notification->recipient_email
        );

        $response->assertSeeText(
            'Transport connection timed out after 30 seconds.'
        );
    }

    public function test_platform_invoice_without_reminders_shows_empty_state(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'REMINDER-HISTORY-EMPTY-PLATFORM',
        ]);

        $this
            ->actingAs($this->platformAdmin)
            ->get(
                $this->platformInvoiceUrl(
                    $invoice
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Reminder History'
            )
            ->assertSeeText(
                'No reminder attempts have been recorded for this invoice.'
            );
    }

    private function organizationInvoiceUrl(
        SubscriptionInvoice $invoice
    ): string {
        return route(
            'organization-billing.invoices.show',
            $invoice
        );
    }

    private function platformInvoiceUrl(
        SubscriptionInvoice $invoice
    ): string {
        return route(
            'platform.organizations.subscription-invoices.show',
            [
                $this->organization,
                $invoice,
            ]
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
                        'REMINDER-HISTORY-INVOICE',

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
                        '16.00',

                    'total_amount' =>
                        '116.00',

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

    /**
     * @param array<string, mixed> $overrides
     */
    private function createNotification(
        SubscriptionInvoice $invoice,
        array $overrides = []
    ): SubscriptionInvoiceNotification {
        $createdAt = Carbon::parse(
            $overrides['created_at']
                ?? now()
        );

        unset($overrides['created_at']);

        $status = $overrides['status']
            ?? SubscriptionInvoiceNotification::STATUS_SENT;

        $notification =
            SubscriptionInvoiceNotification::query()
                ->create(
                    array_merge(
                        [
                            'organization_id' =>
                                $invoice->organization_id,

                            'subscription_invoice_id' =>
                                $invoice->id,

                            'recipient_user_id' =>
                                $this->billingOwner->id,

                            'reminder_key' =>
                                SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS,

                            'status' =>
                                $status,

                            'recipient_email' =>
                                $this->billingOwner->email,

                            'subject' =>
                                'Subscription invoice reminder',

                            'message' =>
                                'A subscription invoice reminder was attempted.',

                            'scheduled_for' =>
                                $createdAt,

                            'sent_at' =>
                                $status
                                    === SubscriptionInvoiceNotification::STATUS_SENT
                                        ? $createdAt
                                        : null,

                            'failed_at' =>
                                $status
                                    === SubscriptionInvoiceNotification::STATUS_FAILED
                                        ? $createdAt
                                        : null,

                            'error_message' => null,

                            'metadata' => [
                                'invoice_number' =>
                                    $invoice->invoice_number,
                            ],
                        ],
                        $overrides
                    )
                );

        $notification->forceFill([
            'created_at' =>
                $createdAt,

            'updated_at' =>
                $createdAt,
        ])->saveQuietly();

        return $notification->fresh();
    }

    /**
     * @return array{SubscriptionInvoice, User}
     */
    private function createOtherOrganizationInvoice(): array
    {
        $organization = Organization::query()->create([
            'name' =>
                'Other Reminder History Clinic',

            'slug' =>
                'other-reminder-history-'
                .Str::lower(Str::random(8)),
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

        $invoice = SubscriptionInvoice::query()->create([
            'organization_subscription_id' =>
                $subscription->id,

            'organization_id' =>
                $organization->id,

            'subscription_plan_id' =>
                $this->plan->id,

            'invoice_number' =>
                'REMINDER-HISTORY-FOREIGN',

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

        return [
            $invoice,
            $billingOwner,
        ];
    }
}
