<?php

namespace App\Enums;

enum SubscriptionInvoiceStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case VOIDED = 'void';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::ISSUED => 'Issued',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
            self::VOIDED => 'Void',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function isOutstanding(): bool
    {
        return in_array(
            $this,
            [
                self::ISSUED,
                self::OVERDUE,
            ],
            true
        );
    }

    public function isTerminal(): bool
    {
        return in_array(
            $this,
            [
                self::PAID,
                self::VOIDED,
                self::CANCELLED,
            ],
            true
        );
    }
}
