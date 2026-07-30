<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationInvoiceReminderPreference extends Model
{
    protected $fillable = [
        'organization_id',
        'before_due_reminders_enabled',
        'overdue_reminders_enabled',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'before_due_reminders_enabled' =>
                'boolean',

            'overdue_reminders_enabled' =>
                'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class
        );
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by_user_id'
        );
    }
}
