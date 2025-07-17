<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreRegistrationLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'specialty',
        'token',
        'user_id',
    ];

    public function preRegistrations(): HasMany
    {
        return $this->hasMany(PreRegistration::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
