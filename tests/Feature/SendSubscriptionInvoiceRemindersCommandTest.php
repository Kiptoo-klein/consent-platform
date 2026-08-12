<?php

namespace Tests\Feature;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionInvoiceStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Mail\SubscriptionInvoiceReminderMail;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionInvoiceNotification;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class SendSubscriptionInvoiceRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private OrganizationSubscription $subscription;

    private SubscriptionPlan $plan;

    private User $billingOwner;

    private int $invoiceSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            Carbon::create(
                2026,
                7,
                29,
                12,
                0,
                0,
                'UTC'
            )
        );

        config()->set(
            'subscription-invoice-notifications.before_due_days',
            [3, 1]
        );

        config()->set(
            'subscription-invoice-notifications.overdue_days',
            [1, 7]
        );

        config()->set(
            'subscription-invoice-notifications.automatic_retry_minutes',
            60
        );

        $this->seed(
            SubscriptionPlanSeeder::class
        );

        $this->plan = SubscriptionPlan::query()
            ->where('slug', 'basic')
            ->firstOrFail();

        $this->organization = Organization::query()->create([
            'name' =>
                'Invoice Reminder Clinic',

            'slug' =>
                'invoice-reminder-clinic',
        ]);

        $this->billingOwner = User::factory()->create([
            'organization_id' =>
                $this->organization->id,

            'name' =>
                'Billing Reminder Owner',

            'email' =>
                'billing-reminders@example.com',

            'is_active' =>
                true,
        ]);

        $this->subscription =
            OrganizationSubscription::query()->create([
                'organization_id' =>
                    $this->organization->id,

                'subscription_plan_id' =>
                    $this->plan->id,

                'billing_owner_user_id' =>
                    $this->billingOwner->id,

                'status' =>
                    OrganizationSubscriptionStatus::ACTIVE,

                'payment_status' =>
                    SubscriptionPaymentStatus::PAID,

                'starts_at' =>
                    now()->subMonth(),

                'trial_ends_at' => null,

                'current_period_starts_at' =>
                    now()->subMonth(),

                'current_period_ends_at' =>
                    now()->addMonth(),

                'cancelled_at' => null,
                'ends_at' => null,
            ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_command_sends_three_day_due_reminder(): void
    {
        Mail::fake();

        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-DUE-IN-THREE-DAYS',

            'due_date' =>
                now()
                    ->addDays(3)
                    ->toDateString(),
        ]);

        $this->artisan(
            'subscription-invoices:send-reminders'
        )
            ->expectsOutput(
                'Invoice reminder run complete. Sent: 1; failed: 0; skipped: 0.'
            )
            ->assertExitCode(0);

        Mail::assertSent(
            SubscriptionInvoiceReminderMail::class,
            function (
                SubscriptionInvoiceReminderMail $mail
            ) use ($invoice): bool {
                return $mail->hasTo(
                    $this->billingOwner->email
                )
                    && $mail->invoice->is($invoice)
                    && $mail->reminderKey
                        === SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS;
            }
        );

        $notification =
            SubscriptionInvoiceNotification::query()
                ->where(
                    'subscription_invoice_id',
                    $invoice->id
                )
                ->sole();

        $this->assertSame(
            $this->organization->id,
            $notification->organization_id
        );

        $this->assertSame(
            $this->billingOwner->id,
            $notification->recipient_user_id
        );

        $this->assertSame(
            SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS,
            $notification->reminder_key
        );

        $this->assertSame(
            SubscriptionInvoiceNotification::STATUS_SENT,
            $notification->status
        );

        $this->assertSame(
            $this->billingOwner->email,
            $notification->recipient_email
        );

        $this->assertNotNull(
            $notification->sent_at
        );

        $this->assertNull(
            $notification->failed_at
        );
    }

    public function test_command_sends_one_day_due_reminder(): void
    {
        Mail::fake();

        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-DUE-TOMORROW',

            'due_date' =>
                now()
                    ->addDay()
                    ->toDateString(),
        ]);

        $this->artisan(
            'subscription-invoices:send-reminders'
        )->assertExitCode(0);

        Mail::assertSent(
            SubscriptionInvoiceReminderMail::class,
            function (
                SubscriptionInvoiceReminderMail $mail
            ) use ($invoice): bool {
                return $mail->invoice->is($invoice)
                    && $mail->reminderKey
                        === SubscriptionInvoiceNotification::REMINDER_DUE_IN_1_DAY;
            }
        );

        $this->assertDatabaseHas(
            'subscription_invoice_notifications',
            [
                'subscription_invoice_id' =>
                    $invoice->id,

                'reminder_key' =>
                    SubscriptionInvoiceNotification::REMINDER_DUE_IN_1_DAY,

                'status' =>
                    SubscriptionInvoiceNotification::STATUS_SENT,
            ]
        );
    }

    public function test_command_sends_one_day_overdue_reminder(): void
    {
        Mail::fake();

        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-OVERDUE-ONE-DAY',

            'status' =>
                SubscriptionInvoiceStatus::OVERDUE,

            'due_date' =>
                now()
                    ->subDay()
                    ->toDateString(),
        ]);

        $this->artisan(
            'subscription-invoices:send-reminders'
        )->assertExitCode(0);

        Mail::assertSent(
            SubscriptionInvoiceReminderMail::class,
            function (
                SubscriptionInvoiceReminderMail $mail
            ) use ($invoice): bool {
                return $mail->invoice->is($invoice)
                    && $mail->reminderKey
                        === SubscriptionInvoiceNotification::REMINDER_OVERDUE_1_DAY;
            }
        );

        $this->assertDatabaseHas(
            'subscription_invoice_notifications',
            [
                'subscription_invoice_id' =>
                    $invoice->id,

                'reminder_key' =>
                    SubscriptionInvoiceNotification::REMINDER_OVERDUE_1_DAY,

                'status' =>
                    SubscriptionInvoiceNotification::STATUS_SENT,
            ]
        );
    }

    public function test_command_sends_seven_day_overdue_reminder(): void
    {
        Mail::fake();

        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-OVERDUE-SEVEN-DAYS',

            'status' =>
                SubscriptionInvoiceStatus::OVERDUE,

            'due_date' =>
                now()
                    ->subDays(7)
                    ->toDateString(),
        ]);

        $this->artisan(
            'subscription-invoices:send-reminders'
        )->assertExitCode(0);

        Mail::assertSent(
            SubscriptionInvoiceReminderMail::class,
            function (
                SubscriptionInvoiceReminderMail $mail
            ) use ($invoice): bool {
                return $mail->invoice->is($invoice)
                    && $mail->reminderKey
                        === SubscriptionInvoiceNotification::REMINDER_OVERDUE_7_DAYS;
            }
        );

        $this->assertDatabaseHas(
            'subscription_invoice_notifications',
            [
                'subscription_invoice_id' =>
                    $invoice->id,

                'reminder_key' =>
                    SubscriptionInvoiceNotification::REMINDER_OVERDUE_7_DAYS,

                'status' =>
                    SubscriptionInvoiceNotification::STATUS_SENT,
            ]
        );
    }

    public function test_non_milestone_and_terminal_invoices_do_not_send(): void
    {
        Mail::fake();

        $this->createInvoice([
            'invoice_number' =>
                'INV-DUE-IN-TWO-DAYS',

            'due_date' =>
                now()
                    ->addDays(2)
                    ->toDateString(),
        ]);

        foreach (
            [
                SubscriptionInvoiceStatus::DRAFT,
                SubscriptionInvoiceStatus::PAID,
                SubscriptionInvoiceStatus::VOIDED,
                SubscriptionInvoiceStatus::CANCELLED,
            ] as $status
        ) {
            $this->createInvoice([
                'status' => $status,

                'issue_date' =>
                    $status === SubscriptionInvoiceStatus::DRAFT
                        ? null
                        : now()
                            ->subMonth()
                            ->toDateString(),

                'due_date' =>
                    now()
                        ->subDay()
                        ->toDateString(),

                'paid_at' =>
                    $status === SubscriptionInvoiceStatus::PAID
                        ? now()
                        : null,

                'voided_at' =>
                    $status === SubscriptionInvoiceStatus::VOIDED
                        ? now()
                        : null,

                'cancelled_at' =>
                    $status === SubscriptionInvoiceStatus::CANCELLED
                        ? now()
                        : null,
            ]);
        }

        $this->artisan(
            'subscription-invoices:send-reminders'
        )->assertExitCode(0);

        Mail::assertNothingSent();

        $this->assertDatabaseCount(
            'subscription_invoice_notifications',
            0
        );
    }

    public function test_only_active_valid_billing_owner_receives_reminders(): void
    {
        Mail::fake();

        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-OWNER-ELIGIBILITY',
        ]);

        $this->billingOwner->update([
            'is_active' => false,
        ]);

        $this->artisan(
            'subscription-invoices:send-reminders'
        )->assertExitCode(0);

        Mail::assertNothingSent();

        $this->billingOwner->update([
            'is_active' => true,
            'email' => 'not-an-email',
        ]);

        $this->artisan(
            'subscription-invoices:send-reminders'
        )->assertExitCode(0);

        Mail::assertNothingSent();

        $this->billingOwner->update([
            'email' =>
                'valid-billing-owner@example.com',
        ]);

        $this->artisan(
            'subscription-invoices:send-reminders'
        )->assertExitCode(0);

        Mail::assertSent(
            SubscriptionInvoiceReminderMail::class,
            function (
                SubscriptionInvoiceReminderMail $mail
            ) use ($invoice): bool {
                return $mail->hasTo(
                    'valid-billing-owner@example.com'
                )
                    && $mail->invoice->is($invoice);
            }
        );

        $this->assertDatabaseCount(
            'subscription_invoice_notifications',
            1
        );
    }

    public function test_successful_reminder_is_idempotent_per_milestone(): void
    {
        Mail::fake();

        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-REMINDER-IDEMPOTENT',
        ]);

        $this->artisan(
            'subscription-invoices:send-reminders'
        )
            ->expectsOutput(
                'Invoice reminder run complete. Sent: 1; failed: 0; skipped: 0.'
            )
            ->assertExitCode(0);

        $this->artisan(
            'subscription-invoices:send-reminders'
        )
            ->expectsOutput(
                'Invoice reminder run complete. Sent: 0; failed: 0; skipped: 1.'
            )
            ->assertExitCode(0);

        Mail::assertSentCount(1);

        $this->assertSame(
            1,
            SubscriptionInvoiceNotification::query()
                ->where(
                    'subscription_invoice_id',
                    $invoice->id
                )
                ->where(
                    'reminder_key',
                    SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS
                )
                ->count()
        );
    }

    public function test_recent_failed_attempt_retries_after_cooldown(): void
    {
        Mail::fake();

        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-REMINDER-RETRY',
        ]);

        $this->createFailedNotification(
            $invoice,
            SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS
        );

        $this->artisan(
            'subscription-invoices:send-reminders'
        )
            ->expectsOutput(
                'Invoice reminder run complete. Sent: 0; failed: 0; skipped: 1.'
            )
            ->assertExitCode(0);

        Mail::assertNothingSent();

        Carbon::setTestNow(
            now()
                ->copy()
                ->addMinutes(61)
        );

        $this->artisan(
            'subscription-invoices:send-reminders'
        )
            ->expectsOutput(
                'Invoice reminder run complete. Sent: 1; failed: 0; skipped: 0.'
            )
            ->assertExitCode(0);

        Mail::assertSentCount(1);

        $this->assertSame(
            2,
            SubscriptionInvoiceNotification::query()
                ->where(
                    'subscription_invoice_id',
                    $invoice->id
                )
                ->where(
                    'reminder_key',
                    SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS
                )
                ->count()
        );
    }

    public function test_delivery_failure_is_recorded_and_command_fails(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-REMINDER-FAILURE',
        ]);

        Mail::shouldReceive('to')
            ->once()
            ->with($this->billingOwner->email)
            ->andThrow(
                new RuntimeException(
                    'Simulated invoice reminder delivery failure.'
                )
            );

        $this->artisan(
            'subscription-invoices:send-reminders'
        )
            ->expectsOutput(
                'Invoice reminder run complete. Sent: 0; failed: 1; skipped: 0.'
            )
            ->assertExitCode(1);

        $notification =
            SubscriptionInvoiceNotification::query()
                ->where(
                    'subscription_invoice_id',
                    $invoice->id
                )
                ->sole();

        $this->assertSame(
            SubscriptionInvoiceNotification::STATUS_FAILED,
            $notification->status
        );

        $this->assertNotNull(
            $notification->failed_at
        );

        $this->assertNull(
            $notification->sent_at
        );

        $this->assertSame(
            'Simulated invoice reminder delivery failure.',
            $notification->error_message
        );
    }

    public function test_dry_run_does_not_send_or_persist(): void
    {
        Mail::fake();

        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-REMINDER-DRY-RUN',
        ]);

        $this->artisan(
            'subscription-invoices:send-reminders',
            [
                '--dry-run' => true,
            ]
        )
            ->expectsOutput(
                "Due: invoice #{$invoice->id} - due_in_3_days reminder to {$this->billingOwner->email}"
            )
            ->expectsOutput(
                'Invoice reminder run complete. Sent: 0; failed: 0; skipped: 1.'
            )
            ->assertExitCode(0);

        Mail::assertNothingSent();

        $this->assertDatabaseCount(
            'subscription_invoice_notifications',
            0
        );
    }

    public function test_mailable_contains_invoice_details_and_portal_link(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-REMINDER-CONTENT',

            'subtotal' =>
                '1000.00',

            'tax_amount' =>
                '160.00',

            'total_amount' =>
                '1160.00',

            'currency' =>
                'USD',
        ]);

        $invoice->load([
            'organization',
            'subscription.billingOwner',
            'plan',
        ]);

        $mail = new SubscriptionInvoiceReminderMail(
            invoice:
                $invoice,

            reminderKey:
                SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS
        );

        $html = $mail->render();

        $this->assertStringContainsString(
            'INV-REMINDER-CONTENT',
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

        $this->assertStringContainsString(
            'USD 1,160.00',
            $html
        );

        $this->assertStringContainsString(
            route(
                'organization-billing.invoices.show',
                $invoice
            ),
            $html
        );

        $this->assertStringContainsString(
            'INV-REMINDER-CONTENT',
            $mail->envelope()->subject
        );
    }

    public function test_invoice_reminder_command_is_scheduled_hourly(): void
    {
        $event = collect(
            app(Schedule::class)->events()
        )->first(
            fn ($scheduledEvent): bool =>
                str_contains(
                    $scheduledEvent->command,
                    'subscription-invoices:send-reminders'
                )
        );

        $this->assertNotNull(
            $event,
            'The invoice reminder command is not scheduled.'
        );

        $this->assertSame(
            '0 * * * *',
            $event->expression
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createInvoice(
        array $overrides = []
    ): SubscriptionInvoice {
        $this->invoiceSequence++;

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
                        'INV-REMINDER-'
                        .str_pad(
                            (string) $this->invoiceSequence,
                            4,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'status' =>
                        SubscriptionInvoiceStatus::ISSUED,

                    'issue_date' =>
                        now()
                            ->subMonth()
                            ->toDateString(),

                    'due_date' =>
                        now()
                            ->addDays(3)
                            ->toDateString(),

                    'subtotal' =>
                        '100.00',

                    'tax_amount' =>
                        '16.00',

                    'total_amount' =>
                        '116.00',

                    'currency' =>
                        'USD',

                    'notes' =>
                        'Subscription invoice reminder test.',

                    'issued_by_user_id' => null,
                    'paid_at' => null,
                    'voided_at' => null,
                    'cancelled_at' => null,
                ],
                $overrides
            )
        );
    }

    private function createFailedNotification(
        SubscriptionInvoice $invoice,
        string $reminderKey
    ): SubscriptionInvoiceNotification {
        return SubscriptionInvoiceNotification::query()->create([
            'organization_id' =>
                $this->organization->id,

            'subscription_invoice_id' =>
                $invoice->id,

            'recipient_user_id' =>
                $this->billingOwner->id,

            'reminder_key' =>
                $reminderKey,

            'status' =>
                SubscriptionInvoiceNotification::STATUS_FAILED,

            'recipient_email' =>
                $this->billingOwner->email,

            'subject' =>
                'Previous failed reminder',

            'message' =>
                'Previous delivery attempt failed.',

            'scheduled_for' =>
                now(),

            'sent_at' => null,

            'failed_at' =>
                now(),

            'error_message' =>
                'Previous simulated failure.',

            'metadata' => [
                'invoice_number' =>
                    $invoice->invoice_number,
            ],
        ]);
    }
}
