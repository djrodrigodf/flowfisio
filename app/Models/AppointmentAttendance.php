<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentAttendance extends Model
{
    protected $fillable = [
        'appointment_id', 'checked_in_at', 'mode', 'confirmed', 'confirmed_by', 'notes',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'confirmed' => 'boolean',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
