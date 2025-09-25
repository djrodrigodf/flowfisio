<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TreatmentPrice extends Model
{
    protected $fillable = [
        'treatment_id', 'insurance_id', 'price', 'starts_at', 'ends_at',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'price' => 'decimal:2',
    ];

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }

    public function insurance()
    {
        return $this->belongsTo(Insurance::class);
    }

    // Escopo para vigência
    public function scopeEffectiveAt(Builder $q, $date)
    {
        return $q->where('starts_at', '<=', $date)
            ->where(function ($w) use ($date) {
                $w->whereNull('ends_at')->orWhere('ends_at', '>=', $date);
            });
    }
}
