<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'max_users',
        'max_consent_managers',
        'max_staff',
        'max_auditors',
        'max_active_kiosks',
        'is_active',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_users' => 'integer',
            'max_consent_managers' => 'integer',
            'max_staff' => 'integer',
            'max_auditors' => 'integer',
            'max_active_kiosks' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Organization subscriptions using this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(
            OrganizationSubscription::class
        );
    }
}
