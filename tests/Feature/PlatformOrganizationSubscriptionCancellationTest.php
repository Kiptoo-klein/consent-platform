<?php

namespace Tests\Feature;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\ActivityLog;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\PlatformRole;
use App\Models\User;
use App\Services\ActivityLogger;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class PlatformOrganizationSubscriptionCancellationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $organizationAdmin;

    private User $platformAdmin;

    private OrganizationSubscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PlatformRoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        $this->post('/register', [
            'organization_name' =>
                'Lifecycle Management Clinic',

            'name' =>
                'Lifecycle Organization Administrator',

            'email' =>
                'lifecycle-org-admin@example.com',

            'password' =>
                'StrongPass1!',

            'password_confirmation' =>
                'StrongPass1!',
        ]);

        $this->organization = Organization::query()
            ->where(
                'name',
                'Lifecycle Management Clinic'
            )
            ->firstOrFail();

        $this->organizationAdmin = User::query()
            ->where(
                'email',
                'lifecycle-org-admin@example.com'
            )
            ->firstOrFail();

        $this->subscription = $this->organization
            ->subscription()
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
            'status' =>
                OrganizationSubscriptionStatus::ACTIVE,

            'payment_status' =>
                SubscriptionPaymentStatus::PAID,

            'starts_at' =>
                now()->subMonth()->startOfMinute(),

            'trial_ends_at' => null,

            'current_period_starts_at' =>
                now()->subDay()->startOfMinute(),

            'current_period_ends_at' =>
                now()->addMonth()->startOfMinute(),

            'cancelled_at' => null,

            'ends_at' => null,
        ]);
    }

    public function test_platform_admin_can_suspend_an_active_subscription(): void
    {
        User::factory()->create([
            'organization_id' => $this->organization->id,
            'platform_role_id' => null,
            'is_active' => true,
        ]);

        $before = $this->subscription->fresh();

        $userCountBefore =
            $this->organization->users()->count();

        $response = $this
            ->actingAs($this->platformAdmin)
            ->patch(
                $this->suspensionUrl(),
                [
                    'reason' =>
                        'Temporary compliance investigation.',
                ]
            );

        $response
            ->assertRedirect(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            )
            ->assertSessionHas('success');

        $subscription = $this->subscription->fresh();

        $this->assertSame(
            OrganizationSubscriptionStatus::SUSPENDED,
            $subscription->status
        );

        $this->assertSame(
            SubscriptionPaymentStatus::PAID,
            $subscription->payment_status
        );

        $this->assertSame(
            $before->subscription_plan_id,
            $subscription->subscription_plan_id
        );

        $this->assertSame(
            $before
                ->current_period_ends_at
                ->toDateTimeString(),
            $subscription
                ->current_period_ends_at
                ->toDateTimeString()
        );

        $this->assertSame(
            $userCountBefore,
            $this->organization->users()->count()
        );

        $this->assertFalse(
            $subscription->allowsOrganizationAccess()
        );

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_suspended'
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
            'active',
            data_get(
                $activity->properties,
                'old.status'
            )
        );

        $this->assertSame(
            'suspended',
            data_get(
                $activity->properties,
                'new.status'
            )
        );

        $this->assertSame(
            'Temporary compliance investigation.',
            data_get(
                $activity->properties,
                'reason'
            )
        );
    }

    public function test_repeated_suspension_does_not_duplicate_audit_activity(): void
    {
        $payload = [
            'reason' =>
                'Temporary compliance investigation.',
        ];

        $this
            ->actingAs($this->platformAdmin)
            ->patch(
                $this->suspensionUrl(),
                $payload
            )
            ->assertRedirect();

        $this
            ->actingAs($this->platformAdmin)
            ->patch(
                $this->suspensionUrl(),
                $payload
            )
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(
            1,
            ActivityLog::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->where(
                    'action',
                    'organization.subscription_suspended'
                )
                ->count()
        );
    }

    public function test_platform_admin_can_resume_a_valid_suspended_subscription(): void
    {
        $this->subscription->update([
            'status' =>
                OrganizationSubscriptionStatus::SUSPENDED,
        ]);

        $response = $this
            ->actingAs($this->platformAdmin)
            ->delete(
                $this->suspensionUrl()
            );

        $response
            ->assertRedirect(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            )
            ->assertSessionHas('success');

        $subscription = $this->subscription->fresh();

        $this->assertSame(
            OrganizationSubscriptionStatus::ACTIVE,
            $subscription->status
        );

        $this->assertSame(
            SubscriptionPaymentStatus::PAID,
            $subscription->payment_status
        );

        $this->assertTrue(
            $subscription->allowsOrganizationAccess()
        );

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_resumed'
            )
            ->sole();

        $this->assertSame(
            'suspended',
            data_get(
                $activity->properties,
                'old.status'
            )
        );

        $this->assertSame(
            'active',
            data_get(
                $activity->properties,
                'new.status'
            )
        );
    }

    public function test_unpaid_suspended_subscription_cannot_be_resumed(): void
    {
        $this->subscription->update([
            'status' =>
                OrganizationSubscriptionStatus::SUSPENDED,

            'payment_status' =>
                SubscriptionPaymentStatus::UNPAID,
        ]);

        $response = $this
            ->actingAs($this->platformAdmin)
            ->from(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            )
            ->delete(
                $this->suspensionUrl()
            );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('subscription');

        $this->assertSame(
            OrganizationSubscriptionStatus::SUSPENDED,
            $this->subscription->fresh()->status
        );

        $this->assertDatabaseMissing('activity_logs', [
            'organization_id' =>
                $this->organization->id,

            'action' =>
                'organization.subscription_resumed',
        ]);
    }

    public function test_suspended_subscription_with_expired_period_cannot_be_resumed(): void
    {
        $this->subscription->update([
            'status' =>
                OrganizationSubscriptionStatus::SUSPENDED,

            'payment_status' =>
                SubscriptionPaymentStatus::PAID,

            'current_period_ends_at' =>
                now()->subMinute(),
        ]);

        $response = $this
            ->actingAs($this->platformAdmin)
            ->from(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            )
            ->delete(
                $this->suspensionUrl()
            );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('subscription');

        $this->assertSame(
            OrganizationSubscriptionStatus::SUSPENDED,
            $this->subscription->fresh()->status
        );

        $this->assertDatabaseMissing('activity_logs', [
            'organization_id' =>
                $this->organization->id,

            'action' =>
                'organization.subscription_resumed',
        ]);
    }

    public function test_platform_admin_can_cancel_subscription_immediately(): void
    {
        $response = $this
            ->actingAs($this->platformAdmin)
            ->patch(
                $this->cancellationUrl(),
                [
                    'mode' => 'immediate',

                    'reason' =>
                        'Customer requested immediate closure.',
                ]
            );

        $response
            ->assertRedirect(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            )
            ->assertSessionHas('success');

        $subscription = $this->subscription->fresh();

        $this->assertSame(
            OrganizationSubscriptionStatus::CANCELLED,
            $subscription->status
        );

        $this->assertSame(
            SubscriptionPaymentStatus::PAID,
            $subscription->payment_status
        );

        $this->assertNotNull(
            $subscription->cancelled_at
        );

        $this->assertNotNull(
            $subscription->ends_at
        );

        $this->assertFalse(
            $subscription->allowsOrganizationAccess()
        );

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_cancelled'
            )
            ->sole();

        $this->assertSame(
            'immediate',
            data_get(
                $activity->properties,
                'cancellation_mode'
            )
        );

        $this->assertSame(
            'active',
            data_get(
                $activity->properties,
                'old.status'
            )
        );

        $this->assertSame(
            'cancelled',
            data_get(
                $activity->properties,
                'new.status'
            )
        );

        $this->assertSame(
            'Customer requested immediate closure.',
            data_get(
                $activity->properties,
                'reason'
            )
        );
    }

    public function test_platform_admin_can_schedule_cancellation_at_period_end(): void
    {
        $periodEnd = $this->subscription
            ->current_period_ends_at
            ->copy();

        $response = $this
            ->actingAs($this->platformAdmin)
            ->patch(
                $this->cancellationUrl(),
                [
                    'mode' => 'period_end',

                    'reason' =>
                        'Customer will finish the paid period.',
                ]
            );

        $response
            ->assertRedirect(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            )
            ->assertSessionHas('success');

        $subscription = $this->subscription->fresh();

        $this->assertSame(
            OrganizationSubscriptionStatus::ACTIVE,
            $subscription->status
        );

        $this->assertSame(
            SubscriptionPaymentStatus::PAID,
            $subscription->payment_status
        );

        $this->assertNotNull(
            $subscription->cancelled_at
        );

        $this->assertSame(
            $periodEnd->toDateTimeString(),
            $subscription->ends_at->toDateTimeString()
        );

        $this->assertTrue(
            $subscription->allowsOrganizationAccess()
        );

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_cancellation_scheduled'
            )
            ->sole();

        $this->assertSame(
            'period_end',
            data_get(
                $activity->properties,
                'cancellation_mode'
            )
        );

        $this->assertSame(
            'active',
            data_get(
                $activity->properties,
                'new.status'
            )
        );

        $this->assertSame(
            $subscription
                ->ends_at
                ->toIso8601String(),
            data_get(
                $activity->properties,
                'new.ends_at'
            )
        );
    }

    public function test_invalid_cancellation_request_is_rejected(): void
    {
        $response = $this
            ->actingAs($this->platformAdmin)
            ->from(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            )
            ->patch(
                $this->cancellationUrl(),
                [
                    'mode' => 'unsupported',
                    'reason' => '',
                ]
            );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors([
                'mode',
                'reason',
            ]);

        $this->assertSame(
            OrganizationSubscriptionStatus::ACTIVE,
            $this->subscription->fresh()->status
        );

        $this->assertDatabaseMissing('activity_logs', [
            'organization_id' =>
                $this->organization->id,

            'action' =>
                'organization.subscription_cancelled',
        ]);
    }

    public function test_organization_user_cannot_manage_subscription_lifecycle(): void
    {
        $this
            ->actingAs($this->organizationAdmin)
            ->patch(
                $this->suspensionUrl(),
                [
                    'reason' =>
                        'Unauthorized suspension attempt.',
                ]
            )
            ->assertForbidden();

        $this
            ->actingAs($this->organizationAdmin)
            ->patch(
                $this->cancellationUrl(),
                [
                    'mode' => 'immediate',
                    'reason' =>
                        'Unauthorized cancellation attempt.',
                ]
            )
            ->assertForbidden();

        $this->assertSame(
            OrganizationSubscriptionStatus::ACTIVE,
            $this->subscription->fresh()->status
        );
    }

    public function test_cancellation_preserves_valid_platform_bypass(): void
    {
        $this->subscription->update([
            'bypass_approved_at' =>
                now()->subDay(),

            'bypass_approved_by_user_id' =>
                $this->platformAdmin->id,

            'bypass_reason' =>
                'Temporary continuity approval.',
        ]);

        $this
            ->actingAs($this->platformAdmin)
            ->patch(
                $this->cancellationUrl(),
                [
                    'mode' => 'immediate',

                    'reason' =>
                        'Administrative cancellation.',
                ]
            )
            ->assertRedirect();

        $subscription = $this->subscription->fresh();

        $this->assertSame(
            OrganizationSubscriptionStatus::CANCELLED,
            $subscription->status
        );

        $this->assertSame(
            $this->platformAdmin->id,
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

    public function test_cancellation_rolls_back_when_audit_logging_fails(): void
    {
        $before = $this->subscription->fresh();

        $this->mock(
            ActivityLogger::class,
            function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('log')
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            'Simulated cancellation audit failure.'
                        )
                    );
            }
        );

        $exceptionWasThrown = false;

        $this->withoutExceptionHandling();

        try {
            $this
                ->actingAs($this->platformAdmin)
                ->patch(
                    $this->cancellationUrl(),
                    [
                        'mode' => 'immediate',

                        'reason' =>
                            'Cancellation rollback test.',
                    ]
                );
        } catch (RuntimeException $exception) {
            $exceptionWasThrown = true;

            $this->assertSame(
                'Simulated cancellation audit failure.',
                $exception->getMessage()
            );
        }

        $this->assertTrue(
            $exceptionWasThrown,
            'The simulated audit failure was not thrown.'
        );

        $subscription = $this->subscription->fresh();

        $this->assertSame(
            $before->status,
            $subscription->status
        );

        $this->assertSame(
            $before->payment_status,
            $subscription->payment_status
        );

        $this->assertNull(
            $subscription->cancelled_at
        );

        $this->assertNull(
            $subscription->ends_at
        );

        $this->assertDatabaseMissing('activity_logs', [
            'organization_id' =>
                $this->organization->id,

            'action' =>
                'organization.subscription_cancelled',
        ]);
    }

    public function test_platform_page_displays_lifecycle_controls(): void
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
            'Subscription Lifecycle'
        );

        $response->assertSeeText(
            'Suspend Subscription'
        );

        $response->assertSeeText(
            'Cancel Subscription'
        );

        $response->assertSee(
            $this->suspensionUrl(),
            false
        );

        $response->assertSee(
            $this->cancellationUrl(),
            false
        );

        $response->assertSee(
            'name="mode"',
            false
        );

        $response->assertSee(
            'value="immediate"',
            false
        );

        $response->assertSee(
            'value="period_end"',
            false
        );
    }

    private function suspensionUrl(): string
    {
        return "/platform/organizations/"
            ."{$this->organization->id}/subscription-suspension";
    }

    private function cancellationUrl(): string
    {
        return "/platform/organizations/"
            ."{$this->organization->id}/subscription-cancellation";
    }
}
