<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientEmergencyContact extends Model
{
    protected $fillable = [
        'patient_id', 'name', 'relationship', 'phone', 'phone_alt', 'notes',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
