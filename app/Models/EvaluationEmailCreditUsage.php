<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationEmailCreditUsage extends Model
{
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_CONSUMED = 'consumed';
    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'organization_id',
        'consent_session_id',
        'consent_notification_id',
        'reservation_key',
        'notification_type',
        'status',
        'reserved_at',
        'reservation_expires_at',
        'consumed_at',
        'released_at',
        'release_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'reserved_at' => 'datetime',
            'reservation_expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'released_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class
        );
    }

    public function consentSession(): BelongsTo
    {
        return $this->belongsTo(
            ConsentSession::class
        );
    }

    public function consentNotification(): BelongsTo
    {
        return $this->belongsTo(
            ConsentNotification::class
        );
    }
}
