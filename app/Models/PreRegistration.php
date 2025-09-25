<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PreRegistration extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $casts = [
        'child_birthdate' => 'date',
    ];

    protected $fillable = [
        'pre_registration_link_id',
        'child_name',
        'child_birthdate',
        'child_gender',
        'child_cpf',
        'child_sus',
        'child_nationality',
        'child_address',
        'child_residence_type',
        'child_phone',
        'child_cellphone',
        'child_school',
        'has_other_clinic',
        'other_clinic_info',
        'care_type',
        'responsible_name',
        'responsible_kinship',
        'responsible_birthdate',
        'responsible_nationality',
        'responsible_cpf',
        'responsible_rg',
        'responsible_profession',
        'responsible_phones',
        'responsible_email',
        'responsible_address',
        'responsible_residence_type',
        'authorized_to_pick_up',
        'is_financial_responsible',
        'status',
        'scheduled_at',
        'scheduled_by',
        'professional_id',
        'anamnese_transcricao',
        'anamnese_gerada',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(PreRegistrationLink::class, 'pre_registration_link_id');
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(PreRegistrationEmergencyContact::class);
    }

    public function additionalResponsibles(): HasMany
    {
        return $this->hasMany(PreRegistrationAdditionalResponsible::class); // Nome da model ajustado
    }

    public function appointment(): HasMany
    {
        return $this->hasMany(PreAppointment::class);
    }

    public function scheduledBy()
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function professional()
    {
        return $this->belongsTo(Partner::class, 'professional_id');
    }
}
