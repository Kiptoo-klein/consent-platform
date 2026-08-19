<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ConsentTemplate extends Model
{
    public const USAGE_INDIVIDUAL = 'individual';

    public const USAGE_SIGNING_STATION = 'signing_station';

    public const USAGE_BOTH = 'both';

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'category',
        'usage_type',
        'template_schema',
        'active_version_id',
        'has_unpublished_changes',
        'status',
        'evaluation_starter_key',
        'evaluation_retired_at',
    ];

    protected $casts = [
        'template_schema' => 'array',
        'has_unpublished_changes' => 'boolean',
        'evaluation_retired_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class
        );
    }

    public function versions(): HasMany
    {
        return $this->hasMany(
            ConsentTemplateVersion::class
        )->orderByDesc('version_number');
    }

    public function consentSessions(): HasMany
    {
        return $this->hasMany(
            ConsentSession::class
        );
    }

    public function signingStations(): HasMany
    {
        return $this->hasMany(
            SigningStation::class
        );
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(
            ConsentTemplateVersion::class
        )->ofMany('version_number', 'max');
    }

    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(
            ConsentTemplateVersion::class,
            'active_version_id'
        );
    }

    /**
     * Check whether this template may create a direct consent record.
     */
    public function supportsIndividualConsent(): bool
    {
        return true;
    }

    /**
     * Check whether this template may be assigned to a signing station.
     */
    public function supportsSigningStation(): bool
    {
        return true;
    }

    /**
     * Return the checkbox values used by the template form.
     */
    public function usageSelections(): array
    {
        $selections = [];

        if ($this->supportsIndividualConsent()) {
            $selections[] = self::USAGE_INDIVIDUAL;
        }

        if ($this->supportsSigningStation()) {
            $selections[] = self::USAGE_SIGNING_STATION;
        }

        return $selections;
    }

    /**
     * Return a user-facing label for the selected workflow.
     */
    public function usageLabel(): string
    {
        return match ($this->usage_type ?? self::USAGE_BOTH) {
            self::USAGE_INDIVIDUAL =>
                'Individual',

            self::USAGE_SIGNING_STATION =>
                'Signing station',

            default =>
                'Individual & station',
        };
    }

    public function isEvaluationStarter(): bool
    {
        return $this->evaluation_starter_key !== null;
    }

    public function isRetiredEvaluationStarter(): bool
    {
        return $this->evaluation_starter_key !== null
            && $this->evaluation_retired_at !== null;
    }

    public function isLive(): bool
    {
        return $this->active_version_id !== null
            && $this->status === 'published';
    }

    public function canPublish(): bool
    {
        return $this->status !== 'archived'
            && $this->has_unpublished_changes;
    }
}
