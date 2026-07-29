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
use App\Models\SubscriptionInvoiceReminderSetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\SubscriptionInvoiceNotificationService;
use App\Services\SubscriptionInvoiceReminderSettingsService;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class PlatformSubscriptionInvoiceReminderSettingsTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $organizationAdmin;

    private User $platformAdmin;

    private OrganizationSubscription $subscription;

    private SubscriptionPlan $plan;

    private int $invoiceSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            '2026-07-29 18:00:00'
        );

        Cache::flush();

        config()->set(
            'subscription-invoice-notifications.enabled',
            true
        );

        config()->set(
            'subscription-invoice-notifications.before_due_days',
            [3, 1]
        );

        config()->set(
            'subscription-invoice-notifications.overdue_days',
            [7, 1]
        );

        config()->set(
            'subscription-invoice-notifications.automatic_retry_minutes',
            60
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
                'Reminder Settings Clinic',

            'name' =>
                'Reminder Settings Administrator',

            'email' =>
                'reminder-settings-admin@example.com',

            'password' =>
                'StrongPass1!',

            'password_confirmation' =>
                'StrongPass1!',
        ]);

        $this->organization =
            Organization::query()
                ->where(
                    'name',
                    'Reminder Settings Clinic'
                )
                ->firstOrFail();

        $this->organizationAdmin =
            User::query()
                ->where(
                    'email',
                    'reminder-settings-admin@example.com'
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
                $this->organizationAdmin->id,

            'status' =>
                OrganizationSubscriptionStatus::ACTIVE,

            'payment_status' =>
                SubscriptionPaymentStatus::PAID,

            'trial_ends_at' => null,

            'current_period_starts_at' =>
                now()->subDay(),

            'current_period_ends_at' =>
                now()->addMonth(),

            'cancelled_at' => null,
            'ends_at' => null,
        ]);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_platform_admin_sees_configuration_fallbacks(): void
    {
        $response = $this
            ->actingAs($this->platformAdmin)
            ->get($this->settingsUrl());

        $response
            ->assertOk()
            ->assertSeeText(
                'Subscription Invoice Reminder Settings'
            )
            ->assertSee(
                'name="before_due_days"',
                false
            )
            ->assertSee(
                'value="3, 1"',
                false
            )
            ->assertSee(
                'name="overdue_days"',
                false
            )
            ->assertSee(
                'value="7, 1"',
                false
            )
            ->assertSee(
                'name="automatic_retry_minutes"',
                false
            )
            ->assertSee(
                'value="60"',
                false
            )
            ->assertSee(
                'name="manual_retry_minutes"',
                false
            )
            ->assertSee(
                'value="5"',
                false
            );

        $this->assertDatabaseCount(
            'subscription_invoice_reminder_settings',
            0
        );
    }

    public function test_organization_user_cannot_manage_settings(): void
    {
        $this
            ->actingAs($this->organizationAdmin)
            ->get($this->settingsUrl())
            ->assertForbidden();

        $this
            ->actingAs($this->organizationAdmin)
            ->patch(
                $this->settingsUrl(),
                $this->settingsPayload()
            )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'subscription_invoice_reminder_settings',
            0
        );
    }

    public function test_platform_admin_can_update_settings(): void
    {
        $settingsService =
            app(
                SubscriptionInvoiceReminderSettingsService::class
            );

        $this->assertSame(
            [3, 1],
            $settingsService->beforeDueDays()
        );

        $response = $this
            ->actingAs($this->platformAdmin)
            ->patch(
                $this->settingsUrl(),
                $this->settingsPayload()
            );

        $response
            ->assertRedirect(
                $this->settingsUrl()
            )
            ->assertSessionHas(
                'success',
                'Subscription invoice reminder settings updated successfully.'
            );

        $setting =
            SubscriptionInvoiceReminderSetting::query()
                ->sole();

        $this->assertTrue(
            $setting->automatic_reminders_enabled
        );

        $this->assertSame(
            [7, 3, 1],
            $setting->before_due_days
        );

        $this->assertSame(
            [14, 7, 1],
            $setting->overdue_days
        );

        $this->assertSame(
            120,
            $setting->automatic_retry_minutes
        );

        $this->assertSame(
            10,
            $setting->manual_retry_minutes
        );

        $this->assertSame(
            $this->platformAdmin->id,
            $setting->updated_by_user_id
        );

        $this->assertSame(
            [7, 3, 1],
            $settingsService->beforeDueDays()
        );

        $this->assertSame(
            [14, 7, 1],
            $settingsService->overdueDays()
        );

        $activity =
            ActivityLog::query()
                ->where(
                    'action',
                    'platform.subscription_invoice_reminder_settings_updated'
                )
                ->sole();

        $this->assertNull(
            $activity->organization_id
        );

        $this->assertSame(
            $this->platformAdmin->id,
            $activity->user_id
        );

        $this->assertSame(
            $setting->getMorphClass(),
            $activity->subject_type
        );

        $this->assertSame(
            $setting->id,
            $activity->subject_id
        );

        $this->assertSame(
            [3, 1],
            data_get(
                $activity->properties,
                'old.before_due_days'
            )
        );

        $this->assertSame(
            [7, 3, 1],
            data_get(
                $activity->properties,
                'new.before_due_days'
            )
        );
    }

    public function test_invalid_milestones_are_rejected(): void
    {
        $response = $this
            ->actingAs($this->platformAdmin)
            ->from($this->settingsUrl())
            ->patch(
                $this->settingsUrl(),
                $this->settingsPayload([
                    'before_due_days' =>
                        '3, 3, 0, 366, tomorrow',

                    'overdue_days' =>
                        '',
                ])
            );

        $response
            ->assertRedirect(
                $this->settingsUrl()
            )
            ->assertSessionHasErrors([
                'before_due_days',
                'overdue_days',
            ]);

        $this->assertDatabaseCount(
            'subscription_invoice_reminder_settings',
            0
        );
    }

    public function test_invalid_retry_intervals_are_rejected(): void
    {
        $response = $this
            ->actingAs($this->platformAdmin)
            ->from($this->settingsUrl())
            ->patch(
                $this->settingsUrl(),
                $this->settingsPayload([
                    'automatic_retry_minutes' =>
                        0,

                    'manual_retry_minutes' =>
                        10081,
                ])
            );

        $response
            ->assertRedirect(
                $this->settingsUrl()
            )
            ->assertSessionHasErrors([
                'automatic_retry_minutes',
                'manual_retry_minutes',
            ]);

        $this->assertDatabaseCount(
            'subscription_invoice_reminder_settings',
            0
        );
    }

    public function test_update_rolls_back_when_audit_logging_fails(): void
    {
        $setting = $this->createSetting([
            'before_due_days' =>
                [3, 1],

            'automatic_retry_minutes' =>
                60,
        ]);

        $settingsService =
            app(
                SubscriptionInvoiceReminderSettingsService::class
            );

        $this->assertSame(
            [3, 1],
            $settingsService->beforeDueDays()
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
                            'Simulated settings audit failure.'
                        )
                    );
            }
        );

        $this->withoutExceptionHandling();

        try {
            $this
                ->actingAs($this->platformAdmin)
                ->patch(
                    $this->settingsUrl(),
                    $this->settingsPayload()
                );

            $this->fail(
                'The expected audit exception was not thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated settings audit failure.',
                $exception->getMessage()
            );
        }

        $setting->refresh();

        $this->assertSame(
            [3, 1],
            $setting->before_due_days
        );

        $this->assertSame(
            60,
            $setting->automatic_retry_minutes
        );

        $this->assertSame(
            [3, 1],
            $settingsService->beforeDueDays()
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'platform.subscription_invoice_reminder_settings_updated',
            ]
        );
    }

    public function test_service_uses_configuration_when_no_row_exists(): void
    {
        $service =
            app(
                SubscriptionInvoiceReminderSettingsService::class
            );

        $this->assertSame(
            [
                'automatic_reminders_enabled' =>
                    true,

                'before_due_days' =>
                    [3, 1],

                'overdue_days' =>
                    [7, 1],

                'automatic_retry_minutes' =>
                    60,

                'manual_retry_minutes' =>
                    5,
            ],
            $service->settings()
        );
    }

    public function test_service_caches_database_settings(): void
    {
        $setting = $this->createSetting([
            'before_due_days' =>
                [5, 2],
        ]);

        $service =
            app(
                SubscriptionInvoiceReminderSettingsService::class
            );

        $this->assertSame(
            [5, 2],
            $service->beforeDueDays()
        );

        $setting->update([
            'before_due_days' =>
                [9, 4],
        ]);

        $this->assertSame(
            [5, 2],
            $service->beforeDueDays()
        );

        $service->forgetCache();

        $this->assertSame(
            [9, 4],
            $service->beforeDueDays()
        );
    }

    public function test_disabled_settings_prevent_automatic_reminders(): void
    {
        Mail::fake();

        $this->createSetting([
            'automatic_reminders_enabled' =>
                false,
        ]);

        $this->createInvoice([
            'due_date' =>
                now()
                    ->addDays(3)
                    ->toDateString(),
        ]);

        $this->artisan(
            'subscription-invoices:send-reminders'
        )
            ->expectsOutput(
                'Subscription invoice reminders are disabled.'
            )
            ->assertExitCode(0);

        Mail::assertNothingSent();

        $this->assertDatabaseCount(
            'subscription_invoice_notifications',
            0
        );
    }

    public function test_database_milestones_work_when_configuration_is_empty(): void
    {
        Mail::fake();

        config()->set(
            'subscription-invoice-notifications.before_due_days',
            []
        );

        config()->set(
            'subscription-invoice-notifications.overdue_days',
            []
        );

        $this->createSetting([
            'before_due_days' =>
                [5],

            'overdue_days' =>
                [14],
        ]);

        $invoice = $this->createInvoice([
            'invoice_number' =>
                'DATABASE-MILESTONE-OVERRIDE',

            'due_date' =>
                now()
                    ->addDays(5)
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
                return $mail->invoice->is(
                    $invoice
                )
                    && $mail->reminderKey
                        === 'due_in_5_days';
            }
        );
    }

    public function test_command_uses_custom_before_due_milestone(): void
    {
        Mail::fake();

        $this->createSetting([
            'before_due_days' =>
                [5],

            'overdue_days' =>
                [14],
        ]);

        $invoice = $this->createInvoice([
            'invoice_number' =>
                'CUSTOM-FIVE-DAY-REMINDER',

            'due_date' =>
                now()
                    ->addDays(5)
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
                return $mail->invoice->is(
                    $invoice
                )
                    && $mail->reminderKey
                        === 'due_in_5_days';
            }
        );
    }

    public function test_command_uses_custom_overdue_milestone(): void
    {
        Mail::fake();

        $this->createSetting([
            'before_due_days' =>
                [5],

            'overdue_days' =>
                [14],
        ]);

        $invoice = $this->createInvoice([
            'invoice_number' =>
                'CUSTOM-FOURTEEN-DAY-OVERDUE',

            'status' =>
                SubscriptionInvoiceStatus::OVERDUE,

            'due_date' =>
                now()
                    ->subDays(14)
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
                return $mail->invoice->is(
                    $invoice
                )
                    && $mail->reminderKey
                        === 'overdue_14_days';
            }
        );
    }

    public function test_automatic_retry_uses_database_interval(): void
    {
        config()->set(
            'subscription-invoice-notifications.automatic_retry_minutes',
            60
        );

        $this->createSetting([
            'automatic_retry_minutes' =>
                15,
        ]);

        $invoice = $this->createInvoice();

        $this->createNotification(
            $invoice,
            [
                'status' =>
                    SubscriptionInvoiceNotification::STATUS_FAILED,

                'created_at' =>
                    now()->subMinutes(20),
            ]
        );

        $notificationService =
            app(
                SubscriptionInvoiceNotificationService::class
            );

        $this->assertFalse(
            $notificationService->recentAttemptExists(
                $invoice,
                SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS
            )
        );
    }

    public function test_manual_retry_uses_database_interval(): void
    {
        Mail::fake();

        config()->set(
            'subscription-invoice-notifications.manual_retry_minutes',
            60
        );

        $this->createSetting([
            'manual_retry_minutes' =>
                5,
        ]);

        $invoice = $this->createInvoice();

        $originalFailure =
            $this->createNotification(
                $invoice,
                [
                    'created_at' =>
                        now()->subMinutes(30),
                ]
            );

        $failedRetry =
            $this->createNotification(
                $invoice,
                [
                    'created_at' =>
                        now()->subMinutes(10),

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
            ->assertRedirect(
                $this->platformInvoiceUrl(
                    $invoice
                )
            )
            ->assertSessionHas(
                'success'
            );

        Mail::assertSent(
            SubscriptionInvoiceReminderMail::class
        );

        $this->assertSame(
            3,
            SubscriptionInvoiceNotification::query()
                ->where(
                    'subscription_invoice_id',
                    $invoice->id
                )
                ->count()
        );
    }

    public function test_platform_navigation_links_to_settings(): void
    {
        $this
            ->actingAs($this->platformAdmin)
            ->get('/platform')
            ->assertOk()
            ->assertSeeText(
                'Invoice Reminder Settings'
            )
            ->assertSee(
                '/platform/subscription-invoice-reminder-settings',
                false
            );
    }

    private function settingsUrl(): string
    {
        return '/platform/subscription-invoice-reminder-settings';
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function settingsPayload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'automatic_reminders_enabled' =>
                    '1',

                'before_due_days' =>
                    '7, 3, 1',

                'overdue_days' =>
                    '14, 7, 1',

                'automatic_retry_minutes' =>
                    120,

                'manual_retry_minutes' =>
                    10,
            ],
            $overrides
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createSetting(
        array $overrides = []
    ): SubscriptionInvoiceReminderSetting {
        $setting =
            SubscriptionInvoiceReminderSetting::query()
                ->create(
                    array_merge(
                        [
                            'automatic_reminders_enabled' =>
                                true,

                            'before_due_days' =>
                                [3, 1],

                            'overdue_days' =>
                                [7, 1],

                            'automatic_retry_minutes' =>
                                60,

                            'manual_retry_minutes' =>
                                5,

                            'updated_by_user_id' =>
                                $this->platformAdmin->id,
                        ],
                        $overrides
                    )
                );

        app(
            SubscriptionInvoiceReminderSettingsService::class
        )->forgetCache();

        return $setting;
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
                        'SETTINGS-INVOICE-'
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
                        'Reminder settings test invoice.',

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
                                $this->organizationAdmin->id,

                            'retry_of_notification_id' => null,

                            'retry_requested_by_user_id' => null,

                            'reminder_key' =>
                                SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS,

                            'status' =>
                                SubscriptionInvoiceNotification::STATUS_FAILED,

                            'recipient_email' =>
                                $this->organizationAdmin->email,

                            'subject' =>
                                'Subscription invoice reminder',

                            'message' =>
                                'A subscription invoice reminder failed.',

                            'scheduled_for' =>
                                $createdAt,

                            'sent_at' => null,

                            'failed_at' =>
                                $createdAt,

                            'error_message' =>
                                'Simulated delivery failure.',

                            'metadata' => [
                                'invoice_number' =>
                                    $invoice->invoice_number,
                            ],
                        ],
                        $overrides
                    )
                );

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

    private function platformInvoiceUrl(
        SubscriptionInvoice $invoice
    ): string {
        return "/platform/organizations/"
            ."{$this->organization->id}"
            ."/subscription-invoices/"
            ."{$invoice->id}";
    }
}
