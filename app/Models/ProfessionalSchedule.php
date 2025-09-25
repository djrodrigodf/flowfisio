<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessionalSchedule extends Model
{
    protected $fillable = [
        'professional_id', 'weekday', 'start_time', 'end_time', 'slot_minutes', 'room_id', 'active',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'slot_minutes' => 'integer',
    ];

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
