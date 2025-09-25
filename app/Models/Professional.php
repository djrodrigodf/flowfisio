<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Professional extends Model
{
    protected $fillable = ['name', 'document', 'email', 'phone', 'active'];

    public function specialties()
    {
        return $this->belongsToMany(Specialty::class, 'professional_specialty');
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'professional_rooms');
    }

    public function schedules()
    {
        return $this->hasMany(ProfessionalSchedule::class);
    }

    public function timeoffs()
    {
        return $this->hasMany(ProfessionalTimeoff::class);
    }
}
