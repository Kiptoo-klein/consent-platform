<?php

namespace Tests\Unit;

use App\Enums\SubscriptionPaymentStatus;
use PHPUnit\Framework\TestCase;

class SubscriptionPaymentStatusTest extends TestCase
{
    public function test_it_defines_supported_payment_statuses(): void
    {
        $this->assertSame(
            [
                'unpaid',
                'paid',
                'past_due',
                'failed',
                'refunded',
            ],
            array_map(
                static fn (
                    SubscriptionPaymentStatus $status
                ): string => $status->value,
                SubscriptionPaymentStatus::cases()
            )
        );
    }

    public function test_only_paid_status_allows_access_without_bypass(): void
    {
        foreach (SubscriptionPaymentStatus::cases() as $status) {
            $this->assertSame(
                $status === SubscriptionPaymentStatus::PAID,
                $status->allowsOrganizationAccess()
            );
        }
    }
}
