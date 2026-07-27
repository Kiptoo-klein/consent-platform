<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentPdfDelivery extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'consent_session_id',
        'recipient',
        'status',
        'attempts',
        'processing_at',
        'sent_at',
        'failed_at',
        'last_error',
        'metadata',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'processing_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function consentSession(): BelongsTo
    {
        return $this->belongsTo(ConsentSession::class);
    }
}
