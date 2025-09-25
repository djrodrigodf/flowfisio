<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'appointment_id',
        'method',       // cash|pix|card|boleto|insurance...
        'status',       // pending|paid|failed|canceled
        'amount',
        'amount_paid',
        'applied_to_due',
        'surcharge_amount',
        'received_at',  // ou paid_at
        'notes',
        'created_by',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'applied_to_due' => 'decimal:2',
        'surcharge_amount' => 'decimal:2',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
