<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'location_id', 'name', 'code', 'capacity', 'active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'active' => 'boolean',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function scopeActive($q)
    {
        return $q->where('active', 1);
    }
}
