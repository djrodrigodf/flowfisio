<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentReschedule extends Model
{
    protected $fillable = [
        'appointment_id',
        'old_start_at', 'old_end_at', 'old_room_id',
        'new_start_at', 'new_end_at', 'new_room_id',
        'reason', 'user_id',
    ];

    protected $casts = [
        'old_start_at' => 'datetime',
        'old_end_at' => 'datetime',
        'new_start_at' => 'datetime',
        'new_end_at' => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
