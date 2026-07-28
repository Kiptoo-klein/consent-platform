<?php

namespace Tests\Unit;

use App\Models\OrganizationSubscription;
use Tests\TestCase;

class OrganizationSubscriptionTest extends TestCase
{
    public function test_paid_subscription_allows_organization_access(): void
    {
        $subscription = new OrganizationSubscription([
            'payment_status' => 'paid',
        ]);

        $this->assertTrue(
            $subscription->allowsOrganizationAccess()
        );

        $this->assertFalse(
            $subscription->hasPlatformBypass()
        );
    }

    public function test_unpaid_subscription_without_bypass_blocks_access(): void
    {
        $subscription = new OrganizationSubscription([
            'payment_status' => 'unpaid',
        ]);

        $this->assertFalse(
            $subscription->allowsOrganizationAccess()
        );
    }

    public function test_incomplete_platform_bypass_blocks_access(): void
    {
        $missingApprover = new OrganizationSubscription([
            'payment_status' => 'unpaid',
            'bypass_approved_at' => '2026-07-28 17:45:00',
        ]);

        $missingApprovalTime = new OrganizationSubscription([
            'payment_status' => 'unpaid',
            'bypass_approved_by_user_id' => 1,
        ]);

        $this->assertFalse(
            $missingApprover->hasPlatformBypass()
        );

        $this->assertFalse(
            $missingApprover->allowsOrganizationAccess()
        );

        $this->assertFalse(
            $missingApprovalTime->hasPlatformBypass()
        );

        $this->assertFalse(
            $missingApprovalTime->allowsOrganizationAccess()
        );
    }

    public function test_complete_platform_bypass_allows_access_without_payment(): void
    {
        $subscription = new OrganizationSubscription([
            'payment_status' => 'unpaid',
            'bypass_approved_at' => '2026-07-28 17:45:00',
            'bypass_approved_by_user_id' => 1,
            'bypass_reason' => 'Approved by Platform Admin.',
        ]);

        $this->assertTrue(
            $subscription->hasPlatformBypass()
        );

        $this->assertTrue(
            $subscription->allowsOrganizationAccess()
        );
    }
}
