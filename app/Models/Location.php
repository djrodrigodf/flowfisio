<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name', 'code', 'address', 'city', 'state', 'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function partners()
    {
        return $this->belongsToMany(Partner::class, 'partner_location')->withTimestamps();
    }

    public function scopeActive($q)
    {
        return $q->where('active', 1);
    }
}
