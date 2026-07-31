<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ConsentSession extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'organization_id',
        'consent_template_id',
        'consent_template_version_id',
        'signing_station_id',
        'consent_campaign_id',
        'created_by',
        'signer_name',
        'signer_email',
        'signer_reference',
        'access_token',
        'status',
        'responses',
        'started_at',
        'completed_at',
        'cancelled_at',
        'expires_at',
        'expired_at',
    ];

    protected $casts = [
        'responses' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expires_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class
        );
    }

    public function consentTemplate(): BelongsTo
    {
        return $this->belongsTo(
            ConsentTemplate::class
        );
    }

    public function consentTemplateVersion(): BelongsTo
    {
        return $this->belongsTo(
            ConsentTemplateVersion::class
        );
    }

    public function consentCampaign(): BelongsTo
    {
        return $this->belongsTo(
            ConsentCampaign::class
        );
    }

    public function signingStation(): BelongsTo
    {
        return $this->belongsTo(
            SigningStation::class
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(
            ConsentNotification::class
        );
    }

    /**
     * Permanent audit events recorded for this consent session.
     */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(
            ConsentAuditEvent::class
        )->orderBy('created_at');
    }

    public function signature(): HasOne
    {
        return $this->hasOne(
            ConsentSignature::class
        );
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(
            ConsentSignature::class
        );
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function canExpire(): bool
    {
        return $this->expires_at !== null
            && in_array(
                $this->status,
                [
                    self::STATUS_PENDING,
                    self::STATUS_IN_PROGRESS,
                ],
                true
            );
    }

    public function isPastDue(): bool
    {
        return $this->canExpire()
            && $this->expires_at->isPast();
    }

    public function cameFromSigningStation(): bool
    {
        return $this->signing_station_id !== null;
    }
}
