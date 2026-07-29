<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_adjustable_subscription_plans(): void
    {
        $this->seed(SubscriptionPlanSeeder::class);

        $this->assertDatabaseCount('subscription_plans', 4);

        $expectedPlans = [
            'basic' => [5, 1, 2, 1, 1],
            'growth' => [15, 3, 8, 3, 5],
            'business' => [40, 8, 24, 7, 15],
            'enterprise' => [100, 20, 60, 19, 50],
        ];

        foreach ($expectedPlans as $slug => $limits) {
            [
                $users,
                $managers,
                $staff,
                $auditors,
                $kiosks,
            ] = $limits;

            $this->assertDatabaseHas('subscription_plans', [
                'slug' => $slug,
                'max_users' => $users,
                'max_consent_managers' => $managers,
                'max_staff' => $staff,
                'max_auditors' => $auditors,
                'max_active_kiosks' => $kiosks,
                'is_active' => true,
            ]);

            $this->assertSame(
                $users,
                1 + $managers + $staff + $auditors
            );
        }
    }

    public function test_reseeding_updates_plans_without_duplicates(): void
    {
        $this->seed(SubscriptionPlanSeeder::class);
        $this->seed(SubscriptionPlanSeeder::class);

        $this->assertSame(
            4,
            SubscriptionPlan::query()->count()
        );
    }
}
