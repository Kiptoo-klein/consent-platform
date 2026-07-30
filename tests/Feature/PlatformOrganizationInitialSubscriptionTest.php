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
use Illuminate\Support\Str;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class PlatformOrganizationInitialSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private User $billingOwner;

    private User $inactiveUser;

    private User $platformAdmin;

    private SubscriptionPlan $basicPlan;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            '2026-07-30 13:30:00'
        );

        $this->seed([
            PlatformRoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        $identifier = Str::lower(
            Str::random(10)
        );

        $this->organization = Organization::query()->create([
            'name' =>
                'Initial Subscription Clinic',

            'slug' =>
                "initial-subscription-{$identifier}",
        ]);

        $this->billingOwner = User::factory()->create([
            'organization_id' =>
                $this->organization->id,

            'platform_role_id' =>
                null,

            'name' =>
                'Active Billing Owner',

            'email' =>
                "billing-{$identifier}@example.com",

            'is_active' =>
                true,
        ]);

        $this->inactiveUser = User::factory()->create([
            'organization_id' =>
                $this->organization->id,

            'platform_role_id' =>
                null,

            'name' =>
                'Inactive Billing Owner',

            'email' =>
                "inactive-{$identifier}@example.com",

            'is_active' =>
                false,
        ]);

        $superAdminRole = PlatformRole::query()
            ->where('slug', 'super-admin')
            ->firstOrFail();

        $this->platformAdmin = User::factory()->create([
            'organization_id' =>
                null,

            'platform_role_id' =>
                $superAdminRole->id,

            'is_active' =>
                true,
        ]);

        $this->basicPlan = SubscriptionPlan::query()
            ->where('slug', 'basic')
            ->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_platform_page_offers_initial_subscription_assignment(): void
    {
        $response = $this
            ->actingAs($this->platformAdmin)
            ->get(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            );

        $response
            ->assertOk()
            ->assertSeeText(
                'Create Subscription Record'
            )
            ->assertSeeText(
                'Basic'
            )
            ->assertSeeText(
                'Active Billing Owner'
            )
            ->assertSeeText(
                'Subscription start date'
            )
            ->assertSee(
                'name="starts_at"',
                false
            )
            ->assertSee(
                'value="2026-07-30"',
                false
            )
            ->assertSeeText(
                'automatically stored at midnight'
            )
            ->assertSee(
                'type="date"',
                false
            )
            ->assertDontSee(
                'type="datetime-local"',
                false
            )
            ->assertDontSeeText(
                'Inactive Billing Owner'
            )
            ->assertSee(
                route(
                    'platform.organizations.subscription.store',
                    $this->organization
                ),
                false
            );
    }

    public function test_platform_admin_can_create_initial_subscription(): void
    {
        $response = $this
            ->actingAs($this->platformAdmin)
            ->post(
                route(
                    'platform.organizations.subscription.store',
                    $this->organization
                ),
                [
                    'subscription_plan_id' =>
                        $this->basicPlan->id,

                    'billing_owner_user_id' =>
                        $this->billingOwner->id,

                    'starts_at' =>
                        '2026-07-25',

                    'trial_ends_at' =>
                        '2026-08-13',
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

        $subscription = $this->organization
            ->subscription()
            ->firstOrFail();

        $this->assertSame(
            $this->basicPlan->id,
            $subscription->subscription_plan_id
        );

        $this->assertSame(
            $this->billingOwner->id,
            $subscription->billing_owner_user_id
        );

        $this->assertSame(
            OrganizationSubscriptionStatus::TRIALING,
            $subscription->status
        );

        $this->assertSame(
            SubscriptionPaymentStatus::UNPAID,
            $subscription->payment_status
        );

        $this->assertTrue(
            $subscription
                ->starts_at
                ->equalTo(
                    Carbon::createFromFormat(
                        'Y-m-d',
                        '2026-07-25'
                    )->startOfDay()
                )
        );

        $this->assertTrue(
            $subscription
                ->trial_ends_at
                ->equalTo(
                    Carbon::createFromFormat(
                        'Y-m-d',
                        '2026-08-13'
                    )->startOfDay()
                )
        );

        $this->assertTrue(
            $subscription->allowsOrganizationAccess()
        );

        $activity = ActivityLog::query()
            ->where(
                'action',
                'organization.subscription_created'
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
            $subscription->id,
            $activity->subject_id
        );

        $this->assertSame(
            'Initial subscription assigned on the Basic plan.',
            $activity->description
        );

        $this
            ->actingAs($this->platformAdmin)
            ->get(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Activate Paid Subscription'
            )
            ->assertSeeText(
                'Activate Subscription'
            )
            ->assertDontSeeText(
                'Create Subscription Record'
            );
    }

    public function test_activation_dates_default_correctly(): void
    {
        $this
            ->actingAs($this->platformAdmin)
            ->post(
                route(
                    'platform.organizations.subscription.store',
                    $this->organization
                ),
                [
                    'subscription_plan_id' =>
                        $this->basicPlan->id,

                    'billing_owner_user_id' =>
                        $this->billingOwner->id,

                    'starts_at' =>
                        '2026-07-30',
                ]
            )
            ->assertRedirect(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            );

        $response = $this
            ->actingAs($this->platformAdmin)
            ->get(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            );

        $response->assertOk();

        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/id="current_period_starts_at".*?'
                .'value="30\/07\/2026"/s',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/id="current_period_ends_at".*?'
                .'value="30\/08\/2026"/s',
            $html
        );

        $this->assertMatchesRegularExpression(
            '/id="ends_at".*?value=""/s',
            $html
        );

        $response->assertSee(
            'placeholder="dd/mm/yyyy"',
            false
        );

        $response->assertSeeText(
            'Final subscription end (optional)'
        );
    }

    public function test_inactive_plan_and_invalid_billing_owner_are_rejected(): void
    {
        $inactivePlan = SubscriptionPlan::query()->create([
            'name' =>
                'Retired Initial Plan',

            'slug' =>
                'retired-initial-plan',

            'description' =>
                'Unavailable for assignment.',

            'max_users' =>
                2,

            'max_consent_managers' =>
                1,

            'max_staff' =>
                1,

            'max_auditors' =>
                1,

            'max_active_kiosks' =>
                1,

            'is_active' =>
                false,

            'sort_order' =>
                99,
        ]);

        $this
            ->actingAs($this->platformAdmin)
            ->post(
                route(
                    'platform.organizations.subscription.store',
                    $this->organization
                ),
                [
                    'subscription_plan_id' =>
                        $inactivePlan->id,

                    'billing_owner_user_id' =>
                        $this->inactiveUser->id,
                ]
            )
            ->assertSessionHasErrors([
                'subscription_plan_id',
                'billing_owner_user_id',
            ]);

        $this->assertDatabaseMissing(
            'organization_subscriptions',
            [
                'organization_id' =>
                    $this->organization->id,
            ]
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'organization.subscription_created',
            ]
        );
    }

    public function test_duplicate_initial_subscription_is_blocked(): void
    {
        $this->organization
            ->subscription()
            ->create([
                'subscription_plan_id' =>
                    $this->basicPlan->id,

                'billing_owner_user_id' =>
                    $this->billingOwner->id,

                'status' =>
                    OrganizationSubscriptionStatus::TRIALING,

                'payment_status' =>
                    SubscriptionPaymentStatus::UNPAID,

                'starts_at' =>
                    now(),
            ]);

        $this
            ->actingAs($this->platformAdmin)
            ->from(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            )
            ->post(
                route(
                    'platform.organizations.subscription.store',
                    $this->organization
                ),
                [
                    'subscription_plan_id' =>
                        $this->basicPlan->id,

                    'billing_owner_user_id' =>
                        $this->billingOwner->id,
                ]
            )
            ->assertRedirect(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            )
            ->assertSessionHasErrors(
                'subscription'
            );

        $this->assertSame(
            1,
            OrganizationSubscription::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->count()
        );
    }

    public function test_initial_subscription_rolls_back_when_audit_fails(): void
    {
        $activityCountBefore =
            ActivityLog::query()->count();

        $this->mock(
            ActivityLogger::class,
            function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('log')
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            'Simulated initial-subscription audit failure.'
                        )
                    );
            }
        );

        $this->withoutExceptionHandling();

        $exceptionWasThrown = false;

        try {
            $this
                ->actingAs($this->platformAdmin)
                ->post(
                    route(
                        'platform.organizations.subscription.store',
                        $this->organization
                    ),
                    [
                        'subscription_plan_id' =>
                            $this->basicPlan->id,

                        'billing_owner_user_id' =>
                            $this->billingOwner->id,
                    ]
                );
        } catch (RuntimeException $exception) {
            $exceptionWasThrown = true;

            $this->assertSame(
                'Simulated initial-subscription audit failure.',
                $exception->getMessage()
            );
        }

        $this->assertTrue(
            $exceptionWasThrown
        );

        $this->assertDatabaseMissing(
            'organization_subscriptions',
            [
                'organization_id' =>
                    $this->organization->id,
            ]
        );

        $this->assertSame(
            $activityCountBefore,
            ActivityLog::query()->count()
        );
    }
}
