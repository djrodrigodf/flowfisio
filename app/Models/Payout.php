<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $fillable = [
        'partner_id',
        'status',
        'period_start', 'period_end',
        'gross_total', 'adjustments_total', 'net_total',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'gross_total' => 'decimal:2',
        'adjustments_total' => 'decimal:2',
        'net_total' => 'decimal:2',
    ];

    protected $appends = ['period_label'];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function items()
    {
        return $this->hasMany(PayoutItem::class);
    }

    public function adjustments()
    {
        return $this->hasMany(PayoutAdjustment::class);
    }

    public function getPeriodLabelAttribute(): string
    {
        $ini = optional($this->period_start)?->format('d/m');
        $fim = optional($this->period_end)?->format('d/m/Y');

        // use " - " se quiser evitar multibyte
        return "{$ini} – {$fim}";
    }
}
