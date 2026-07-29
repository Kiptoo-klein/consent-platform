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

    public function test_future_trial_allows_access_without_payment(): void
    {
        $subscription = new OrganizationSubscription([
            'status' =>
                OrganizationSubscriptionStatus::TRIALING,
            'payment_status' =>
                SubscriptionPaymentStatus::UNPAID,
            'trial_ends_at' => now()->addDay(),
        ]);

        $this->assertTrue(
            $subscription->allowsOrganizationAccess()
        );
    }

    public function test_expired_trial_blocks_access_without_payment(): void
    {
        $subscription = new OrganizationSubscription([
            'status' =>
                OrganizationSubscriptionStatus::TRIALING,
            'payment_status' =>
                SubscriptionPaymentStatus::UNPAID,
            'trial_ends_at' => now()->subMinute(),
        ]);

        $this->assertFalse(
            $subscription->allowsOrganizationAccess()
        );
    }

    public function test_paid_subscription_with_future_period_allows_access(): void
    {
        $subscription = new OrganizationSubscription([
            'status' =>
                OrganizationSubscriptionStatus::ACTIVE,
            'payment_status' =>
                SubscriptionPaymentStatus::PAID,
            'current_period_ends_at' => now()->addMonth(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->assertTrue(
            $subscription->allowsOrganizationAccess()
        );
    }

    public function test_paid_subscription_with_expired_period_blocks_access(): void
    {
        $subscription = new OrganizationSubscription([
            'status' =>
                OrganizationSubscriptionStatus::ACTIVE,
            'payment_status' =>
                SubscriptionPaymentStatus::PAID,
            'current_period_ends_at' => now()->subMinute(),
        ]);

        $this->assertFalse(
            $subscription->allowsOrganizationAccess()
        );
    }

    public function test_terminal_lifecycle_statuses_block_paid_access(): void
    {
        $blockedStatuses = [
            OrganizationSubscriptionStatus::PAST_DUE,
            OrganizationSubscriptionStatus::CANCELLED,
            OrganizationSubscriptionStatus::EXPIRED,
            OrganizationSubscriptionStatus::SUSPENDED,
        ];

        foreach ($blockedStatuses as $status) {
            $subscription = new OrganizationSubscription([
                'status' => $status,
                'payment_status' =>
                    SubscriptionPaymentStatus::PAID,
                'current_period_ends_at' => now()->addMonth(),
            ]);

            $this->assertFalse(
                $subscription->allowsOrganizationAccess(),
                "Status {$status->value} unexpectedly allowed access."
            );
        }
    }

    public function test_subscription_end_date_blocks_paid_access(): void
    {
        $subscription = new OrganizationSubscription([
            'status' =>
                OrganizationSubscriptionStatus::ACTIVE,
            'payment_status' =>
                SubscriptionPaymentStatus::PAID,
            'current_period_ends_at' => now()->addMonth(),
            'ends_at' => now()->subMinute(),
        ]);

        $this->assertFalse(
            $subscription->allowsOrganizationAccess()
        );
    }

    public function test_platform_bypass_overrides_expired_lifecycle(): void
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
            'status' =>
                OrganizationSubscriptionStatus::EXPIRED,
            'payment_status' =>
                SubscriptionPaymentStatus::FAILED,
            'current_period_ends_at' => now()->subDay(),
            'ends_at' => now()->subDay(),
            'bypass_approved_at' => now(),
            'bypass_approved_by_user_id' => $approver->id,
            'bypass_reason' =>
                'Temporary access after expiry.',
        ]);

        $this->assertTrue(
            $subscription->hasPlatformBypass()
        );

        $this->assertTrue(
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
