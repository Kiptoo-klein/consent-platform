<?php

namespace App\Models;

use App\Enums\SubscriptionTransactionStatus;
use App\Enums\SubscriptionTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionTransaction extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_subscription_id',
        'organization_id',
        'subscription_plan_id',
        'reference',
        'type',
        'status',
        'amount',
        'currency',
        'payment_method',
        'paid_at',
        'period_starts_at',
        'period_ends_at',
        'notes',
        'recorded_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SubscriptionTransactionType::class,
            'status' => SubscriptionTransactionStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'period_starts_at' => 'datetime',
            'period_ends_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(
            OrganizationSubscription::class,
            'organization_subscription_id'
        );
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            SubscriptionPlan::class,
            'subscription_plan_id'
        );
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'recorded_by_user_id'
        );
    }
}
