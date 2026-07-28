<?php

namespace Tests\Unit;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\OrganizationSubscription;
use App\Models\PlatformRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSubscriptionTest extends TestCase
{
    use RefreshDatabase;
    public function test_paid_subscription_allows_organization_access(): void
    {
        $subscription = new OrganizationSubscription([
            'payment_status' => 'paid',
        ]);

        $this->assertTrue(
            $subscription->allowsOrganizationAccess()
        );

        $this->assertSame(
            SubscriptionPaymentStatus::PAID,
            $subscription->payment_status
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
        $superAdminRole = PlatformRole::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'description' => 'Full platform access.',
        ]);

        $approver = User::factory()->create([
            'organization_id' => null,
            'platform_role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);

        $subscription = new OrganizationSubscription([
            'payment_status' => 'unpaid',
            'bypass_approved_at' => '2026-07-28 17:45:00',
            'bypass_approved_by_user_id' => $approver->id,
            'bypass_reason' => 'Approved by Platform Admin.',
        ]);

        $this->assertTrue(
            $subscription->hasPlatformBypass()
        );

        $this->assertTrue(
            $subscription->allowsOrganizationAccess()
        );
    }

    public function test_bypass_approved_by_non_super_admin_blocks_access(): void
    {
        $supportRole = PlatformRole::create([
            'name' => 'Support',
            'slug' => 'support',
            'description' => 'Customer support access.',
        ]);

        $approver = User::factory()->create([
            'organization_id' => null,
            'platform_role_id' => $supportRole->id,
            'is_active' => true,
        ]);

        $subscription = new OrganizationSubscription([
            'payment_status' => 'unpaid',
            'bypass_approved_at' => '2026-07-28 17:45:00',
            'bypass_approved_by_user_id' => $approver->id,
            'bypass_reason' => 'Invalid support approval.',
        ]);

        $this->assertFalse(
            $subscription->hasPlatformBypass()
        );

        $this->assertFalse(
            $subscription->allowsOrganizationAccess()
        );
    }

    public function test_bypass_approved_by_inactive_super_admin_blocks_access(): void
    {
        $superAdminRole = PlatformRole::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'description' => 'Full platform access.',
        ]);

        $approver = User::factory()->create([
            'organization_id' => null,
            'platform_role_id' => $superAdminRole->id,
            'is_active' => false,
        ]);

        $subscription = new OrganizationSubscription([
            'payment_status' => 'unpaid',
            'bypass_approved_at' => '2026-07-28 17:45:00',
            'bypass_approved_by_user_id' => $approver->id,
            'bypass_reason' => 'Approval is no longer valid.',
        ]);

        $this->assertFalse(
            $subscription->hasPlatformBypass()
        );

        $this->assertFalse(
            $subscription->allowsOrganizationAccess()
        );
    }

    public function test_subscription_status_is_cast_to_enum(): void
    {
        $subscription = new OrganizationSubscription([
            'status' => 'trialing',
        ]);

        $this->assertSame(
            OrganizationSubscriptionStatus::TRIALING,
            $subscription->status
        );
    }
}
