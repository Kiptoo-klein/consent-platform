<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentSignature extends Model
{
    use HasFactory;

    public const TYPE_DRAWN = 'drawn';

    public const TYPE_TYPED = 'typed';

    protected $fillable = [
        'consent_session_id',
        'signer_name',
        'signature_data',
        'signature_type',
        'signed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function consentSession(): BelongsTo
    {
        return $this->belongsTo(
            ConsentSession::class
        );
    }

    public function isSigned(): bool
    {
        return $this->signed_at !== null
            && filled($this->signature_data);
    }
}
