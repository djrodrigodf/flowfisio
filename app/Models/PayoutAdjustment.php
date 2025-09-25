<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutAdjustment extends Model
{
    protected $fillable = [
        'payout_id', 'amount', 'type', 'reason', 'user_id', 'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
