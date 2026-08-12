<?php

namespace Tests\Feature;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\PlatformRole;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\SubscriptionExpiryService;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class ExpireOrganizationSubscriptionsCommandTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SubscriptionPlanSeeder::class);

        $this->plan = SubscriptionPlan::query()
            ->where('slug', 'basic')
            ->firstOrFail();
    }

    public function test_command_expires_an_unpaid_trial(): void
    {
        $subscription = $this->createSubscription([
            'status' =>
                OrganizationSubscriptionStatus::TRIALING,
            'payment_status' =>
                SubscriptionPaymentStatus::UNPAID,
            'trial_ends_at' => now()->subMinute(),
            'current_period_starts_at' => null,
            'current_period_ends_at' => null,
        ]);

        $this->artisan('subscriptions:expire')
            ->expectsOutput(
                'Expired 1 organization subscription.'
            )
            ->assertExitCode(0);

        $subscription = $subscription->fresh();

        $this->assertSame(
            OrganizationSubscriptionStatus::EXPIRED,
            $subscription->status
        );

        $this->assertSame(
            SubscriptionPaymentStatus::UNPAID,
            $subscription->payment_status
        );

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_expired'
            )
            ->sole();

        $this->assertNull($activity->user_id);

        $this->assertSame(
            $subscription->organization_id,
            $activity->organization_id
        );

        $this->assertSame(
            $subscription->getMorphClass(),
            $activity->subject_type
        );

        $this->assertSame(
            $subscription->id,
            $activity->subject_id
        );

        $this->assertSame(
            'trial_ended',
            data_get(
                $activity->properties,
                'expiration_reason'
            )
        );

        $this->assertSame(
            'scheduled_command',
            data_get(
                $activity->properties,
                'expiration_source'
            )
        );

        $this->assertSame(
            'trialing',
            data_get($activity->properties, 'old.status')
        );

        $this->assertSame(
            'expired',
            data_get($activity->properties, 'new.status')
        );

        $this->assertSame(
            'unpaid',
            data_get(
                $activity->properties,
                'new.payment_status'
            )
        );
    }

    public function test_command_expires_a_paid_period(): void
    {
        $subscription = $this->createSubscription([
            'status' =>
                OrganizationSubscriptionStatus::ACTIVE,
            'payment_status' =>
                SubscriptionPaymentStatus::PAID,
            'current_period_starts_at' => now()->subMonth(),
            'current_period_ends_at' => now()->subMinute(),
        ]);

        $this->artisan('subscriptions:expire')
            ->expectsOutput(
                'Expired 1 organization subscription.'
            )
            ->assertExitCode(0);

        $subscription = $subscription->fresh();

        $this->assertSame(
            OrganizationSubscriptionStatus::EXPIRED,
            $subscription->status
        );

        $this->assertSame(
            SubscriptionPaymentStatus::PAST_DUE,
            $subscription->payment_status
        );

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_expired'
            )
            ->sole();

        $this->assertSame(
            'billing_period_ended',
            data_get(
                $activity->properties,
                'expiration_reason'
            )
        );

        $this->assertSame(
            'paid',
            data_get(
                $activity->properties,
                'old.payment_status'
            )
        );

        $this->assertSame(
            'past_due',
            data_get(
                $activity->properties,
                'new.payment_status'
            )
        );
    }

    public function test_command_expires_a_passed_final_end_date(): void
    {
        $subscription = $this->createSubscription([
            'status' =>
                OrganizationSubscriptionStatus::ACTIVE,
            'payment_status' =>
                SubscriptionPaymentStatus::PAID,
            'current_period_ends_at' => now()->addMonth(),
            'ends_at' => now()->subMinute(),
        ]);

        $this->artisan('subscriptions:expire')
            ->expectsOutput(
                'Expired 1 organization subscription.'
            )
            ->assertExitCode(0);

        $subscription = $subscription->fresh();

        $this->assertSame(
            OrganizationSubscriptionStatus::EXPIRED,
            $subscription->status
        );

        $this->assertSame(
            SubscriptionPaymentStatus::PAST_DUE,
            $subscription->payment_status
        );

        $this->assertDatabaseHas('activity_logs', [
            'organization_id' =>
                $subscription->organization_id,
            'action' =>
                'organization.subscription_expired',
            'subject_type' =>
                $subscription->getMorphClass(),
            'subject_id' => $subscription->id,
        ]);

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_expired'
            )
            ->sole();

        $this->assertSame(
            'subscription_ended',
            data_get(
                $activity->properties,
                'expiration_reason'
            )
        );
    }

    public function test_evaluation_subscription_is_not_expired(): void
    {
        $subscription = $this->createSubscription([
            'status' =>
                OrganizationSubscriptionStatus::EVALUATION,
            'payment_status' =>
                SubscriptionPaymentStatus::UNPAID,
            /*
             * Even a stale legacy trial date must not turn
             * Evaluation into a time-limited subscription.
             */
            'trial_ends_at' => now()->subDay(),
            'current_period_starts_at' => null,
            'current_period_ends_at' => null,
            'ends_at' => null,
        ]);

        $this->artisan('subscriptions:expire')
            ->expectsOutput(
                'Expired 0 organization subscriptions.'
            )
            ->assertExitCode(0);

        $subscription = $subscription->fresh();

        $this->assertSame(
            OrganizationSubscriptionStatus::EVALUATION,
            $subscription->status
        );

        $this->assertSame(
            SubscriptionPaymentStatus::UNPAID,
            $subscription->payment_status
        );

        $this->assertDatabaseMissing('activity_logs', [
            'organization_id' =>
                $subscription->organization_id,
            'action' =>
                'organization.subscription_expired',
        ]);
    }

    public function test_future_subscriptions_are_not_changed(): void
    {
        $subscription = $this->createSubscription([
            'status' =>
                OrganizationSubscriptionStatus::ACTIVE,
            'payment_status' =>
                SubscriptionPaymentStatus::PAID,
            'current_period_ends_at' => now()->addMonth(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->artisan('subscriptions:expire')
            ->expectsOutput(
                'Expired 0 organization subscriptions.'
            )
            ->assertExitCode(0);

        $subscription = $subscription->fresh();

        $this->assertSame(
            OrganizationSubscriptionStatus::ACTIVE,
            $subscription->status
        );

        $this->assertSame(
            SubscriptionPaymentStatus::PAID,
            $subscription->payment_status
        );

        $this->assertDatabaseMissing('activity_logs', [
            'organization_id' =>
                $subscription->organization_id,
            'action' =>
                'organization.subscription_expired',
        ]);
    }

    public function test_already_expired_subscriptions_are_idempotent(): void
    {
        $subscription = $this->createSubscription([
            'status' =>
                OrganizationSubscriptionStatus::EXPIRED,
            'payment_status' =>
                SubscriptionPaymentStatus::PAST_DUE,
            'current_period_ends_at' => now()->subDay(),
        ]);

        $this->artisan('subscriptions:expire')
            ->expectsOutput(
                'Expired 0 organization subscriptions.'
            )
            ->assertExitCode(0);

        $this->assertDatabaseMissing('activity_logs', [
            'organization_id' =>
                $subscription->organization_id,
            'action' =>
                'organization.subscription_expired',
        ]);
    }

    public function test_expiry_preserves_a_valid_platform_bypass(): void
    {
        $this->seed(PlatformRoleSeeder::class);

        $superAdminRole = PlatformRole::query()
            ->where('slug', 'super-admin')
            ->firstOrFail();

        $approver = User::factory()->create([
            'organization_id' => null,
            'platform_role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);

        $approvedAt = now()->subDay();

        $subscription = $this->createSubscription([
            'status' =>
                OrganizationSubscriptionStatus::ACTIVE,
            'payment_status' =>
                SubscriptionPaymentStatus::PAID,
            'current_period_ends_at' => now()->subMinute(),
            'bypass_approved_at' => $approvedAt,
            'bypass_approved_by_user_id' => $approver->id,
            'bypass_reason' =>
                'Temporary continuity approval.',
        ]);

        $this->artisan('subscriptions:expire')
            ->expectsOutput(
                'Expired 1 organization subscription.'
            )
            ->assertExitCode(0);

        $subscription = $subscription->fresh();

        $this->assertSame(
            OrganizationSubscriptionStatus::EXPIRED,
            $subscription->status
        );

        $this->assertSame(
            $approver->id,
            $subscription->bypass_approved_by_user_id
        );

        $this->assertSame(
            'Temporary continuity approval.',
            $subscription->bypass_reason
        );

        $this->assertTrue(
            $subscription->hasPlatformBypass()
        );

        $this->assertTrue(
            $subscription->allowsOrganizationAccess()
        );
    }

    public function test_repeated_command_runs_do_not_duplicate_activity(): void
    {
        $subscription = $this->createSubscription([
            'status' =>
                OrganizationSubscriptionStatus::TRIALING,
            'payment_status' =>
                SubscriptionPaymentStatus::UNPAID,
            'trial_ends_at' => now()->subMinute(),
            'current_period_starts_at' => null,
            'current_period_ends_at' => null,
        ]);

        $this->artisan('subscriptions:expire')
            ->expectsOutput(
                'Expired 1 organization subscription.'
            )
            ->assertExitCode(0);

        $this->artisan('subscriptions:expire')
            ->expectsOutput(
                'Expired 0 organization subscriptions.'
            )
            ->assertExitCode(0);

        $this->assertSame(
            1,
            ActivityLog::query()
                ->where(
                    'organization_id',
                    $subscription->organization_id
                )
                ->where(
                    'action',
                    'organization.subscription_expired'
                )
                ->count()
        );
    }

    public function test_expiry_rolls_back_when_audit_logging_fails(): void
    {
        $subscription = $this->createSubscription([
            'status' =>
                OrganizationSubscriptionStatus::ACTIVE,
            'payment_status' =>
                SubscriptionPaymentStatus::PAID,
            'current_period_ends_at' => now()->subMinute(),
        ]);

        $this->mock(
            ActivityLogger::class,
            function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('log')
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            'Simulated subscription audit failure.'
                        )
                    );
            }
        );

        $exceptionWasThrown = false;

        try {
            app(SubscriptionExpiryService::class)
                ->expireIfDue(
                    organizationSubscription: $subscription,
                    source: 'scheduled_command'
                );
        } catch (RuntimeException $exception) {
            $exceptionWasThrown = true;

            $this->assertSame(
                'Simulated subscription audit failure.',
                $exception->getMessage()
            );
        }

        $this->assertTrue(
            $exceptionWasThrown,
            'The simulated audit failure was not thrown.'
        );

        $subscription = $subscription->fresh();

        $this->assertSame(
            OrganizationSubscriptionStatus::ACTIVE,
            $subscription->status
        );

        $this->assertSame(
            SubscriptionPaymentStatus::PAID,
            $subscription->payment_status
        );

        $this->assertDatabaseMissing('activity_logs', [
            'organization_id' =>
                $subscription->organization_id,
            'action' =>
                'organization.subscription_expired',
        ]);
    }

    public function test_subscription_expiry_command_is_scheduled(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(
                fn ($scheduledEvent): bool =>
                    str_contains(
                        $scheduledEvent->command,
                        'subscriptions:expire'
                    )
            );

        $this->assertNotNull(
            $event,
            'The subscription expiry command is not scheduled.'
        );

        $this->assertSame(
            '* * * * *',
            $event->expression
        );
    }

    private function createSubscription(
        array $attributes = []
    ): OrganizationSubscription {
        $identifier = Str::lower(
            Str::random(12)
        );

        $organization = Organization::create([
            'name' => "Expiry Clinic {$identifier}",
            'slug' => "expiry-clinic-{$identifier}",
        ]);

        return $organization->subscription()->create(
            array_merge(
                [
                    'subscription_plan_id' =>
                        $this->plan->id,
                    'billing_owner_user_id' => null,
                    'status' =>
                        OrganizationSubscriptionStatus::ACTIVE,
                    'payment_status' =>
                        SubscriptionPaymentStatus::PAID,
                    'starts_at' => now()->subMonth(),
                    'trial_ends_at' => null,
                    'current_period_starts_at' =>
                        now()->subMonth(),
                    'current_period_ends_at' =>
                        now()->addMonth(),
                    'cancelled_at' => null,
                    'ends_at' => null,
                    'bypass_approved_at' => null,
                    'bypass_approved_by_user_id' => null,
                    'bypass_reason' => null,
                ],
                $attributes
            )
        );
    }
}
