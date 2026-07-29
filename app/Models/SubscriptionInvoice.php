<?php

namespace App\Models;

use App\Enums\SubscriptionInvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionInvoice extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'organization_subscription_id',
        'organization_id',
        'subscription_plan_id',
        'invoice_number',
        'status',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'currency',
        'notes',
        'issued_by_user_id',
        'paid_at',
        'voided_at',
        'cancelled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionInvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
            'cancelled_at' => 'datetime',
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
        return $this->belongsTo(
            Organization::class
        );
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            SubscriptionPlan::class,
            'subscription_plan_id'
        );
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'issued_by_user_id'
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            SubscriptionTransaction::class,
            'subscription_invoice_id'
        );
    }

    public function reminderNotifications(): HasMany
    {
        return $this->hasMany(
            SubscriptionInvoiceNotification::class,
            'subscription_invoice_id'
        );
    }
}
