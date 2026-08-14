<?php

namespace Tests\Feature;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionInvoiceStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\PlatformRole;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\SubscriptionInvoiceOverdueService;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class MarkSubscriptionInvoicesOverdueCommandTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private OrganizationSubscription $subscription;

    private SubscriptionPlan $plan;

    private User $platformAdmin;

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

        $this->seed([
            PlatformRoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        $this->plan = SubscriptionPlan::query()
            ->where('slug', 'basic')
            ->firstOrFail();

        $superAdminRole = PlatformRole::query()
            ->where('slug', 'super-admin')
            ->firstOrFail();

        $this->platformAdmin = User::factory()->create([
            'organization_id' => null,
            'platform_role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);

        $this->organization = Organization::query()->create([
            'name' => 'Overdue Invoice Clinic',
            'slug' => 'overdue-invoice-clinic',
        ]);

        $billingOwner = User::factory()->create([
            'organization_id' =>
                $this->organization->id,

            'platform_role_id' => null,
            'is_active' => true,
        ]);

        $this->subscription =
            OrganizationSubscription::query()->create([
                'organization_id' =>
                    $this->organization->id,

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

    public function test_command_marks_past_due_issued_invoice_overdue(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-AUTO-OVERDUE-0001',

            'due_date' =>
                now()->subDay()->toDateString(),
        ]);

        $this->artisan(
            'subscription-invoices:mark-overdue'
        )
            ->expectsOutput(
                'Marked 1 subscription invoice overdue.'
            )
            ->assertExitCode(0);

        $invoice = $invoice->fresh();

        $this->assertSame(
            SubscriptionInvoiceStatus::OVERDUE,
            $invoice->status
        );

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_invoice_marked_overdue'
            )
            ->sole();

        $this->assertNull($activity->user_id);

        $this->assertSame(
            $this->organization->id,
            $activity->organization_id
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
            'INV-AUTO-OVERDUE-0001',
            data_get(
                $activity->properties,
                'invoice_number'
            )
        );

        $this->assertSame(
            'scheduled_command',
            data_get(
                $activity->properties,
                'overdue_source'
            )
        );

        $this->assertSame(
            'issued',
            data_get(
                $activity->properties,
                'old.status'
            )
        );

        $this->assertSame(
            'overdue',
            data_get(
                $activity->properties,
                'new.status'
            )
        );

        $this->assertSame(
            now()->subDay()->toDateString(),
            data_get(
                $activity->properties,
                'due_date'
            )
        );

        $this->assertNotNull(
            data_get(
                $activity->properties,
                'marked_overdue_at'
            )
        );
    }

    public function test_invoice_due_today_is_not_overdue(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-DUE-TODAY',

            'due_date' =>
                now()->toDateString(),
        ]);

        $this->artisan(
            'subscription-invoices:mark-overdue'
        )
            ->expectsOutput(
                'Marked 0 subscription invoices overdue.'
            )
            ->assertExitCode(0);

        $this->assertSame(
            SubscriptionInvoiceStatus::ISSUED,
            $invoice->fresh()->status
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'organization.subscription_invoice_marked_overdue',

                'subject_id' =>
                    $invoice->id,
            ]
        );
    }

    public function test_future_invoice_is_not_marked_overdue(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-FUTURE-DUE-DATE',

            'due_date' =>
                now()
                    ->addDay()
                    ->toDateString(),
        ]);

        $this->artisan(
            'subscription-invoices:mark-overdue'
        )
            ->expectsOutput(
                'Marked 0 subscription invoices overdue.'
            )
            ->assertExitCode(0);

        $this->assertSame(
            SubscriptionInvoiceStatus::ISSUED,
            $invoice->fresh()->status
        );
    }

    public function test_non_issued_invoices_are_not_changed(): void
    {
        $invoices = [
            $this->createInvoice([
                'invoice_number' =>
                    'INV-DRAFT-PAST-DUE',

                'status' =>
                    SubscriptionInvoiceStatus::DRAFT,

                'issued_by_user_id' => null,
            ]),

            $this->createInvoice([
                'invoice_number' =>
                    'INV-ALREADY-OVERDUE',

                'status' =>
                    SubscriptionInvoiceStatus::OVERDUE,
            ]),

            $this->createInvoice([
                'invoice_number' =>
                    'INV-ALREADY-PAID',

                'status' =>
                    SubscriptionInvoiceStatus::PAID,

                'paid_at' =>
                    now()->subDay(),
            ]),

            $this->createInvoice([
                'invoice_number' =>
                    'INV-VOIDED',

                'status' =>
                    SubscriptionInvoiceStatus::VOIDED,

                'voided_at' =>
                    now()->subDay(),
            ]),

            $this->createInvoice([
                'invoice_number' =>
                    'INV-CANCELLED',

                'status' =>
                    SubscriptionInvoiceStatus::CANCELLED,

                'cancelled_at' =>
                    now()->subDay(),
            ]),
        ];

        $this->artisan(
            'subscription-invoices:mark-overdue'
        )
            ->expectsOutput(
                'Marked 0 subscription invoices overdue.'
            )
            ->assertExitCode(0);

        foreach ($invoices as $invoice) {
            $this->assertSame(
                $invoice->status,
                $invoice->fresh()->status
            );
        }

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'organization.subscription_invoice_marked_overdue',
            ]
        );
    }

    public function test_issued_invoice_without_due_date_is_not_changed(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-WITHOUT-DUE-DATE',

            'due_date' => null,
        ]);

        $this->artisan(
            'subscription-invoices:mark-overdue'
        )
            ->expectsOutput(
                'Marked 0 subscription invoices overdue.'
            )
            ->assertExitCode(0);

        $this->assertSame(
            SubscriptionInvoiceStatus::ISSUED,
            $invoice->fresh()->status
        );
    }

    public function test_chunk_option_processes_all_eligible_invoices(): void
    {
        $first = $this->createInvoice([
            'invoice_number' =>
                'INV-CHUNK-0001',
        ]);

        $second = $this->createInvoice([
            'invoice_number' =>
                'INV-CHUNK-0002',
        ]);

        $this->artisan(
            'subscription-invoices:mark-overdue',
            [
                '--chunk' => 1,
            ]
        )
            ->expectsOutput(
                'Marked 2 subscription invoices overdue.'
            )
            ->assertExitCode(0);

        $this->assertSame(
            SubscriptionInvoiceStatus::OVERDUE,
            $first->fresh()->status
        );

        $this->assertSame(
            SubscriptionInvoiceStatus::OVERDUE,
            $second->fresh()->status
        );

        $this->assertSame(
            2,
            ActivityLog::query()
                ->where(
                    'action',
                    'organization.subscription_invoice_marked_overdue'
                )
                ->count()
        );
    }

    public function test_repeated_command_runs_do_not_duplicate_activity(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-IDEMPOTENT-OVERDUE',
        ]);

        $this->artisan(
            'subscription-invoices:mark-overdue'
        )
            ->expectsOutput(
                'Marked 1 subscription invoice overdue.'
            )
            ->assertExitCode(0);

        $this->artisan(
            'subscription-invoices:mark-overdue'
        )
            ->expectsOutput(
                'Marked 0 subscription invoices overdue.'
            )
            ->assertExitCode(0);

        $this->assertSame(
            1,
            ActivityLog::query()
                ->where(
                    'action',
                    'organization.subscription_invoice_marked_overdue'
                )
                ->where(
                    'subject_id',
                    $invoice->id
                )
                ->count()
        );
    }

    public function test_status_change_rolls_back_when_audit_logging_fails(): void
    {
        $invoice = $this->createInvoice([
            'invoice_number' =>
                'INV-OVERDUE-ROLLBACK',
        ]);

        $this->mock(
            ActivityLogger::class,
            function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('log')
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            'Simulated invoice overdue audit failure.'
                        )
                    );
            }
        );

        $exceptionWasThrown = false;

        try {
            app(SubscriptionInvoiceOverdueService::class)
                ->markOverdueIfDue(
                    subscriptionInvoice:
                        $invoice,

                    source:
                        'scheduled_command'
                );
        } catch (RuntimeException $exception) {
            $exceptionWasThrown = true;

            $this->assertSame(
                'Simulated invoice overdue audit failure.',
                $exception->getMessage()
            );
        }

        $this->assertTrue(
            $exceptionWasThrown,
            'The simulated audit failure was not thrown.'
        );

        $this->assertSame(
            SubscriptionInvoiceStatus::ISSUED,
            $invoice->fresh()->status
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'organization.subscription_invoice_marked_overdue',

                'subject_id' =>
                    $invoice->id,
            ]
        );
    }

    public function test_invoice_overdue_command_is_scheduled(): void
    {
        $event = collect(
            app(Schedule::class)->events()
        )->first(
            fn ($scheduledEvent): bool =>
                str_contains(
                    $scheduledEvent->command,
                    'subscription-invoices:mark-overdue'
                )
        );

        $this->assertNotNull(
            $event,
            'The invoice overdue command is not scheduled.'
        );

        $this->assertSame(
            '15 2 * * *',
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
                        'INV-OVERDUE-'
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
                            ->subDays(14)
                            ->toDateString(),

                    'due_date' =>
                        now()
                            ->subDay()
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
                        'Automatically evaluated invoice.',

                    'issued_by_user_id' =>
                        $this->platformAdmin->id,

                    'paid_at' => null,
                    'voided_at' => null,
                    'cancelled_at' => null,
                ],
                $overrides
            )
        );
    }
}
