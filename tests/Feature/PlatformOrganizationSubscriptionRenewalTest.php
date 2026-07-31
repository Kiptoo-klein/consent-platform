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
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class PlatformOrganizationSubscriptionRenewalTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $organizationAdmin;

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
            'organization_name' => 'Renewal Test Clinic',
            'name' => 'Renewal Organization Administrator',
            'email' => 'renewal-org-admin@example.com',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
        ]);

        $this->organization = Organization::query()
            ->where('name', 'Renewal Test Clinic')
            ->firstOrFail();

        $this->organizationAdmin = User::query()
            ->where(
                'email',
                'renewal-org-admin@example.com'
            )
            ->firstOrFail();

        $this->subscription = $this->organization
            ->subscription()
            ->firstOrFail();

        $this->plan = $this->subscription
            ->plan()
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
                OrganizationSubscriptionStatus::EXPIRED,

            'payment_status' =>
                SubscriptionPaymentStatus::PAST_DUE,

            'starts_at' =>
                now()->subMonths(2)->startOfMinute(),

            'trial_ends_at' =>
                now()->subMonth()->startOfMinute(),

            'current_period_starts_at' =>
                now()->subMonth()->startOfMinute(),

            'current_period_ends_at' =>
                now()->subDay()->startOfMinute(),

            'cancelled_at' =>
                now()->subDay()->startOfMinute(),

            'ends_at' => null,
        ]);
    }

    public function test_platform_admin_can_renew_an_expired_subscription(): void
    {
        User::factory()->create([
            'organization_id' => $this->organization->id,
            'platform_role_id' => null,
            'is_active' => true,
        ]);

        $userCountBefore =
            $this->organization->users()->count();

        $planIdBefore =
            $this->subscription->subscription_plan_id;

        $periodStart = now()
            ->addMinute()
            ->startOfMinute();

        $periodEnd = $periodStart
            ->copy()
            ->addMonth();

        $response = $this
            ->actingAs($this->platformAdmin)
            ->patch(
                $this->renewalUrl(),
                $this->renewalPayload(
                    $periodStart,
                    $periodEnd
                )
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

        $this->assertSame(
            $periodStart->toDateTimeString(),
            $subscription
                ->current_period_starts_at
                ->toDateTimeString()
        );

        $this->assertSame(
            $periodEnd->toDateTimeString(),
            $subscription
                ->current_period_ends_at
                ->toDateTimeString()
        );

        $this->assertNull($subscription->trial_ends_at);
        $this->assertNull($subscription->cancelled_at);
        $this->assertNull($subscription->ends_at);

        $this->assertSame(
            $planIdBefore,
            $subscription->subscription_plan_id
        );

        $this->assertSame(
            $userCountBefore,
            $this->organization->users()->count()
        );

        $this->assertTrue(
            $subscription->allowsOrganizationAccess()
        );

        $this
            ->actingAs($this->organizationAdmin)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_period_end_must_be_after_period_start(): void
    {
        $previous = $this->subscription->fresh();

        $periodStart = now()
            ->addMonth()
            ->startOfMinute();

        $periodEnd = $periodStart
            ->copy()
            ->subMinute();

        $response = $this
            ->actingAs($this->platformAdmin)
            ->from(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            )
            ->patch(
                $this->renewalUrl(),
                $this->renewalPayload(
                    $periodStart,
                    $periodEnd
                )
            );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors(
                'current_period_ends_at'
            );

        $subscription = $this->subscription->fresh();

        $this->assertSame(
            $previous->status,
            $subscription->status
        );

        $this->assertSame(
            $previous->payment_status,
            $subscription->payment_status
        );

        $this->assertDatabaseMissing('activity_logs', [
            'organization_id' => $this->organization->id,
            'action' =>
                'organization.subscription_renewed',
        ]);
    }

    public function test_final_end_must_not_precede_period_end(): void
    {
        $periodStart = now()
            ->addMinute()
            ->startOfMinute();

        $periodEnd = $periodStart
            ->copy()
            ->addMonth();

        $finalEnd = $periodEnd
            ->copy()
            ->subMinute();

        $response = $this
            ->actingAs($this->platformAdmin)
            ->patch(
                $this->renewalUrl(),
                $this->renewalPayload(
                    $periodStart,
                    $periodEnd,
                    $finalEnd
                )
            );

        $response->assertSessionHasErrors('ends_at');

        $this->assertSame(
            OrganizationSubscriptionStatus::EXPIRED,
            $this->subscription->fresh()->status
        );

        $this->assertDatabaseMissing('activity_logs', [
            'organization_id' => $this->organization->id,
            'action' =>
                'organization.subscription_renewed',
        ]);
    }

    public function test_organization_user_cannot_renew_subscription(): void
    {
        $periodStart = now()
            ->addMinute()
            ->startOfMinute();

        $periodEnd = $periodStart
            ->copy()
            ->addMonth();

        $this
            ->actingAs($this->organizationAdmin)
            ->patch(
                $this->renewalUrl(),
                $this->renewalPayload(
                    $periodStart,
                    $periodEnd
                )
            )
            ->assertForbidden();

        $this->assertSame(
            OrganizationSubscriptionStatus::EXPIRED,
            $this->subscription->fresh()->status
        );
    }

    public function test_renewal_records_old_and_new_values(): void
    {
        $oldSubscription = $this->subscription->fresh();

        $periodStart = now()
            ->addMinute()
            ->startOfMinute();

        $periodEnd = $periodStart
            ->copy()
            ->addMonth();

        $finalEnd = $periodEnd
            ->copy()
            ->addMonths(11);

        $this
            ->actingAs($this->platformAdmin)
            ->patch(
                $this->renewalUrl(),
                $this->renewalPayload(
                    $periodStart,
                    $periodEnd,
                    $finalEnd
                )
            )
            ->assertRedirect(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            );

        $subscription = $this->subscription->fresh();

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_renewed'
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
            $subscription->getMorphClass(),
            $activity->subject_type
        );

        $this->assertSame(
            $subscription->id,
            $activity->subject_id
        );

        $this->assertSame(
            'Organization subscription renewed.',
            $activity->description
        );

        $this->assertSame(
            $oldSubscription->status->value,
            data_get(
                $activity->properties,
                'old.status'
            )
        );

        $this->assertSame(
            $oldSubscription->payment_status->value,
            data_get(
                $activity->properties,
                'old.payment_status'
            )
        );

        $this->assertSame(
            $oldSubscription
                ->current_period_starts_at
                ->toIso8601String(),
            data_get(
                $activity->properties,
                'old.current_period_starts_at'
            )
        );

        $this->assertSame(
            $oldSubscription
                ->current_period_ends_at
                ->toIso8601String(),
            data_get(
                $activity->properties,
                'old.current_period_ends_at'
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
            'paid',
            data_get(
                $activity->properties,
                'new.payment_status'
            )
        );

        $this->assertSame(
            $subscription
                ->current_period_starts_at
                ->toIso8601String(),
            data_get(
                $activity->properties,
                'new.current_period_starts_at'
            )
        );

        $this->assertSame(
            $subscription
                ->current_period_ends_at
                ->toIso8601String(),
            data_get(
                $activity->properties,
                'new.current_period_ends_at'
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

    public function test_repeating_identical_renewal_creates_no_audit(): void
    {
        $periodStart = now()
            ->addMinute()
            ->startOfMinute();

        $periodEnd = $periodStart
            ->copy()
            ->addMonth();

        $this->subscription->update([
            'status' =>
                OrganizationSubscriptionStatus::ACTIVE,

            'payment_status' =>
                SubscriptionPaymentStatus::PAID,

            'trial_ends_at' => null,

            'current_period_starts_at' =>
                $periodStart,

            'current_period_ends_at' =>
                $periodEnd,

            'cancelled_at' => null,

            'ends_at' => null,
        ]);

        $this
            ->actingAs($this->platformAdmin)
            ->patch(
                $this->renewalUrl(),
                $this->renewalPayload(
                    $periodStart,
                    $periodEnd
                )
            )
            ->assertRedirect(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            )
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('activity_logs', [
            'organization_id' => $this->organization->id,
            'action' =>
                'organization.subscription_renewed',
        ]);
    }

    public function test_renewal_rolls_back_when_audit_logging_fails(): void
    {
        $previous = $this->subscription->fresh();

        $this->mock(
            ActivityLogger::class,
            function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('log')
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            'Simulated renewal audit failure.'
                        )
                    );
            }
        );

        $periodStart = now()
            ->addMinute()
            ->startOfMinute();

        $periodEnd = $periodStart
            ->copy()
            ->addMonth();

        $exceptionWasThrown = false;

        $this->withoutExceptionHandling();

        try {
            $this
                ->actingAs($this->platformAdmin)
                ->patch(
                    $this->renewalUrl(),
                    $this->renewalPayload(
                        $periodStart,
                        $periodEnd
                    )
                );
        } catch (RuntimeException $exception) {
            $exceptionWasThrown = true;

            $this->assertSame(
                'Simulated renewal audit failure.',
                $exception->getMessage()
            );
        }

        $this->assertTrue(
            $exceptionWasThrown,
            'The simulated audit failure was not thrown.'
        );

        $subscription = $this->subscription->fresh();

        $this->assertSame(
            $previous->status,
            $subscription->status
        );

        $this->assertSame(
            $previous->payment_status,
            $subscription->payment_status
        );

        $this->assertSame(
            $previous
                ->current_period_starts_at
                ->toDateTimeString(),
            $subscription
                ->current_period_starts_at
                ->toDateTimeString()
        );

        $this->assertSame(
            $previous
                ->current_period_ends_at
                ->toDateTimeString(),
            $subscription
                ->current_period_ends_at
                ->toDateTimeString()
        );

        $this->assertDatabaseMissing('activity_logs', [
            'organization_id' => $this->organization->id,
            'action' =>
                'organization.subscription_renewed',
        ]);
    }

    public function test_platform_page_displays_renewal_form(): void
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

        $response->assertSee(
            'id="current_period_starts_at_picker"',
            false
        );

        $response->assertSee(
            'id="current_period_ends_at_picker"',
            false
        );

        $response->assertSee(
            'id="ends_at_picker"',
            false
        );

        $html = $response->getContent();

        $this->assertStringNotContainsString(
            '>current_period_starts_at',
            $html
        );

        $this->assertStringNotContainsString(
            '>current_period_ends_at',
            $html
        );

        $this->assertStringNotContainsString(
            '>ends_at',
            $html
        );

        $this->assertStringNotContainsString(
            "?->format('Y-m-d\\TH:i')",
            $html
        );

        $this->assertMatchesRegularExpression(
            '/<input(?=[^>]*'
                .'id="current_period_starts_at")'
                .'(?=[^>]*value="[^"]+")'
                .'[^>]*>/s',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/<input(?=[^>]*'
                .'id="current_period_ends_at")'
                .'(?=[^>]*value="[^"]+")'
                .'[^>]*>/s',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/<input(?=[^>]*id="ends_at")'
                .'(?=[^>]*value="")'
                .'[^>]*>/s',
            $html
        );

        $response->assertSeeText('Renew Subscription');

        $response->assertSee(
            'name="current_period_starts_at"',
            false
        );

        $response->assertSee(
            'placeholder="dd/mm/yyyy"',
            false
        );

        $response->assertSeeText(
            'Final subscription end (optional)'
        );

        $response->assertDontSee(
            'type="datetime-local"',
            false
        );

        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/id="ends_at".*?value=""/s',
            $html
        );

        $response->assertSee(
            'name="current_period_ends_at"',
            false
        );

        $response->assertSee(
            'name="ends_at"',
            false
        );

        $response->assertSee(
            $this->renewalUrl(),
            false
        );
    }

    private function renewalUrl(): string
    {
        return "/platform/organizations/"
            ."{$this->organization->id}/subscription-renewal";
    }

    /**
     * @return array<string, string|null>
     */
    private function renewalPayload(
        Carbon $periodStart,
        Carbon $periodEnd,
        ?Carbon $finalEnd = null
    ): array {
        return [
            'current_period_starts_at' =>
                $periodStart->format('Y-m-d\TH:i'),

            'current_period_ends_at' =>
                $periodEnd->format('Y-m-d\TH:i'),

            'ends_at' =>
                $finalEnd?->format('Y-m-d\TH:i'),
        ];
    }
}
