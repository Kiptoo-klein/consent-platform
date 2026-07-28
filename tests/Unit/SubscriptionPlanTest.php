<?php

namespace Tests\Unit;

use App\Models\SubscriptionPlan;
use Tests\TestCase;

class SubscriptionPlanTest extends TestCase
{
    public function test_active_kiosk_limit_is_assignable_and_cast_to_integer(): void
    {
        $plan = new SubscriptionPlan([
            'max_active_kiosks' => '5',
        ]);

        $this->assertContains(
            'max_active_kiosks',
            $plan->getFillable()
        );

        $this->assertSame(
            5,
            $plan->max_active_kiosks
        );
    }
}
