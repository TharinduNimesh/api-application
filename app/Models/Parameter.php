<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Parameter extends Model
{
    protected $fillable = [
        'id',
        'name',
        'type',
        'location',
        'required',
        'description',
        'defaultValue',
        'fileConfig'
    ];

    protected $casts = [
        'required' => 'boolean',
        'fileConfig' => 'array'
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }
}
