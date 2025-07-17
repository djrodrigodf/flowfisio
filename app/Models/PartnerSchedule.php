<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerSchedule extends Model
{
    protected $fillable = [
        'partner_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'string',
        'end_time' => 'string',
    ];

    public const DIAS_SEMANA = [
        'monday' => 'Segunda-feira',
        'tuesday' => 'Terça-feira',
        'wednesday' => 'Quarta-feira',
        'thursday' => 'Quinta-feira',
        'friday' => 'Sexta-feira',
        'saturday' => 'Sábado',
        'sunday' => 'Domingo',
    ];

    /**
     * Retorna o parceiro vinculado ao horário.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Retorna o label do dia da semana (pt-BR).
     */
    public function getDayLabelAttribute(): string
    {
        return self::DIAS_SEMANA[$this->day_of_week] ?? ucfirst($this->day_of_week);
    }
}
