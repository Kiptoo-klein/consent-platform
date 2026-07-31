<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class OrganizationSubscriptionPlanRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REJECTED = 'rejected';

    public const BILLING_CYCLE_MONTHLY = 'monthly';

    public const BILLING_CYCLE_ANNUAL = 'annual';

    protected $fillable = [
        'organization_id',
        'organization_subscription_id',
        'current_subscription_plan_id',
        'requested_subscription_plan_id',
        'requested_by_user_id',
        'subscription_invoice_id',
        'billing_cycle',
        'status',
        'monthly_price_snapshot',
        'annual_discount_percent_snapshot',
        'amount_snapshot',
        'currency',
        'requested_at',
        'resolved_at',
        'resolved_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price_snapshot' =>
                'decimal:2',

            'annual_discount_percent_snapshot' =>
                'decimal:2',

            'amount_snapshot' =>
                'decimal:2',

            'requested_at' =>
                'datetime',

            'resolved_at' =>
                'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class
        );
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(
            OrganizationSubscription::class,
            'organization_subscription_id'
        );
    }

    public function currentPlan(): BelongsTo
    {
        return $this->belongsTo(
            SubscriptionPlan::class,
            'current_subscription_plan_id'
        );
    }

    public function requestedPlan(): BelongsTo
    {
        return $this->belongsTo(
            SubscriptionPlan::class,
            'requested_subscription_plan_id'
        );
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'requested_by_user_id'
        );
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(
            SubscriptionInvoice::class,
            'subscription_invoice_id'
        );
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'resolved_by_user_id'
        );
    }

    /**
     * Calculate the subscription period end without overflowing
     * shorter calendar months or non-leap years.
     */
    public function periodEndFrom(
        CarbonInterface $periodStart
    ): CarbonImmutable {
        $start =
            CarbonImmutable::instance(
                $periodStart
            );

        return match ($this->billing_cycle) {
            self::BILLING_CYCLE_MONTHLY =>
                $start->addMonthNoOverflow(),

            self::BILLING_CYCLE_ANNUAL =>
                $start->addYearNoOverflow(),

            default =>
                throw new InvalidArgumentException(
                    'Unsupported subscription billing cycle.'
                ),
        };
    }

    /**
     * Determine whether this request is awaiting resolution.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Determine whether the organization may cancel this request directly.
     *
     * Direct cancellation is limited to unresolved pending requests.
     * Later workflow stages, such as an issued invoice or submitted payment,
     * will require Platform Billing review.
     */
    public function canBeCancelledByOrganization(): bool
    {
        return $this->isPending()
            && $this->resolved_at === null;
    }

}
