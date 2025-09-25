<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Patient extends Model
{
    protected $fillable = [
        'pre_registration_id',
        'name',
        'document',
        'sus',
        'birthdate',
        'gender',
        'nationality',
        'active',
        'email',
        'phone',
        'phone_alt',
        'zip_code',
        'address',
        'residence_type',
        'city',
        'state',
        'school',
        'insurance_id',
        'insurance_number',
        'insurance_valid_until',
        'has_other_clinic',
        'other_clinic_info',
        'care_type',
        'notes',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'insurance_valid_until' => 'date',
        'active' => 'boolean',
        'has_other_clinic' => 'boolean',
    ];

    public function insurance(): BelongsTo
    {
        return $this->belongsTo(Insurance::class);
    }

    public function preRegistration(): BelongsTo
    {
        return $this->belongsTo(PreRegistration::class);
    }
}
