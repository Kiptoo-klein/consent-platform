<?php

namespace Tests\Feature;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionInvoiceStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Mail\SubscriptionInvoiceReminderMail;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\PlatformRole;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionInvoiceNotification;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\ActivityLogger;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class SubscriptionInvoiceReminderRetryTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $billingOwner;

    private User $platformAdmin;

    private OrganizationSubscription $subscription;

    private SubscriptionPlan $plan;

    private int $invoiceSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            '2026-07-29 12:00:00'
        );

        config()->set(
            'subscription-invoice-notifications.manual_retry_minutes',
            5
        );

        $this->seed([
            PlatformRoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        $this->post('/register', [
            'organization_name' =>
                'Reminder Retry Clinic',

            'name' =>
                'Reminder Retry Administrator',

            'email' =>
                'reminder-retry-admin@example.com',

            'password' =>
                'StrongPass1!',

            'password_confirmation' =>
                'StrongPass1!',
        ]);

        $this->verifyAuthenticatedUser();

        $this->organization =
            Organization::query()
                ->where(
                    'name',
                    'Reminder Retry Clinic'
                )
                ->firstOrFail();

        $this->billingOwner =
            User::query()
                ->where(
                    'email',
                    'reminder-retry-admin@example.com'
                )
                ->firstOrFail();

        $this->subscription =
            $this->organization
                ->subscription()
                ->firstOrFail();

        $this->plan =
            $this->subscription
                ->plan()
                ->firstOrFail();

        $superAdminRole =
            PlatformRole::query()
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
                now()
                    ->subDay()
                    ->startOfMinute(),

            'current_period_ends_at' =>
                now()
                    ->addMonth()
                    ->startOfMinute(),

            'cancelled_at' => null,
            'ends_at' => null,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_platform_invoice_shows_retry_only_for_failed_attempts(): void
    {
        $invoice = $this->createInvoice();

        $failed = $this->createNotification(
            $invoice,
            [
                'status' =>
                    SubscriptionInvoiceNotification::STATUS_FAILED,
            ]
        );

        $sent = $this->createNotification(
            $invoice,
            [
                'status' =>
                    SubscriptionInvoiceNotification::STATUS_SENT,

                'created_at' =>
                    now()->subMinute(),
            ]
        );

        $processing = $this->createNotification(
            $invoice,
            [
                'status' =>
                    SubscriptionInvoiceNotification::STATUS_PROCESSING,

                'created_at' =>
                    now()->subMinutes(2),
            ]
        );

        $response = $this
            ->actingAs($this->platformAdmin)
            ->get(
                $this->platformInvoiceUrl(
                    $invoice
                )
            );

        $response
            ->assertOk()
            ->assertSeeText(
                'Retry reminder'
            )
            ->assertSee(
                $this->retryUrl(
                    $invoice,
                    $failed
                ),
                false
            )
            ->assertDontSee(
                $this->retryUrl(
                    $invoice,
                    $sent
                ),
                false
            )
            ->assertDontSee(
                $this->retryUrl(
                    $invoice,
                    $processing
                ),
                false
            );
    }

    public function test_organization_invoice_never_shows_retry_controls(): void
    {
        $invoice = $this->createInvoice();

        $failed = $this->createNotification(
            $invoice,
            [
                'status' =>
                    SubscriptionInvoiceNotification::STATUS_FAILED,
            ]
        );

        $this
            ->actingAs($this->billingOwner)
            ->get(
                $this->organizationInvoiceUrl(
                    $invoice
                )
            )
            ->assertOk()
            ->assertDontSeeText(
                'Retry reminder'
            )
            ->assertDontSee(
                $this->retryUrl(
                    $invoice,
                    $failed
                ),
                false
            );
    }

    public function test_platform_admin_can_retry_failed_reminder_successfully(): void
    {
        Mail::fake();

        $invoice = $this->createInvoice();

        $failed = $this->createNotification(
            $invoice,
            [
                'status' =>
                    SubscriptionInvoiceNotification::STATUS_FAILED,

                'error_message' =>
                    'Original delivery failure.',
            ]
        );

        $originalStatus =
            $failed->status;

        $originalError =
            $failed->error_message;

        $originalFailedAt =
            $failed->failed_at
                ?->toDateTimeString();

        $originalUpdatedAt =
            $failed->updated_at
                ?->toDateTimeString();

        $response = $this
            ->actingAs($this->platformAdmin)
            ->post(
                $this->retryUrl(
                    $invoice,
                    $failed
                )
            );

        $response
            ->assertRedirect(
                $this->platformInvoiceUrl(
                    $invoice
                )
            )
            ->assertSessionHas(
                'success',
                'Subscription invoice reminder sent successfully.'
            );

        Mail::assertSent(
            SubscriptionInvoiceReminderMail::class,
            function (
                SubscriptionInvoiceReminderMail $mail
            ) use (
                $invoice,
                $failed
            ): bool {
                return $mail->hasTo(
                    $this->billingOwner->email
                )
                    && $mail->invoice->is(
                        $invoice
                    )
                    && $mail->reminderKey
                        === $failed->reminder_key;
            }
        );

        $retry =
            SubscriptionInvoiceNotification::query()
                ->where(
                    'retry_of_notification_id',
                    $failed->id
                )
                ->sole();

        $this->assertSame(
            SubscriptionInvoiceNotification::STATUS_SENT,
            $retry->status
        );

        $this->assertSame(
            $this->platformAdmin->id,
            $retry->retry_requested_by_user_id
        );

        $this->assertSame(
            $failed->reminder_key,
            $retry->reminder_key
        );

        $this->assertSame(
            $this->billingOwner->email,
            $retry->recipient_email
        );

        $this->assertNotNull(
            $retry->sent_at
        );

        $this->assertNull(
            $retry->failed_at
        );

        $this->assertNull(
            $retry->error_message
        );

        $this->assertSame(
            2,
            SubscriptionInvoiceNotification::query()
                ->where(
                    'subscription_invoice_id',
                    $invoice->id
                )
                ->count()
        );

        $failed->refresh();

        $this->assertSame(
            $originalStatus,
            $failed->status
        );

        $this->assertSame(
            $originalError,
            $failed->error_message
        );

        $this->assertSame(
            $originalFailedAt,
            $failed->failed_at
                ?->toDateTimeString()
        );

        $this->assertSame(
            $originalUpdatedAt,
            $failed->updated_at
                ?->toDateTimeString()
        );

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_invoice_reminder_retried'
            )
            ->sole();

        $this->assertSame(
            $this->organization->id,
            $activity->organization_id
        );

        $this->assertSame(
            $this->platformAdmin->id,
            $activity->user_id
        );

        $this->assertSame(
            $retry->getMorphClass(),
            $activity->subject_type
        );

        $this->assertSame(
            $retry->id,
            $activity->subject_id
        );

        $this->assertSame(
            $failed->id,
            data_get(
                $activity->properties,
                'original_notification_id'
            )
        );

        $this->assertSame(
            $retry->id,
            data_get(
                $activity->properties,
                'retry_notification_id'
            )
        );

        $this->assertSame(
            $invoice->invoice_number,
            data_get(
                $activity->properties,
                'invoice_number'
            )
        );
    }

    public function test_failed_retry_creates_new_failed_attempt(): void
    {
        $invoice = $this->createInvoice();

        $failed = $this->createNotification(
            $invoice,
            [
                'status' =>
                    SubscriptionInvoiceNotification::STATUS_FAILED,
            ]
        );

        Mail::shouldReceive('to')
            ->once()
            ->with(
                $this->billingOwner->email
            )
            ->andThrow(
                new RuntimeException(
                    'Simulated manual retry delivery failure.'
                )
            );

        $response = $this
            ->actingAs($this->platformAdmin)
            ->post(
                $this->retryUrl(
                    $invoice,
                    $failed
                )
            );

        $response
            ->assertRedirect(
                $this->platformInvoiceUrl(
                    $invoice
                )
            )
            ->assertSessionHasErrors(
                'reminder'
            );

        $retry =
            SubscriptionInvoiceNotification::query()
                ->where(
                    'retry_of_notification_id',
                    $failed->id
                )
                ->sole();

        $this->assertSame(
            SubscriptionInvoiceNotification::STATUS_FAILED,
            $retry->status
        );

        $this->assertNull(
            $retry->sent_at
        );

        $this->assertNotNull(
            $retry->failed_at
        );

        $this->assertSame(
            'Simulated manual retry delivery failure.',
            $retry->error_message
        );

        $failed->refresh();

        $this->assertSame(
            SubscriptionInvoiceNotification::STATUS_FAILED,
            $failed->status
        );

        $this->assertSame(
            2,
            SubscriptionInvoiceNotification::query()
                ->where(
                    'subscription_invoice_id',
                    $invoice->id
                )
                ->count()
        );
    }

    public function test_sent_attempt_cannot_be_retried(): void
    {
        Mail::fake();

        $invoice = $this->createInvoice();

        $sent = $this->createNotification(
            $invoice,
            [
                'status' =>
                    SubscriptionInvoiceNotification::STATUS_SENT,
            ]
        );

        $this
            ->actingAs($this->platformAdmin)
            ->post(
                $this->retryUrl(
                    $invoice,
                    $sent
                )
            )
            ->assertStatus(422);

        Mail::assertNothingSent();

        $this->assertDatabaseCount(
            'subscription_invoice_notifications',
            1
        );
    }

    public function test_processing_attempt_cannot_be_retried(): void
    {
        Mail::fake();

        $invoice = $this->createInvoice();

        $processing =
            $this->createNotification(
                $invoice,
                [
                    'status' =>
                        SubscriptionInvoiceNotification::STATUS_PROCESSING,
                ]
            );

        $this
            ->actingAs($this->platformAdmin)
            ->post(
                $this->retryUrl(
                    $invoice,
                    $processing
                )
            )
            ->assertStatus(422);

        Mail::assertNothingSent();

        $this->assertDatabaseCount(
            'subscription_invoice_notifications',
            1
        );
    }

    public function test_organization_user_cannot_retry_reminder(): void
    {
        Mail::fake();

        $invoice = $this->createInvoice();

        $failed = $this->createNotification(
            $invoice,
            [
                'status' =>
                    SubscriptionInvoiceNotification::STATUS_FAILED,
            ]
        );

        $this
            ->actingAs($this->billingOwner)
            ->post(
                $this->retryUrl(
                    $invoice,
                    $failed
                )
            )
            ->assertForbidden();

        Mail::assertNothingSent();

        $this->assertDatabaseCount(
            'subscription_invoice_notifications',
            1
        );
    }

    public function test_cross_organization_notification_cannot_be_retried(): void
    {
        Mail::fake();

        $invoice = $this->createInvoice();

        [
            $otherOrganization,
            $otherInvoice,
            $foreignNotification,
        ] = $this->createOtherOrganizationReminder();

        $this
            ->actingAs($this->platformAdmin)
            ->post(
                $this->retryUrl(
                    $invoice,
                    $foreignNotification
                )
            )
            ->assertNotFound();

        Mail::assertNothingSent();

        $this->assertSame(
            1,
            SubscriptionInvoiceNotification::query()
                ->where(
                    'subscription_invoice_id',
                    $otherInvoice->id
                )
                ->count()
        );

        $this->assertNotSame(
            $this->organization->id,
            $otherOrganization->id
        );
    }

    public function test_notification_from_another_invoice_cannot_be_retried(): void
    {
        Mail::fake();

        $invoice = $this->createInvoice([
            'invoice_number' =>
                'RETRY-TARGET-INVOICE',
        ]);

        $otherInvoice = $this->createInvoice([
            'invoice_number' =>
                'RETRY-OTHER-INVOICE',
        ]);

        $otherNotification =
            $this->createNotification(
                $otherInvoice,
                [
                    'status' =>
                        SubscriptionInvoiceNotification::STATUS_FAILED,
                ]
            );

        $this
            ->actingAs($this->platformAdmin)
            ->post(
                $this->retryUrl(
                    $invoice,
                    $otherNotification
                )
            )
            ->assertNotFound();

        Mail::assertNothingSent();

        $this->assertDatabaseCount(
            'subscription_invoice_notifications',
            1
        );
    }

    public function test_recent_retry_attempt_blocks_duplicate_retry(): void
    {
        Mail::fake();

        $invoice = $this->createInvoice();

        $failed = $this->createNotification(
            $invoice,
            [
                'status' =>
                    SubscriptionInvoiceNotification::STATUS_FAILED,

                'created_at' =>
                    now()->subMinutes(2),
            ]
        );

        $this->createNotification(
            $invoice,
            [
                'status' =>
                    SubscriptionInvoiceNotification::STATUS_PROCESSING,

                'created_at' =>
                    now()->subMinute(),

                'retry_of_notification_id' =>
                    $failed->id,

                'retry_requested_by_user_id' =>
                    $this->platformAdmin->id,
            ]
        );

        $this
            ->actingAs($this->platformAdmin)
            ->post(
                $this->retryUrl(
                    $invoice,
                    $failed
                )
            )
            ->assertStatus(422);

        Mail::assertNothingSent();

        $this->assertSame(
            2,
            SubscriptionInvoiceNotification::query()
                ->where(
                    'subscription_invoice_id',
                    $invoice->id
                )
                ->count()
        );
    }

    public function test_failed_retry_cannot_bypass_cooldown_through_retry_chain(): void
    {
        Mail::fake();

        $invoice = $this->createInvoice();

        $originalFailure =
            $this->createNotification(
                $invoice,
                [
                    'status' =>
                        SubscriptionInvoiceNotification::STATUS_FAILED,

                    'created_at' =>
                        now()->subMinutes(3),
                ]
            );

        $failedRetry =
            $this->createNotification(
                $invoice,
                [
                    'status' =>
                        SubscriptionInvoiceNotification::STATUS_FAILED,

                    'created_at' =>
                        now()->subMinute(),

                    'retry_of_notification_id' =>
                        $originalFailure->id,

                    'retry_requested_by_user_id' =>
                        $this->platformAdmin->id,
                ]
            );

        $this
            ->actingAs($this->platformAdmin)
            ->post(
                $this->retryUrl(
                    $invoice,
                    $failedRetry
                )
            )
            ->assertStatus(422);

        Mail::assertNothingSent();

        $this->assertSame(
            2,
            SubscriptionInvoiceNotification::query()
                ->where(
                    'subscription_invoice_id',
                    $invoice->id
                )
                ->count()
        );
    }

    public function test_audit_failure_rolls_back_retry_before_email_delivery(): void
    {
        Mail::fake();

        $invoice = $this->createInvoice();

        $failed = $this->createNotification(
            $invoice,
            [
                'status' =>
                    SubscriptionInvoiceNotification::STATUS_FAILED,
            ]
        );

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
                            'Simulated reminder retry audit failure.'
                        )
                    );
            }
        );

        $this->withoutExceptionHandling();

        try {
            $this
                ->actingAs($this->platformAdmin)
                ->post(
                    $this->retryUrl(
                        $invoice,
                        $failed
                    )
                );

            $this->fail(
                'The expected audit exception was not thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated reminder retry audit failure.',
                $exception->getMessage()
            );
        }

        Mail::assertNothingSent();

        $this->assertDatabaseCount(
            'subscription_invoice_notifications',
            1
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'organization.subscription_invoice_reminder_retried',
            ]
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

    private function organizationInvoiceUrl(
        SubscriptionInvoice $invoice
    ): string {
        return route(
            'organization-billing.invoices.show',
            $invoice
        );
    }

    private function retryUrl(
        SubscriptionInvoice $invoice,
        SubscriptionInvoiceNotification $notification
    ): string {
        return "/platform/organizations/"
            ."{$this->organization->id}"
            ."/subscription-invoices/"
            ."{$invoice->id}"
            ."/reminder-notifications/"
            ."{$notification->id}"
            ."/retry";
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
                        'REMINDER-RETRY-'
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
                        'Manual reminder retry test.',

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

        unset(
            $overrides['created_at']
        );

        $retryFields = [];

        foreach (
            [
                'retry_of_notification_id',
                'retry_requested_by_user_id',
            ]
            as $field
        ) {
            if (
                array_key_exists(
                    $field,
                    $overrides
                )
            ) {
                $retryFields[$field] =
                    $overrides[$field];

                unset(
                    $overrides[$field]
                );
            }
        }

        $status =
            $overrides['status']
            ?? SubscriptionInvoiceNotification::STATUS_FAILED;

        $errorMessage =
            array_key_exists(
                'error_message',
                $overrides
            )
                ? $overrides['error_message']
                : (
                    $status
                        === SubscriptionInvoiceNotification::STATUS_FAILED
                            ? 'Original simulated delivery failure.'
                            : null
                );

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

                            'error_message' =>
                                $errorMessage,

                            'metadata' => [
                                'invoice_number' =>
                                    $invoice->invoice_number,
                            ],
                        ],
                        $overrides
                    )
                );

        if ($retryFields !== []) {
            $notification
                ->forceFill(
                    $retryFields
                )
                ->save();
        }

        $notification
            ->forceFill([
                'created_at' =>
                    $createdAt,

                'updated_at' =>
                    $createdAt,
            ])
            ->saveQuietly();

        return $notification->fresh();
    }

    /**
     * @return array{
     *     Organization,
     *     SubscriptionInvoice,
     *     SubscriptionInvoiceNotification
     * }
     */
    private function createOtherOrganizationReminder(): array
    {
        $organization =
            Organization::query()->create([
                'name' =>
                    'Other Retry Clinic',

                'slug' =>
                    'other-retry-clinic-'
                    .Str::lower(
                        Str::random(8)
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

        $invoice =
            SubscriptionInvoice::query()->create([
                'organization_subscription_id' =>
                    $subscription->id,

                'organization_id' =>
                    $organization->id,

                'subscription_plan_id' =>
                    $this->plan->id,

                'invoice_number' =>
                    'FOREIGN-REMINDER-RETRY',

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

        $notification =
            SubscriptionInvoiceNotification::query()
                ->create([
                    'organization_id' =>
                        $organization->id,

                    'subscription_invoice_id' =>
                        $invoice->id,

                    'recipient_user_id' =>
                        $billingOwner->id,

                    'reminder_key' =>
                        SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS,

                    'status' =>
                        SubscriptionInvoiceNotification::STATUS_FAILED,

                    'recipient_email' =>
                        $billingOwner->email,

                    'subject' =>
                        'Foreign failed reminder',

                    'message' =>
                        'A foreign reminder failed.',

                    'scheduled_for' =>
                        now(),

                    'sent_at' => null,

                    'failed_at' =>
                        now(),

                    'error_message' =>
                        'Foreign simulated failure.',

                    'metadata' => [
                        'invoice_number' =>
                            $invoice->invoice_number,
                    ],
                ]);

        return [
            $organization,
            $invoice,
            $notification,
        ];
    }
}
