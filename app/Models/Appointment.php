<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id', 'partner_id', 'treatment_id',
        'insurance_id', 'location_id', 'room_id',
        'start_at', 'end_at', 'duration_min', 'status',
        // snapshot atual
        'price', 'repasse_type', 'repasse_value', 'treatment_table_id',
        // diversos
        'is_first_visit', 'notes', 'created_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_first_visit' => 'boolean',
        'price' => 'decimal:2',
        'repasse_value' => 'decimal:2',
    ];

    // Relations
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }

    public function insurance()
    {
        return $this->belongsTo(Insurance::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function table()
    {
        return $this->belongsTo(TreatmentTable::class, 'treatment_table_id');
    }

    public function payout()     { return $this->belongsTo(\App\Models\Payout::class); }
    public function payoutItem() { return $this->hasOne(\App\Models\PayoutItem::class); }

    // Overlap helper
    public function scopeOverlapping(Builder $q, Carbon $start, Carbon $end): Builder
    {
        return $q->where('start_at', '<', $end)
            ->where('end_at', '>', $start);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function reschedules()
    {
        return $this->hasMany(AppointmentReschedule::class);
    }

    // (opcional) computed property para exibir local bonitinho
    public function getPlaceAttribute(): string
    {
        $loc = $this->location?->name;
        $room = $this->room?->name;

        return trim(($loc ?: '—').($room ? " • {$room}" : ''));
    }

    public function getRepasseAmountAttribute(): float
    {
        $price = (float) ($this->price ?? 0);
        $val = (float) ($this->repasse_value ?? 0);
        if ($this->repasse_type === 'fixed') {
            return round($val, 2);
        }
        if ($this->repasse_type === 'percent') {
            return round($price * ($val / 100), 2);
        }

        return 0.0;
    }
}
