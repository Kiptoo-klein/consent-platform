<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentTemplateVersion extends Model
{
    protected $fillable = [
        'consent_template_id',
        'version_number',
        'title',
        'description',
        'template_schema',
        'published_at',
        'published_by',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'template_schema' => 'array',
        'published_at' => 'datetime',
    ];

    /**
     * The parent consent template.
     */
    public function consentTemplate(): BelongsTo
    {
        return $this->belongsTo(
            ConsentTemplate::class
        );
    }

    /**
     * The user who published this version.
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'published_by'
        );
    }
}
