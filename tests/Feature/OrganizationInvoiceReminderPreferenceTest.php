<?php

namespace Tests\Feature;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionInvoiceStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Mail\SubscriptionInvoiceReminderMail;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\OrganizationInvoiceReminderPreference;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\OrganizationInvoiceReminderPreferenceService;
use App\Services\SubscriptionInvoiceReminderSettingsService;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class OrganizationInvoiceReminderPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $billingOwner;

    private OrganizationSubscription $subscription;

    private SubscriptionPlan $plan;

    private int $invoiceSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            '2026-07-29 18:30:00'
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

        $this->seed(
            SubscriptionPlanSeeder::class
        );

        $this->post('/register', [
            'organization_name' =>
                'Reminder Preference Clinic',

            'name' =>
                'Reminder Preference Owner',

            'email' =>
                'reminder-preference-owner@example.com',

            'password' =>
                'StrongPass1!',

            'password_confirmation' =>
                'StrongPass1!',
        ]);

        $this->organization =
            Organization::query()
                ->where(
                    'name',
                    'Reminder Preference Clinic'
                )
                ->firstOrFail();

        $this->billingOwner =
            User::query()
                ->where(
                    'email',
                    'reminder-preference-owner@example.com'
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

        $this->subscription->update([
            'billing_owner_user_id' =>
                $this->billingOwner->id,

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

    public function test_billing_owner_sees_enabled_defaults_without_preference_row(): void
    {
        $response = $this
            ->actingAs($this->billingOwner)
            ->get($this->preferencesUrl());

        $response
            ->assertOk()
            ->assertSeeText(
                'Invoice Reminder Preferences'
            )
            ->assertSee(
                'name="before_due_reminders_enabled"',
                false
            )
            ->assertSee(
                'name="overdue_reminders_enabled"',
                false
            )
            ->assertSeeText(
                'Before-due reminders'
            )
            ->assertSeeText(
                'Overdue reminders'
            );

        $this->assertDatabaseCount(
            'organization_invoice_reminder_preferences',
            0
        );
    }

    public function test_non_billing_organization_user_cannot_manage_preferences(): void
    {
        $nonBillingUser =
            User::factory()->create([
                'organization_id' =>
                    $this->organization->id,

                'platform_role_id' => null,
                'is_active' => true,
            ]);

        $this
            ->actingAs($nonBillingUser)
            ->get($this->preferencesUrl())
            ->assertForbidden();

        $this
            ->actingAs($nonBillingUser)
            ->patch(
                $this->preferencesUrl(),
                $this->preferencePayload()
            )
            ->assertForbidden();

        $this->assertDatabaseCount(
            'organization_invoice_reminder_preferences',
            0
        );
    }

    public function test_guest_is_redirected_from_preferences(): void
    {
        $this->post('/logout');

        $this->assertGuest();

        $this
            ->get($this->preferencesUrl())
            ->assertRedirect('/login');
    }

    public function test_billing_owner_can_update_preferences(): void
    {
        $service =
            app(
                OrganizationInvoiceReminderPreferenceService::class
            );

        $this->assertSame(
            [
                'before_due_reminders_enabled' =>
                    true,

                'overdue_reminders_enabled' =>
                    true,
            ],
            $service->preferencesFor(
                $this->organization->id
            )
        );

        $response = $this
            ->actingAs($this->billingOwner)
            ->patch(
                $this->preferencesUrl(),
                $this->preferencePayload([
                    'before_due_reminders_enabled' =>
                        '0',

                    'overdue_reminders_enabled' =>
                        '1',
                ])
            );

        $response
            ->assertRedirect(
                $this->preferencesUrl()
            )
            ->assertSessionHas(
                'success',
                'Invoice reminder preferences updated successfully.'
            );

        $preference =
            OrganizationInvoiceReminderPreference::query()
                ->sole();

        $this->assertSame(
            $this->organization->id,
            $preference->organization_id
        );

        $this->assertFalse(
            $preference
                ->before_due_reminders_enabled
        );

        $this->assertTrue(
            $preference
                ->overdue_reminders_enabled
        );

        $this->assertSame(
            $this->billingOwner->id,
            $preference->updated_by_user_id
        );

        $this->assertSame(
            [
                'before_due_reminders_enabled' =>
                    false,

                'overdue_reminders_enabled' =>
                    true,
            ],
            $service->preferencesFor(
                $this->organization->id
            )
        );

        $activity =
            ActivityLog::query()
                ->where(
                    'action',
                    'organization.subscription_invoice_reminder_preferences_updated'
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
            $preference->getMorphClass(),
            $activity->subject_type
        );

        $this->assertSame(
            $preference->id,
            $activity->subject_id
        );

        $this->assertTrue(
            data_get(
                $activity->properties,
                'old.before_due_reminders_enabled'
            )
        );

        $this->assertFalse(
            data_get(
                $activity->properties,
                'new.before_due_reminders_enabled'
            )
        );

        $this->assertTrue(
            data_get(
                $activity->properties,
                'new.overdue_reminders_enabled'
            )
        );
    }

    public function test_invalid_preference_values_are_rejected(): void
    {
        $response = $this
            ->actingAs($this->billingOwner)
            ->from($this->preferencesUrl())
            ->patch(
                $this->preferencesUrl(),
                [
                    'before_due_reminders_enabled' =>
                        'sometimes',
                ]
            );

        $response
            ->assertRedirect(
                $this->preferencesUrl()
            )
            ->assertSessionHasErrors([
                'before_due_reminders_enabled',
                'overdue_reminders_enabled',
            ]);

        $this->assertDatabaseCount(
            'organization_invoice_reminder_preferences',
            0
        );
    }

    public function test_update_rolls_back_when_audit_logging_fails(): void
    {
        $preference =
            $this->createPreference([
                'before_due_reminders_enabled' =>
                    true,

                'overdue_reminders_enabled' =>
                    true,
            ]);

        $service =
            app(
                OrganizationInvoiceReminderPreferenceService::class
            );

        $this->assertTrue(
            $service
                ->beforeDueRemindersEnabled(
                    $this->organization->id
                )
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
                            'Simulated preference audit failure.'
                        )
                    );
            }
        );

        $this->withoutExceptionHandling();

        try {
            $this
                ->actingAs($this->billingOwner)
                ->patch(
                    $this->preferencesUrl(),
                    $this->preferencePayload([
                        'before_due_reminders_enabled' =>
                            '0',
                    ])
                );

            $this->fail(
                'The expected audit exception was not thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated preference audit failure.',
                $exception->getMessage()
            );
        }

        $preference->refresh();

        $this->assertTrue(
            $preference
                ->before_due_reminders_enabled
        );

        $this->assertTrue(
            $preference
                ->overdue_reminders_enabled
        );

        $this->assertTrue(
            $service
                ->beforeDueRemindersEnabled(
                    $this->organization->id
                )
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'organization.subscription_invoice_reminder_preferences_updated',
            ]
        );
    }

    public function test_service_defaults_both_reminder_types_to_enabled(): void
    {
        $service =
            app(
                OrganizationInvoiceReminderPreferenceService::class
            );

        $this->assertSame(
            [
                'before_due_reminders_enabled' =>
                    true,

                'overdue_reminders_enabled' =>
                    true,
            ],
            $service->preferencesFor(
                $this->organization->id
            )
        );

        $this->assertTrue(
            $service
                ->beforeDueRemindersEnabled(
                    $this->organization->id
                )
        );

        $this->assertTrue(
            $service
                ->overdueRemindersEnabled(
                    $this->organization->id
                )
        );
    }

    public function test_service_caches_preferences_per_organization(): void
    {
        $preference =
            $this->createPreference([
                'before_due_reminders_enabled' =>
                    true,
            ]);

        $service =
            app(
                OrganizationInvoiceReminderPreferenceService::class
            );

        $this->assertTrue(
            $service
                ->beforeDueRemindersEnabled(
                    $this->organization->id
                )
        );

        $preference->update([
            'before_due_reminders_enabled' =>
                false,
        ]);

        $this->assertTrue(
            $service
                ->beforeDueRemindersEnabled(
                    $this->organization->id
                )
        );

        $service->forgetCache(
            $this->organization->id
        );

        $this->assertFalse(
            $service
                ->beforeDueRemindersEnabled(
                    $this->organization->id
                )
        );
    }

    public function test_disabling_before_due_reminders_still_allows_overdue_reminders(): void
    {
        Mail::fake();

        $this->createPreference([
            'before_due_reminders_enabled' =>
                false,

            'overdue_reminders_enabled' =>
                true,
        ]);

        $dueInvoice =
            $this->createInvoice([
                'invoice_number' =>
                    'PREFERENCE-DUE-DISABLED',

                'due_date' =>
                    now()
                        ->addDays(3)
                        ->toDateString(),
            ]);

        $overdueInvoice =
            $this->createInvoice([
                'invoice_number' =>
                    'PREFERENCE-OVERDUE-ENABLED',

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
            1
        );

        $this->assertDatabaseMissing(
            'subscription_invoice_notifications',
            [
                'subscription_invoice_id' =>
                    $dueInvoice->id,
            ]
        );

        $this->assertDatabaseHas(
            'subscription_invoice_notifications',
            [
                'subscription_invoice_id' =>
                    $overdueInvoice->id,

                'reminder_key' =>
                    'overdue_1_day',
            ]
        );
    }

    public function test_disabling_overdue_reminders_still_allows_before_due_reminders(): void
    {
        Mail::fake();

        $this->createPreference([
            'before_due_reminders_enabled' =>
                true,

            'overdue_reminders_enabled' =>
                false,
        ]);

        $dueInvoice =
            $this->createInvoice([
                'invoice_number' =>
                    'PREFERENCE-DUE-ENABLED',

                'due_date' =>
                    now()
                        ->addDays(3)
                        ->toDateString(),
            ]);

        $overdueInvoice =
            $this->createInvoice([
                'invoice_number' =>
                    'PREFERENCE-OVERDUE-DISABLED',

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
            1
        );

        $this->assertDatabaseHas(
            'subscription_invoice_notifications',
            [
                'subscription_invoice_id' =>
                    $dueInvoice->id,

                'reminder_key' =>
                    'due_in_3_days',
            ]
        );

        $this->assertDatabaseMissing(
            'subscription_invoice_notifications',
            [
                'subscription_invoice_id' =>
                    $overdueInvoice->id,
            ]
        );
    }

    public function test_disabling_both_preferences_prevents_all_automatic_reminders(): void
    {
        Mail::fake();

        $this->createPreference([
            'before_due_reminders_enabled' =>
                false,

            'overdue_reminders_enabled' =>
                false,
        ]);

        $this->createInvoice([
            'invoice_number' =>
                'PREFERENCE-NO-DUE',

            'due_date' =>
                now()
                    ->addDays(3)
                    ->toDateString(),
        ]);

        $this->createInvoice([
            'invoice_number' =>
                'PREFERENCE-NO-OVERDUE',

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

        Mail::assertNothingSent();

        $this->assertDatabaseCount(
            'subscription_invoice_notifications',
            0
        );
    }

    public function test_global_disable_overrides_enabled_organization_preferences(): void
    {
        Mail::fake();

        $this->createPreference([
            'before_due_reminders_enabled' =>
                true,

            'overdue_reminders_enabled' =>
                true,
        ]);

        $this->createInvoice([
            'invoice_number' =>
                'PREFERENCE-GLOBAL-DISABLED',

            'due_date' =>
                now()
                    ->addDays(3)
                    ->toDateString(),
        ]);

        config()->set(
            'subscription-invoice-notifications.enabled',
            false
        );

        app(
            SubscriptionInvoiceReminderSettingsService::class
        )->forgetCache();

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

    public function test_preferences_are_tenant_scoped_during_delivery(): void
    {
        Mail::fake();

        $this->createPreference([
            'before_due_reminders_enabled' =>
                false,

            'overdue_reminders_enabled' =>
                true,
        ]);

        $currentInvoice =
            $this->createInvoice([
                'invoice_number' =>
                    'PREFERENCE-CURRENT-TENANT',

                'due_date' =>
                    now()
                        ->addDays(3)
                        ->toDateString(),
            ]);

        $other =
            $this->createOtherOrganizationContext();

        $otherInvoice =
            $this->createInvoiceFor(
                $other['organization'],
                $other['subscription'],
                $other['plan'],
                [
                    'invoice_number' =>
                        'PREFERENCE-OTHER-TENANT',

                    'due_date' =>
                        now()
                            ->addDays(3)
                            ->toDateString(),
                ]
            );

        $this->artisan(
            'subscription-invoices:send-reminders'
        )->assertExitCode(0);

        Mail::assertSent(
            SubscriptionInvoiceReminderMail::class,
            function (
                SubscriptionInvoiceReminderMail $mail
            ) use ($otherInvoice): bool {
                return $mail->invoice->is(
                    $otherInvoice
                );
            }
        );

        Mail::assertSent(
            SubscriptionInvoiceReminderMail::class,
            1
        );

        $this->assertDatabaseMissing(
            'subscription_invoice_notifications',
            [
                'subscription_invoice_id' =>
                    $currentInvoice->id,
            ]
        );

        $this->assertDatabaseHas(
            'subscription_invoice_notifications',
            [
                'organization_id' =>
                    $other['organization']->id,

                'subscription_invoice_id' =>
                    $otherInvoice->id,

                'reminder_key' =>
                    'due_in_3_days',
            ]
        );
    }

    public function test_billing_portal_links_to_reminder_preferences(): void
    {
        $this
            ->actingAs($this->billingOwner)
            ->get('/subscription/billing')
            ->assertOk()
            ->assertSeeText(
                'Reminder Preferences'
            )
            ->assertSee(
                '/subscription/billing/reminder-preferences',
                false
            );
    }

    private function preferencesUrl(): string
    {
        return '/subscription/billing/reminder-preferences';
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function preferencePayload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'before_due_reminders_enabled' =>
                    '1',

                'overdue_reminders_enabled' =>
                    '1',
            ],
            $overrides
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createPreference(
        array $overrides = []
    ): OrganizationInvoiceReminderPreference {
        return $this->createPreferenceFor(
            $this->organization,
            $this->billingOwner,
            $overrides
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createPreferenceFor(
        Organization $organization,
        User $updatedBy,
        array $overrides = []
    ): OrganizationInvoiceReminderPreference {
        $preference =
            OrganizationInvoiceReminderPreference::query()
                ->create(
                    array_merge(
                        [
                            'organization_id' =>
                                $organization->id,

                            'before_due_reminders_enabled' =>
                                true,

                            'overdue_reminders_enabled' =>
                                true,

                            'updated_by_user_id' =>
                                $updatedBy->id,
                        ],
                        $overrides
                    )
                );

        app(
            OrganizationInvoiceReminderPreferenceService::class
        )->forgetCache(
            $organization->id
        );

        return $preference;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createInvoice(
        array $overrides = []
    ): SubscriptionInvoice {
        return $this->createInvoiceFor(
            $this->organization,
            $this->subscription,
            $this->plan,
            $overrides
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createInvoiceFor(
        Organization $organization,
        OrganizationSubscription $subscription,
        SubscriptionPlan $plan,
        array $overrides = []
    ): SubscriptionInvoice {
        $this->invoiceSequence++;

        return SubscriptionInvoice::query()->create(
            array_merge(
                [
                    'organization_subscription_id' =>
                        $subscription->id,

                    'organization_id' =>
                        $organization->id,

                    'subscription_plan_id' =>
                        $plan->id,

                    'invoice_number' =>
                        'PREFERENCE-INVOICE-'
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
                        'Organization reminder preference test invoice.',

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
     * @return array{
     *     organization: Organization,
     *     billing_owner: User,
     *     subscription: OrganizationSubscription,
     *     plan: SubscriptionPlan
     * }
     */
    private function createOtherOrganizationContext(): array
    {
        $suffix =
            Str::lower(
                Str::random(8)
            );

        $organization =
            Organization::query()->create([
                'name' =>
                    "Other Reminder Clinic {$suffix}",

                'slug' =>
                    "other-reminder-clinic-{$suffix}",
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

                'cancel_at_period_end' =>
                    false,

                'cancelled_at' => null,
                'ends_at' => null,
            ]);

        return [
            'organization' =>
                $organization,

            'billing_owner' =>
                $billingOwner,

            'subscription' =>
                $subscription,

            'plan' =>
                $this->plan,
        ];
    }
}
