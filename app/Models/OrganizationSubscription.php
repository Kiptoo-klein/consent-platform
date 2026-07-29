<?php

namespace App\Models;

use App\Enums\OrganizationSubscriptionStatus;
use App\Enums\SubscriptionPaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSubscription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'subscription_plan_id',
        'billing_owner_user_id',
        'status',
        'payment_status',
        'starts_at',
        'trial_ends_at',
        'current_period_starts_at',
        'current_period_ends_at',
        'cancelled_at',
        'ends_at',
        'bypass_approved_at',
        'bypass_approved_by_user_id',
        'bypass_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrganizationSubscriptionStatus::class,
            'payment_status' => SubscriptionPaymentStatus::class,
            'starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'current_period_starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'ends_at' => 'datetime',
            'bypass_approved_at' => 'datetime',
        ];
    }

    /**
     * The organization that owns this subscription.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * The plan assigned to this subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            SubscriptionPlan::class,
            'subscription_plan_id'
        );
    }

    /**
     * The Organization Admin responsible for billing.
     */
    public function billingOwner(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'billing_owner_user_id'
        );
    }

    /**
     * The Platform Admin who granted the payment bypass.
     */
    public function bypassApprover(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'bypass_approved_by_user_id'
        );
    }

    /**
     * Determine whether a Platform Admin bypass is active.
     */
    public function hasPlatformBypass(): bool
    {
        if (
            $this->bypass_approved_at === null
            || $this->bypass_approved_by_user_id === null
        ) {
            return false;
        }

        $approver = $this->relationLoaded('bypassApprover')
            ? $this->getRelation('bypassApprover')
            : $this->bypassApprover()
                ->with('platformRole')
                ->first();

        if (
            $approver === null
            || $approver->is_active !== true
        ) {
            return false;
        }

        $approver->loadMissing('platformRole');

        return $approver->platformRole?->slug === 'super-admin';
    }

    /**
     * Determine whether users in the organization may access the system.
     *
     * A valid Platform Admin bypass overrides payment and lifecycle
     * restrictions. Otherwise, an organization must have either an
     * unexpired trial or a paid subscription with a valid lifecycle.
     */
    public function allowsOrganizationAccess(): bool
    {
        if ($this->hasPlatformBypass()) {
            return true;
        }

        if ($this->hasActiveTrial()) {
            return true;
        }

        if (
            $this->payment_status
                ?->allowsOrganizationAccess() !== true
        ) {
            return false;
        }

        if ($this->hasBlockingLifecycleStatus()) {
            return false;
        }

        if (
            $this->current_period_ends_at !== null
            && ! $this->current_period_ends_at->isFuture()
        ) {
            return false;
        }

        if (
            $this->ends_at !== null
            && ! $this->ends_at->isFuture()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether an unpaid trial is still valid.
     */
    private function hasActiveTrial(): bool
    {
        return $this->status
            === OrganizationSubscriptionStatus::TRIALING
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture();
    }

    /**
     * Determine whether the lifecycle status independently blocks access.
     */
    private function hasBlockingLifecycleStatus(): bool
    {
        return in_array(
            $this->status,
            [
                OrganizationSubscriptionStatus::PAST_DUE,
                OrganizationSubscriptionStatus::CANCELLED,
                OrganizationSubscriptionStatus::EXPIRED,
                OrganizationSubscriptionStatus::SUSPENDED,
            ],
            true
        );
    }
}
