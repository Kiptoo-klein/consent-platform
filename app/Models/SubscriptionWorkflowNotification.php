<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionWorkflowNotification extends Model
{
    public const EVENT_INVOICE_READY_CUSTOMER =
        'invoice_ready_customer';

    public const EVENT_PLAN_SELECTED_PLATFORM =
        'plan_selected_platform';

    public const EVENT_PAYMENT_REPORTED_PLATFORM =
        'payment_reported_platform';

    public const EVENT_REQUEST_CANCELLED_PLATFORM =
        'request_cancelled_platform';

    public const EVENT_PAYMENT_REJECTED_CUSTOMER =
        'payment_rejected_customer';

    public const EVENT_PAYMENT_RECEIPT_CUSTOMER =
        'payment_receipt_customer';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'organization_id',
        'subscription_invoice_id',
        'organization_subscription_plan_request_id',
        'subscription_transaction_id',
        'recipient_user_id',
        'event',
        'fingerprint',
        'status',
        'recipient_email',
        'subject',
        'message',
        'metadata',
        'sent_at',
        'failed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class
        );
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(
            SubscriptionInvoice::class,
            'subscription_invoice_id'
        );
    }

    public function planRequest(): BelongsTo
    {
        return $this->belongsTo(
            OrganizationSubscriptionPlanRequest::class,
            'organization_subscription_plan_request_id'
        );
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(
            SubscriptionTransaction::class,
            'subscription_transaction_id'
        );
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'recipient_user_id'
        );
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
