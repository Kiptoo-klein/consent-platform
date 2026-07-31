<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SigningStationDeviceLease extends Model
{
    protected $fillable = [
        'lease_token',
        'organization_id',
        'signing_station_id',
        'device_key',
        'station_token_hash',
        'last_seen_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class
        );
    }

    public function signingStation(): BelongsTo
    {
        return $this->belongsTo(
            SigningStation::class
        );
    }
}
