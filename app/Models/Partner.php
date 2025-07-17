<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Models\Role;

class Partner extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $fillable = [
        'name',
        'role_id',
        'phone',
        'birth_date',
        'email',
        'cpf',
        'photo_path',
        'notes',
        'is_anamnese',
    ];

    protected $with = ['role'];
    protected $casts = [
        'birth_date' => 'date',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function schedules()
    {
        return $this->hasMany(PartnerSchedule::class, 'partner_id')
            ->orderByRaw("FIELD(day_of_week, 'monday','tuesday','wednesday','thursday','friday','saturday','sunday')");
    }

    public function getProfilePhotoUrlAttribute()
    {
        return $this->getFirstMediaUrl('profile') ?: null;
    }
}
