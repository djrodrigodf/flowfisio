<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutItem extends Model
{
    protected $fillable = [
        'payout_id', 'appointment_id', 'partner_id', 'treatment_id', 'service_date', 'payout_value', 'metadata',
    ];

    protected $casts = [
        'service_date' => 'date',
        'payout_value' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }
}
