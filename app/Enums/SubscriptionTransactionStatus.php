<?php

namespace App\Enums;

enum SubscriptionTransactionStatus: string
{
    case PENDING = 'pending';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SUCCESSFUL => 'Successful',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::SUCCESSFUL;
    }
}
