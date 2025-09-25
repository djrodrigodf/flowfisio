<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleBlock extends Model
{
    protected $fillable = ['room_id', 'professional_id', 'starts_at', 'ends_at', 'reason'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }
}
