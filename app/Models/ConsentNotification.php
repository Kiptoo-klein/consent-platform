<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentNotification extends Model
{
    public const TYPE_INITIAL = 'initial';
    public const TYPE_RESEND = 'resend';
    public const TYPE_MANUAL_REMINDER = 'manual_reminder';
    public const TYPE_AUTOMATIC_REMINDER = 'automatic_reminder';

    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    public const TRIGGER_AUTOMATIC_CREATION = 'automatic_creation';
    public const TRIGGER_MANUAL = 'manual';
    public const TRIGGER_SCHEDULER = 'scheduler';

    protected $fillable = [
        'organization_id',
        'consent_session_id',
        'actor_user_id',
        'type',
        'trigger',
        'status',
        'recipient_email',
        'subject',
        'message',
        'days_before_deadline',
        'scheduled_for',
        'sent_at',
        'failed_at',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'days_before_deadline' => 'integer',
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function consentSession(): BelongsTo
    {
        return $this->belongsTo(ConsentSession::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
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
