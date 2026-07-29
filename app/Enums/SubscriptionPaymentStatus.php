<?php

namespace App\Enums;

enum SubscriptionPaymentStatus: string
{
    case UNPAID = 'unpaid';
    case PAID = 'paid';
    case PAST_DUE = 'past_due';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    /**
     * Determine whether payment independently allows
     * organization access.
     */
    public function allowsOrganizationAccess(): bool
    {
        return $this === self::PAID;
    }
}
