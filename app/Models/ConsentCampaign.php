<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsentCampaign extends Model
{
    public const MAX_RECIPIENTS = 20;

    protected $fillable = [
        'organization_id',
        'consent_template_id',
        'consent_template_version_id',
        'created_by',
        'name',
        'recipient_count',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'recipient_count' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

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
}
