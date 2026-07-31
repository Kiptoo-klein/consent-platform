<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPaymentSetting extends Model
{
    public const SINGLETON_KEY =
        'default';

    protected $fillable = [
        'mpesa_enabled',
        'mpesa_type',
        'mpesa_business_number',
        'mpesa_account_reference_instructions',
        'mpesa_instructions',
        'bank_enabled',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'bank_branch',
        'bank_swift_code',
        'bank_reference_instructions',
        'bank_instructions',
        'billing_contact_email',
        'billing_contact_phone',
        'additional_instructions',
        'updated_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mpesa_enabled' =>
                'boolean',

            'bank_enabled' =>
                'boolean',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by_user_id'
        );
    }
}
