<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformRole extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * The users assigned to this platform role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
