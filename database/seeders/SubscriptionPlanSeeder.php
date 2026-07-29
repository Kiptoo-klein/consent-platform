<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' =>
                    'Essential consent workflows for small organizations.',
                'max_users' => 5,
                'max_consent_managers' => 1,
                'max_staff' => 2,
                'max_auditors' => 1,
                'max_active_kiosks' => 1,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'description' =>
                    'Expanded users and kiosks for growing organizations.',
                'max_users' => 15,
                'max_consent_managers' => 3,
                'max_staff' => 8,
                'max_auditors' => 3,
                'max_active_kiosks' => 5,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' =>
                    'Higher-capacity consent operations for established organizations.',
                'max_users' => 40,
                'max_consent_managers' => 8,
                'max_staff' => 24,
                'max_auditors' => 7,
                'max_active_kiosks' => 15,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' =>
                    'Large-scale consent workflows with extensive user and kiosk capacity.',
                'max_users' => 100,
                'max_consent_managers' => 20,
                'max_staff' => 60,
                'max_auditors' => 19,
                'max_active_kiosks' => 50,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
