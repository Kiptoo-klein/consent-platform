<?php

namespace Tests\Feature;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionTransactionStatus;
use App\Enums\SubscriptionTransactionType;
use App\Models\ConsentTemplate;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\PlatformRole;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionTransaction;
use App\Models\User;
use Database\Seeders\PlatformRoleSeeder;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationPaidActivationGuardTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private OrganizationSubscription $subscription;

    private User $platformAdmin;

    private SubscriptionPlan $paidPlan;

    private int $initialPlanId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PlatformRoleSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

        $this->post('/register', [
            'organization_name' =>
                'Evaluation Activation Guard Clinic',

            'name' =>
                'Evaluation Administrator',

            'email' =>
                'evaluation-activation@example.com',

            'password' =>
                'StrongPass1!',

            'password_confirmation' =>
                'StrongPass1!',
        ])->assertRedirect(
            route('dashboard')
        );

        $this->organization =
            Organization::query()
                ->where(
                    'name',
                    'Evaluation Activation Guard Clinic'
                )
                ->firstOrFail();

        $this->subscription =
            $this->organization
                ->subscription()
                ->firstOrFail();

        $this->initialPlanId =
            (int) $this->subscription
                ->subscription_plan_id;

        $this->paidPlan =
            SubscriptionPlan::query()
                ->where('slug', 'growth')
                ->firstOrFail();

        $superAdminRole =
            PlatformRole::query()
                ->where('slug', 'super-admin')
                ->firstOrFail();

        $this->platformAdmin =
            User::factory()->create([
                'organization_id' =>
                    null,

                'platform_role_id' =>
                    $superAdminRole->id,

                'is_active' =>
                    true,
            ]);
    }

    public function test_platform_plan_update_cannot_bypass_evaluation_plan_selection(): void
    {
        $this
            ->actingAs($this->platformAdmin)
            ->patch(
                route(
                    'platform.organizations.subscription-plan.update',
                    $this->organization
                ),
                [
                    'subscription_plan_id' =>
                        $this->paidPlan->id,
                ]
            )
            ->assertStatus(409);

        $this->assertEvaluationStatePreserved();
    }

    public function test_platform_renewal_cannot_activate_free_evaluation(): void
    {
        $periodStart =
            now()
                ->addDay()
                ->startOfDay();

        $periodEnd =
            $periodStart
                ->copy()
                ->addMonth();

        $this
            ->actingAs($this->platformAdmin)
            ->patch(
                route(
                    'platform.organizations.subscription-renewal.update',
                    $this->organization
                ),
                [
                    'current_period_starts_at' =>
                        $periodStart->format('d/m/Y'),

                    'current_period_ends_at' =>
                        $periodEnd->format('d/m/Y'),

                    'ends_at' =>
                        null,
                ]
            )
            ->assertStatus(409);

        $this->assertEvaluationStatePreserved();
    }

    public function test_generic_successful_payment_cannot_activate_free_evaluation(): void
    {
        $reference =
            'EVAL-GENERIC-PAYMENT';

        $this
            ->actingAs($this->platformAdmin)
            ->post(
                route(
                    'platform.organizations.subscription-transactions.store',
                    $this->organization
                ),
                [
                    'reference' =>
                        $reference,

                    'type' =>
                        SubscriptionTransactionType::
                            PAYMENT->value,

                    'status' =>
                        SubscriptionTransactionStatus::
                            SUCCESSFUL->value,

                    'amount' =>
                        '100.00',

                    'currency' =>
                        'USD',

                    'payment_method' =>
                        'Card',

                    'paid_at' =>
                        now()
                            ->startOfMinute()
                            ->format('Y-m-d\TH:i'),

                    'notes' =>
                        'Must not bypass Evaluation.',
                ]
            )
            ->assertStatus(409);

        $this->assertDatabaseMissing(
            'subscription_transactions',
            [
                'reference' =>
                    $reference,
            ]
        );

        $this->assertEvaluationStatePreserved();
    }

    public function test_generic_successful_renewal_cannot_activate_free_evaluation(): void
    {
        $reference =
            'EVAL-GENERIC-RENEWAL';

        $periodStart =
            now()
                ->addDay()
                ->startOfMinute();

        $periodEnd =
            $periodStart
                ->copy()
                ->addMonth();

        $this
            ->actingAs($this->platformAdmin)
            ->post(
                route(
                    'platform.organizations.subscription-transactions.store',
                    $this->organization
                ),
                [
                    'reference' =>
                        $reference,

                    'type' =>
                        SubscriptionTransactionType::
                            RENEWAL->value,

                    'status' =>
                        SubscriptionTransactionStatus::
                            SUCCESSFUL->value,

                    'amount' =>
                        '100.00',

                    'currency' =>
                        'USD',

                    'payment_method' =>
                        'Card',

                    'paid_at' =>
                        now()
                            ->startOfMinute()
                            ->format('Y-m-d\TH:i'),

                    'period_starts_at' =>
                        $periodStart
                            ->format('Y-m-d\TH:i'),

                    'period_ends_at' =>
                        $periodEnd
                            ->format('Y-m-d\TH:i'),

                    'notes' =>
                        'Must not bypass Evaluation.',
                ]
            )
            ->assertStatus(409);

        $this->assertDatabaseMissing(
            'subscription_transactions',
            [
                'reference' =>
                    $reference,
            ]
        );

        $this->assertEvaluationStatePreserved();
    }

    public function test_platform_ui_replaces_direct_activation_forms_during_evaluation(): void
    {
        $this
            ->actingAs($this->platformAdmin)
            ->get(
                route(
                    'platform.organizations.show',
                    $this->organization
                )
            )
            ->assertOk()
            ->assertSee(
                'data-evaluation-plan-update-guard',
                false
            )
            ->assertSee(
                'data-evaluation-renewal-guard',
                false
            )
            ->assertSeeText(
                'Free Evaluation Upgrade'
            );

        $this
            ->actingAs($this->platformAdmin)
            ->get(
                route(
                    'platform.organizations.subscription-transactions.index',
                    $this->organization
                )
            )
            ->assertOk()
            ->assertSee(
                'data-evaluation-transaction-guard',
                false
            );
    }

    private function assertEvaluationStatePreserved(): void
    {
        $subscription =
            $this->subscription->fresh();

        $this->assertTrue(
            $subscription->isEvaluation()
        );

        $this->assertSame(
            OrganizationSubscriptionStatus::EVALUATION,
            $subscription->status
        );

        $this->assertSame(
            SubscriptionPaymentStatus::UNPAID,
            $subscription->payment_status
        );

        $this->assertTrue(
            $subscription->requires_plan_selection
        );

        $this->assertNull(
            $subscription->plan_selected_at
        );

        $this->assertNull(
            $subscription->current_period_starts_at
        );

        $this->assertNull(
            $subscription->current_period_ends_at
        );

        $this->assertSame(
            $this->initialPlanId,
            (int) $subscription
                ->subscription_plan_id
        );

        $this->assertSame(
            3,
            ConsentTemplate::query()
                ->where(
                    'organization_id',
                    $this->organization->id
                )
                ->whereNotNull(
                    'evaluation_starter_key'
                )
                ->whereNull(
                    'evaluation_retired_at'
                )
                ->count()
        );
    }
}
