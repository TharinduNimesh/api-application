<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Endpoint extends Model
{
    protected $fillable = [
        'id',
        'name',
        'method',
        'path',
        'description'
    ];

    public function api(): BelongsTo
    {
        return $this->belongsTo(Api::class);
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(Parameter::class);
    }
}
