<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformBrandingSetting extends Model
{
    public const SINGLETON_KEY =
        'default';

    protected $fillable = [
        'platform_name',
        'short_name',
        'tagline',
        'description',
        'logo_path',
        'favicon_path',
        'primary_color',
        'accent_color',
        'pdf_primary_color',
        'support_email',
        'website_url',
        'footer_text',
        'updated_by_user_id',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by_user_id'
        );
    }
}
