<?php

namespace Tests\Unit;

use App\Enums\OrganizationSubscriptionStatus;
use PHPUnit\Framework\TestCase;

class OrganizationSubscriptionStatusTest extends TestCase
{
    public function test_it_defines_supported_subscription_statuses(): void
    {
        $this->assertSame(
            [
                'trialing',
                'active',
                'past_due',
                'cancelled',
                'expired',
                'suspended',
            ],
            array_map(
                static fn (
                    OrganizationSubscriptionStatus $status
                ): string => $status->value,
                OrganizationSubscriptionStatus::cases()
            )
        );
    }
}
