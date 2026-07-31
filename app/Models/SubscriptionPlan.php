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
        'monthly_price',
        'currency',
        'annual_billing_enabled',
        'annual_discount_percent',
        'max_users',
        'max_consent_managers',
        'max_staff',
        'max_auditors',
        'max_active_kiosks',
        'max_consent_templates',
        'max_signed_consents_per_period',
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
            'monthly_price' => 'decimal:2',
            'annual_billing_enabled' => 'boolean',
            'annual_discount_percent' => 'decimal:2',
            'max_users' => 'integer',
            'max_consent_managers' => 'integer',
            'max_staff' => 'integer',
            'max_auditors' => 'integer',
            'max_active_kiosks' => 'integer',
            'max_consent_templates' => 'integer',
            'max_signed_consents_per_period' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Calculate the discounted annual price.
     */
    public function annualPrice(): ?string
    {
        if (
            ! $this->annual_billing_enabled
            || $this->monthly_price === null
        ) {
            return null;
        }

        $fullAnnualPrice =
            (float) $this->monthly_price * 12;

        $discountRate =
            (float) $this->annual_discount_percent
            / 100;

        return number_format(
            round(
                $fullAnnualPrice
                * (1 - $discountRate),
                2
            ),
            2,
            '.',
            ''
        );
    }

    /**
     * Calculate the amount saved through annual billing.
     */
    public function annualSavings(): ?string
    {
        $annualPrice = $this->annualPrice();

        if ($annualPrice === null) {
            return null;
        }

        return number_format(
            round(
                ((float) $this->monthly_price * 12)
                - (float) $annualPrice,
                2
            ),
            2,
            '.',
            ''
        );
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
