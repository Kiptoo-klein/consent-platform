<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    /**
     * Attributes that may be assigned through mass assignment.
     */
    protected $fillable = [
        'organization_id',
        'user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    /**
     * Cast structured log properties into an array automatically.
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    /**
     * Organization affected by this activity.
     *
     * Some platform-wide activities may not belong to an organization,
     * so this relationship can return null.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * User who performed the activity.
     *
     * This can return null for system-generated activity or when the
     * original user account has been permanently removed.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Model affected by the activity.
     *
     * Examples include a user, organization, consent form, patient,
     * consent session, or signature.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
