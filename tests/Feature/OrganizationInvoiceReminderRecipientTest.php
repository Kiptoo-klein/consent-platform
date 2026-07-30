<?php

namespace Tests\Feature;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionInvoiceStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Mail\SubscriptionInvoiceReminderMail;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\OrganizationInvoiceReminderRecipient;
use App\Models\OrganizationSubscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionInvoiceNotification;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\OrganizationInvoiceReminderRecipientService;
use App\Services\SubscriptionInvoiceNotificationService;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class OrganizationInvoiceReminderRecipientTest extends TestCase
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

        Carbon::setTestNow('2026-07-30 11:50:00');
        Cache::flush();

        config()->set('subscription-invoice-notifications.enabled', true);
        config()->set('subscription-invoice-notifications.before_due_days', [3, 1]);
        config()->set('subscription-invoice-notifications.overdue_days', [7, 1]);
        config()->set('subscription-invoice-notifications.automatic_retry_minutes', 60);
        config()->set('subscription-invoice-notifications.manual_retry_minutes', 5);

        $this->seed(SubscriptionPlanSeeder::class);

        $this->post('/register', [
            'organization_name' => 'Reminder Recipient Clinic',
            'name' => 'Reminder Recipient Owner',
            'email' => 'reminder-recipient-owner@example.com',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
        ]);

        $this->organization = Organization::query()
            ->where('name', 'Reminder Recipient Clinic')
            ->firstOrFail();

        $this->billingOwner = User::query()
            ->where('email', 'reminder-recipient-owner@example.com')
            ->firstOrFail();

        $this->subscription = $this->organization
            ->subscription()
            ->firstOrFail();

        $this->plan = $this->subscription
            ->plan()
            ->firstOrFail();

        $this->subscription->update([
            'billing_owner_user_id' => $this->billingOwner->id,
            'status' => OrganizationSubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'trial_ends_at' => null,
            'current_period_starts_at' => now()->subDay(),
            'current_period_ends_at' => now()->addMonth(),
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

    public function test_billing_owner_sees_only_eligible_additional_recipient_choices(): void
    {
        $active = $this->createUser([
            'name' => 'Active Accounts Lead',
            'email' => 'active-accounts@example.com',
        ]);

        $disabled = $this->createUser([
            'name' => 'Disabled Accounts Lead',
            'is_active' => false,
        ]);

        $archived = $this->createUser([
            'name' => 'Archived Accounts Lead',
        ]);
        $archived->delete();

        $other = $this->createOtherOrganizationContext();

        $this->actingAs($this->billingOwner)
            ->get($this->recipientsUrl())
            ->assertOk()
            ->assertSeeText('Invoice Reminder Recipients')
            ->assertSeeText('Billing owner')
            ->assertSeeText('Always included')
            ->assertSeeText($this->billingOwner->email)
            ->assertSeeText($active->name)
            ->assertSee('name="recipient_user_ids[]"', false)
            ->assertDontSeeText($disabled->name)
            ->assertDontSeeText($archived->name)
            ->assertDontSeeText($other['billing_owner']->name);

        $this->assertDatabaseCount('organization_invoice_reminder_recipients', 0);
    }

    public function test_only_billing_owner_can_manage_recipient_settings(): void
    {
        $recipient = $this->createUser();
        $nonBillingUser = $this->createUser();

        $this->actingAs($nonBillingUser)
            ->get($this->recipientsUrl())
            ->assertForbidden();

        $this->actingAs($nonBillingUser)
            ->patch($this->recipientsUrl(), $this->payload([$recipient->id]))
            ->assertForbidden();

        $this->post('/logout');

        $this->get($this->recipientsUrl())
            ->assertRedirect('/login');
    }

    public function test_billing_owner_can_replace_recipients_with_transactional_audit(): void
    {
        $first = $this->createUser(['email' => 'first-recipient@example.com']);
        $second = $this->createUser(['email' => 'second-recipient@example.com']);
        $removed = $this->createUser(['email' => 'removed-recipient@example.com']);

        $this->createRecipient($removed);

        $service = app(OrganizationInvoiceReminderRecipientService::class);
        $this->assertSame([$removed->id], $service->configuredRecipientUserIds($this->organization->id));

        $this->actingAs($this->billingOwner)
            ->patch($this->recipientsUrl(), $this->payload([$second->id, $first->id]))
            ->assertRedirect($this->recipientsUrl())
            ->assertSessionHas(
                'success',
                'Invoice reminder recipients updated successfully.'
            );

        $this->assertDatabaseCount('organization_invoice_reminder_recipients', 2);
        $this->assertDatabaseMissing('organization_invoice_reminder_recipients', [
            'organization_id' => $this->organization->id,
            'user_id' => $removed->id,
        ]);

        foreach ([$first, $second] as $recipient) {
            $this->assertDatabaseHas('organization_invoice_reminder_recipients', [
                'organization_id' => $this->organization->id,
                'user_id' => $recipient->id,
                'created_by_user_id' => $this->billingOwner->id,
            ]);
        }

        $this->assertSame(
            [$first->id, $second->id],
            $service->configuredRecipientUserIds($this->organization->id)
        );

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_invoice_reminder_recipients_updated'
            )
            ->sole();

        $this->assertSame($this->organization->id, $activity->organization_id);
        $this->assertSame($this->billingOwner->id, $activity->user_id);
        $this->assertSame($this->organization->getMorphClass(), $activity->subject_type);
        $this->assertSame($this->organization->id, $activity->subject_id);
        $this->assertSame([$removed->id], data_get($activity->properties, 'old.recipient_user_ids'));
        $this->assertSame(
            [$first->id, $second->id],
            data_get($activity->properties, 'new.recipient_user_ids')
        );
    }

    public function test_empty_selection_removes_optional_recipients_and_falls_back_to_owner(): void
    {
        $recipient = $this->createUser();
        $this->createRecipient($recipient);

        $this->actingAs($this->billingOwner)
            ->patch($this->recipientsUrl(), $this->payload([]))
            ->assertRedirect($this->recipientsUrl());

        $this->assertDatabaseCount('organization_invoice_reminder_recipients', 0);
        $this->assertSame(
            [$this->billingOwner->id],
            $this->eligibleRecipientIds($this->createInvoice())
        );
    }

    public function test_duplicate_foreign_disabled_archived_and_owner_ids_are_rejected(): void
    {
        $valid = $this->createUser();
        $disabled = $this->createUser(['is_active' => false]);
        $archived = $this->createUser();
        $archived->delete();
        $other = $this->createOtherOrganizationContext();

        $invalidPayloads = [
            [$valid->id, $valid->id],
            [$disabled->id],
            [$archived->id],
            [$other['billing_owner']->id],
            [$this->billingOwner->id],
        ];

        foreach ($invalidPayloads as $recipientIds) {
            $this->actingAs($this->billingOwner)
                ->from($this->recipientsUrl())
                ->patch($this->recipientsUrl(), $this->payload($recipientIds))
                ->assertRedirect($this->recipientsUrl())
                ->assertSessionHasErrors();
        }

        $this->assertDatabaseCount('organization_invoice_reminder_recipients', 0);
    }

    public function test_recipient_update_rolls_back_and_preserves_cache_when_audit_fails(): void
    {
        $original = $this->createUser();
        $replacement = $this->createUser();
        $this->createRecipient($original);

        $service = app(OrganizationInvoiceReminderRecipientService::class);
        $this->assertSame([$original->id], $service->configuredRecipientUserIds($this->organization->id));

        $this->mock(ActivityLogger::class, function (MockInterface $mock): void {
            $mock->shouldReceive('log')
                ->once()
                ->andThrow(new RuntimeException('Simulated recipient audit failure.'));
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->billingOwner)
                ->patch($this->recipientsUrl(), $this->payload([$replacement->id]));

            $this->fail('The expected audit exception was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated recipient audit failure.', $exception->getMessage());
        }

        $this->assertDatabaseHas('organization_invoice_reminder_recipients', [
            'organization_id' => $this->organization->id,
            'user_id' => $original->id,
        ]);
        $this->assertDatabaseMissing('organization_invoice_reminder_recipients', [
            'organization_id' => $this->organization->id,
            'user_id' => $replacement->id,
        ]);
        $this->assertSame([$original->id], $service->configuredRecipientUserIds($this->organization->id));
        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'organization.subscription_invoice_reminder_recipients_updated',
        ]);
    }

    public function test_service_caches_configured_ids_and_filters_ineligible_users(): void
    {
        $valid = $this->createUser(['email' => 'valid-extra@example.com']);
        $disabled = $this->createUser(['is_active' => false]);
        $invalidEmail = $this->createUser(['email' => 'not-an-email']);
        $archived = $this->createUser();
        $other = $this->createOtherOrganizationContext();

        foreach ([$valid, $disabled, $invalidEmail, $archived] as $user) {
            $this->createRecipient($user);
        }
        $this->createRecipientFor($this->organization, $other['billing_owner']);
        $archived->delete();

        $service = app(OrganizationInvoiceReminderRecipientService::class);
        $configured = $service->configuredRecipientUserIds($this->organization->id);

        $this->assertContains($valid->id, $configured);
        $this->assertContains($other['billing_owner']->id, $configured);
        $this->assertSame(
            [$this->billingOwner->id, $valid->id],
            $this->eligibleRecipientIds($this->createInvoice())
        );

        OrganizationInvoiceReminderRecipient::query()
            ->where('organization_id', $this->organization->id)
            ->delete();

        $this->assertSame($configured, $service->configuredRecipientUserIds($this->organization->id));

        $service->forgetCache($this->organization->id);
        $this->assertSame([], $service->configuredRecipientUserIds($this->organization->id));
    }

    public function test_valid_configured_recipient_can_receive_when_owner_is_ineligible(): void
    {
        $recipient = $this->createUser(['email' => 'fallback-accounts@example.com']);
        $this->createRecipient($recipient);

        $this->billingOwner->update([
            'is_active' => false,
            'email' => 'invalid-owner-email',
        ]);

        $this->assertSame(
            [$recipient->id],
            $this->eligibleRecipientIds($this->createInvoice())
        );
    }

    public function test_automatic_reminder_sends_one_attempt_per_effective_recipient(): void
    {
        Mail::fake();

        $recipient = $this->createUser(['email' => 'automatic-extra@example.com']);
        $this->createRecipient($recipient);
        $invoice = $this->createInvoice(['invoice_number' => 'MULTI-RECIPIENT-INVOICE']);

        $this->artisan('subscription-invoices:send-reminders')
            ->expectsOutput('Invoice reminder run complete. Sent: 2; failed: 0; skipped: 0.')
            ->assertExitCode(0);

        Mail::assertSentCount(2);

        foreach ([$this->billingOwner, $recipient] as $expectedRecipient) {
            Mail::assertSent(
                SubscriptionInvoiceReminderMail::class,
                fn (SubscriptionInvoiceReminderMail $mail): bool =>
                    $mail->invoice->is($invoice)
                    && $mail->hasTo($expectedRecipient->email)
            );

            $this->assertDatabaseHas('subscription_invoice_notifications', [
                'organization_id' => $this->organization->id,
                'subscription_invoice_id' => $invoice->id,
                'recipient_user_id' => $expectedRecipient->id,
                'recipient_email' => $expectedRecipient->email,
                'reminder_key' => SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS,
                'status' => SubscriptionInvoiceNotification::STATUS_SENT,
            ]);
        }
    }

    public function test_automatic_idempotency_is_per_recipient_and_new_recipient_gets_current_milestone(): void
    {
        Mail::fake();

        $first = $this->createUser(['email' => 'first-idempotent@example.com']);
        $this->createRecipient($first);
        $invoice = $this->createInvoice(['invoice_number' => 'RECIPIENT-IDEMPOTENCY']);

        $this->artisan('subscription-invoices:send-reminders')->assertExitCode(0);
        Mail::assertSentCount(2);

        $second = $this->createUser(['email' => 'second-idempotent@example.com']);
        $this->createRecipient($second);
        app(OrganizationInvoiceReminderRecipientService::class)
            ->forgetCache($this->organization->id);

        $this->artisan('subscription-invoices:send-reminders')->assertExitCode(0);

        Mail::assertSentCount(3);
        $this->assertSame(
            3,
            SubscriptionInvoiceNotification::query()
                ->where('subscription_invoice_id', $invoice->id)
                ->where(
                    'reminder_key',
                    SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS
                )
                ->count()
        );

        foreach ([$this->billingOwner, $first, $second] as $recipient) {
            $this->assertSame(
                1,
                SubscriptionInvoiceNotification::query()
                    ->where('subscription_invoice_id', $invoice->id)
                    ->where('recipient_user_id', $recipient->id)
                    ->where(
                        'reminder_key',
                        SubscriptionInvoiceNotification::REMINDER_DUE_IN_3_DAYS
                    )
                    ->count()
            );
        }
    }

    public function test_recipient_delivery_is_tenant_scoped_and_dry_run_lists_each_recipient(): void
    {
        Mail::fake();

        $currentExtra = $this->createUser(['email' => 'current-extra@example.com']);
        $this->createRecipient($currentExtra);
        $currentInvoice = $this->createInvoice(['invoice_number' => 'CURRENT-RECIPIENT-INVOICE']);

        $this->artisan('subscription-invoices:send-reminders', ['--dry-run' => true])
            ->expectsOutput(
                "Due: invoice #{$currentInvoice->id} - due_in_3_days reminder to {$this->billingOwner->email}"
            )
            ->expectsOutput(
                "Due: invoice #{$currentInvoice->id} - due_in_3_days reminder to {$currentExtra->email}"
            )
            ->expectsOutput('Invoice reminder run complete. Sent: 0; failed: 0; skipped: 1.')
            ->assertExitCode(0);

        Mail::assertNothingSent();
        $this->assertDatabaseCount('subscription_invoice_notifications', 0);

        $other = $this->createOtherOrganizationContext();
        $otherExtra = $this->createUserFor($other['organization'], [
            'email' => 'other-extra@example.com',
        ]);
        $this->createRecipientFor($other['organization'], $otherExtra, $other['billing_owner']);
        $otherInvoice = $this->createInvoiceFor(
            $other['organization'],
            $other['subscription'],
            $other['plan'],
            ['invoice_number' => 'OTHER-RECIPIENT-INVOICE']
        );

        $this->artisan('subscription-invoices:send-reminders')->assertExitCode(0);
        Mail::assertSentCount(4);

        foreach ([
            [$currentInvoice, $this->billingOwner],
            [$currentInvoice, $currentExtra],
            [$otherInvoice, $other['billing_owner']],
            [$otherInvoice, $otherExtra],
        ] as [$invoice, $recipient]) {
            Mail::assertSent(
                SubscriptionInvoiceReminderMail::class,
                fn (SubscriptionInvoiceReminderMail $mail): bool =>
                    $mail->invoice->is($invoice)
                    && $mail->hasTo($recipient->email)
            );
        }
    }

    public function test_manual_retry_resolution_stays_owner_only_and_portal_links_to_recipients(): void
    {
        $additional = $this->createUser();
        $this->createRecipient($additional);
        $invoice = $this->createInvoice();

        $manualRecipient = app(SubscriptionInvoiceNotificationService::class)
            ->eligibleRecipient($invoice);

        $this->assertNotNull($manualRecipient);
        $this->assertTrue($manualRecipient->is($this->billingOwner));
        $this->assertFalse($manualRecipient->is($additional));

        $this->actingAs($this->billingOwner)
            ->get('/subscription/billing')
            ->assertOk()
            ->assertSeeText('Reminder Recipients')
            ->assertSee('/subscription/billing/reminder-recipients', false);
    }

    private function recipientsUrl(): string
    {
        return '/subscription/billing/reminder-recipients';
    }

    /** @param list<int> $recipientUserIds */
    private function payload(array $recipientUserIds): array
    {
        return ['recipient_user_ids' => $recipientUserIds];
    }

    /** @param array<string, mixed> $overrides */
    private function createUser(array $overrides = []): User
    {
        return $this->createUserFor($this->organization, $overrides);
    }

    /** @param array<string, mixed> $overrides */
    private function createUserFor(Organization $organization, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'organization_id' => $organization->id,
            'platform_role_id' => null,
            'is_active' => true,
        ], $overrides));
    }

    private function createRecipient(User $user): OrganizationInvoiceReminderRecipient
    {
        return $this->createRecipientFor($this->organization, $user, $this->billingOwner);
    }

    private function createRecipientFor(
        Organization $organization,
        User $user,
        ?User $createdBy = null
    ): OrganizationInvoiceReminderRecipient {
        return OrganizationInvoiceReminderRecipient::query()->forceCreate([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'created_by_user_id' => ($createdBy ?? $this->billingOwner)->id,
        ]);
    }

    /** @return list<int> */
    private function eligibleRecipientIds(SubscriptionInvoice $invoice): array
    {
        return app(OrganizationInvoiceReminderRecipientService::class)
            ->eligibleAutomaticRecipients($invoice)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $overrides */
    private function createInvoice(array $overrides = []): SubscriptionInvoice
    {
        return $this->createInvoiceFor(
            $this->organization,
            $this->subscription,
            $this->plan,
            $overrides
        );
    }

    /** @param array<string, mixed> $overrides */
    private function createInvoiceFor(
        Organization $organization,
        OrganizationSubscription $subscription,
        SubscriptionPlan $plan,
        array $overrides = []
    ): SubscriptionInvoice {
        $this->invoiceSequence++;

        return SubscriptionInvoice::query()->create(array_merge([
            'organization_subscription_id' => $subscription->id,
            'organization_id' => $organization->id,
            'subscription_plan_id' => $plan->id,
            'invoice_number' => 'RECIPIENT-INVOICE-'.str_pad(
                (string) $this->invoiceSequence,
                4,
                '0',
                STR_PAD_LEFT
            ),
            'status' => SubscriptionInvoiceStatus::ISSUED,
            'issue_date' => now()->subMonth()->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
            'subtotal' => '100.00',
            'tax_amount' => '16.00',
            'total_amount' => '116.00',
            'currency' => 'USD',
            'notes' => 'Reminder recipient test invoice.',
            'issued_by_user_id' => null,
            'paid_at' => null,
            'voided_at' => null,
            'cancelled_at' => null,
        ], $overrides));
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
        $suffix = Str::lower(Str::random(8));

        $organization = Organization::query()->create([
            'name' => "Other Recipient Clinic {$suffix}",
            'slug' => "other-recipient-clinic-{$suffix}",
        ]);

        $billingOwner = $this->createUserFor($organization, [
            'name' => "Other Billing Owner {$suffix}",
            'email' => "other-billing-owner-{$suffix}@example.com",
        ]);

        $subscription = OrganizationSubscription::query()->create([
            'organization_id' => $organization->id,
            'subscription_plan_id' => $this->plan->id,
            'billing_owner_user_id' => $billingOwner->id,
            'status' => OrganizationSubscriptionStatus::ACTIVE,
            'payment_status' => SubscriptionPaymentStatus::PAID,
            'starts_at' => now()->subMonth(),
            'trial_ends_at' => null,
            'current_period_starts_at' => now()->subDay(),
            'current_period_ends_at' => now()->addMonth(),
            'cancel_at_period_end' => false,
            'cancelled_at' => null,
            'ends_at' => null,
        ]);

        return [
            'organization' => $organization,
            'billing_owner' => $billingOwner,
            'subscription' => $subscription,
            'plan' => $this->plan,
        ];
    }
}
