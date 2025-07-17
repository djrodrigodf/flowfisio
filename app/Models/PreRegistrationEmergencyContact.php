<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreRegistrationEmergencyContact extends Model
{
    use HasFactory;
    protected $fillable = [
        'pre_registration_id',
        'name',
        'kinship',
        'phone',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(PreRegistration::class, 'pre_registration_id');
    }
}
