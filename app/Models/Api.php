<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Api extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'baseUrl',
        'is_active',
        'rateLimit',
        'created_by'
    ];

    protected $casts = [
        'rateLimit' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function endpoints(): HasMany
    {
        return $this->hasMany(Endpoint::class);
    }
}
