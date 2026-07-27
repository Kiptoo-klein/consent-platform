<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SigningStation extends Model
{
    protected $attributes = [
        'auto_reset_seconds' => 3,
    ];

    use HasFactory;

    protected $fillable = [
        'organization_id',
        'consent_template_id',
        'created_by',
        'name',
        'station_token',
        'active',
        'require_email',
        'require_reference',
        'auto_reset_seconds',
        'sender_name',
        'reply_to_email',
        'email_description',
    ];

    protected $casts = [
        'active' => 'boolean',
        'require_email' => 'boolean',
        'require_reference' => 'boolean',
        'auto_reset_seconds' => 'integer',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function consentSessions(): HasMany
    {
        return $this->hasMany(
            ConsentSession::class
        );
    }

    public function isAvailable(): bool
    {
        return $this->active
            && $this->consentTemplate?->isLive();
    }

    public function getPublicUrlAttribute(): string
    {
        return route(
            'public-signing-stations.show',
            $this->station_token
        );
    }
}
