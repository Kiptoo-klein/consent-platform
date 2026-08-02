<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailQuotaAttempt extends Model
{
    public const PRIORITY_NORMAL = 'normal';

    public const PRIORITY_CRITICAL = 'critical';

    public const STATUS_RESERVED = 'reserved';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'category',
        'job_key',
        'priority',
        'status',
        'reserved_at',
        'sent_at',
        'failed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'reserved_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
