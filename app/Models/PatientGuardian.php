<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientGuardian extends Model
{
    protected $fillable = [
        'patient_id', 'name', 'kinship', 'birthdate', 'nationality', 'cpf', 'rg',
        'profession', 'phones', 'email', 'address', 'residence_type',
        'is_primary', 'is_financial', 'can_pick_up', 'notes',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'is_primary' => 'boolean',
        'is_financial' => 'boolean',
        'can_pick_up' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
