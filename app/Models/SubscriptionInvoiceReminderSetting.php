<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionInvoiceReminderSetting extends Model
{
    public const SINGLETON_KEY =
        'default';

    protected $fillable = [
        'automatic_reminders_enabled',
        'before_due_days',
        'overdue_days',
        'automatic_retry_minutes',
        'manual_retry_minutes',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'automatic_reminders_enabled' =>
                'boolean',

            'before_due_days' =>
                'array',

            'overdue_days' =>
                'array',

            'automatic_retry_minutes' =>
                'integer',

            'manual_retry_minutes' =>
                'integer',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by_user_id'
        );
    }
}
