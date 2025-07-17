<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreRegistrationAdditionalResponsible extends Model
{
    use HasFactory;
    protected $table = 'pre_reg_additional_responsibles';

    protected $fillable = [
        'pre_registration_id',
        'name',
        'cpf',
        'kinship',
        'phone',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(PreRegistration::class, 'pre_registration_id');
    }
}

