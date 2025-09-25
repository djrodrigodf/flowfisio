<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    protected $fillable = [
        'specialty_id', 'treatment_type_id', 'name', 'slug', 'valor_base', 'active',
    ];

    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

    public function type()
    {
        return $this->belongsTo(TreatmentType::class, 'treatment_type_id');
    }

    public function prices()
    {
        return $this->hasMany(TreatmentPrice::class);
    }

    public function payouts()
    {
        return $this->hasMany(TreatmentPayout::class);
    }

    public function partners()
    {
        return $this->belongsToMany(Partner::class, 'partner_treatment')->withTimestamps();
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_treatment');
    }
}
