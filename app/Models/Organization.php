<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'website',
        'logo',
        'primary_color',
        'secondary_color',
        'accent_color',
        'pdf_primary_color',
        'pdf_accent_color',
        'support_email',
        'address',
        'footer_text',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    /**
     * Organization users.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Organization consent templates.
     */
    public function consentTemplates(): HasMany
    {
        return $this->hasMany(ConsentTemplate::class);
    }

    /**
     * The organization's current subscription.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(OrganizationSubscription::class);
    }

    /**
     * Determine whether organization access is currently archived.
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
}
