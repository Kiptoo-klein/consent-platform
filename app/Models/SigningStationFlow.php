<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SigningStationFlow extends Model
{
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_DETAILS = 'details';
    public const STATUS_SIGNING = 'signing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ABANDONED = 'abandoned';

    public const STAGE_REVIEW = 'review';
    public const STAGE_DETAILS = 'details';
    public const STAGE_SIGNING = 'signing';
    public const STAGE_COMPLETED = 'completed';

    protected $fillable = [
        'flow_token',
        'organization_id',
        'signing_station_id',
        'consent_session_id',
        'status',
        'current_stage',
        'abandonment_reason',
        'started_at',
        'review_confirmed_at',
        'details_started_at',
        'consent_started_at',
        'completed_at',
        'cancelled_at',
        'abandoned_at',
        'last_activity_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'review_confirmed_at' => 'datetime',
            'details_started_at' => 'datetime',
            'consent_started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'abandoned_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function signingStation(): BelongsTo
    {
        return $this->belongsTo(SigningStation::class);
    }

    public function consentSession(): BelongsTo
    {
        return $this->belongsTo(ConsentSession::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_ABANDONED,
        ], true);
    }
}
