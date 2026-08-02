<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionInvoiceNotification extends Model
{
    public const REMINDER_DUE_IN_3_DAYS =
        'due_in_3_days';

    public const REMINDER_DUE_IN_1_DAY =
        'due_in_1_day';

    public const REMINDER_OVERDUE_1_DAY =
        'overdue_1_day';

    public const REMINDER_OVERDUE_7_DAYS =
        'overdue_7_days';
    public const STATUS_QUEUED =
        'queued';


    public const STATUS_PROCESSING =
        'processing';

    public const STATUS_SENT =
        'sent';

    public const STATUS_FAILED =
        'failed';

    protected $fillable = [
        'organization_id',
        'subscription_invoice_id',
        'recipient_user_id',
        'retry_of_notification_id',
        'retry_requested_by_user_id',
        'reminder_key',
        'status',
        'recipient_email',
        'subject',
        'message',
        'scheduled_for',
        'sent_at',
        'failed_at',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(
            SubscriptionInvoice::class,
            'subscription_invoice_id'
        );
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class
        );
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'recipient_user_id'
        );
    }

    public function retryOf(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'retry_of_notification_id'
        );
    }

    public function retries(): HasMany
    {
        return $this->hasMany(
            self::class,
            'retry_of_notification_id'
        );
    }

    public function retryRequestedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'retry_requested_by_user_id'
        );
    }

    public function isQueued(): bool
    {
        return $this->status
            === self::STATUS_QUEUED;
    }

    public function isSent(): bool
    {
        return $this->status
            === self::STATUS_SENT;
    }

    public function isFailed(): bool
    {
        return $this->status
            === self::STATUS_FAILED;
    }
}
