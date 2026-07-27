<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ConsentAuditEvent extends Model
{
    /**
     * The audit table has created_at but no updated_at column.
     */
    public const UPDATED_AT = null;

    /**
     * Fields that may be populated when recording an event.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'consent_session_id',
        'user_id',
        'event_type',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'organization_id' => 'integer',
            'consent_session_id' => 'integer',
            'user_id' => 'integer',
            'metadata' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * The organization that owns this audit event.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * The consent session associated with this event.
     */
    public function consentSession(): BelongsTo
    {
        return $this->belongsTo(ConsentSession::class);
    }

    /**
     * The authenticated organization user responsible for the event.
     *
     * This may be null for signer or automated system events.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Audit events are immutable after creation.
     */
    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException(
                'Consent audit events are append-only and cannot be updated.'
            );
        });

        static::deleting(function (): void {
            throw new LogicException(
                'Consent audit events are append-only and cannot be deleted.'
            );
        });
    }
}
