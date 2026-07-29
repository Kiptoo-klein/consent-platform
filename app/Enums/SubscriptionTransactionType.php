<?php

namespace App\Enums;

enum SubscriptionTransactionType: string
{
    case PAYMENT = 'payment';
    case RENEWAL = 'renewal';
    case REFUND = 'refund';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::PAYMENT => 'Payment',
            self::RENEWAL => 'Renewal',
            self::REFUND => 'Refund',
            self::ADJUSTMENT => 'Adjustment',
        };
    }
}
