<?php

// app/Domain/Calendar/Services/IsHoliday.php

namespace App\Domain\Calendar\Services;

use App\Models\Holiday;
use Carbon\Carbon;

class IsHoliday
{
    /**
     * Retorna o Holiday aplicável (ou null) para data+escopo.
     * $scopeType: null|'location'|'room'|'professional'
     * $scopeId:   id do alvo (quando houver)
     */
    public function for(?string $scopeType, ?int $scopeId, Carbon $date): ?Holiday
    {
        $md = $date->format('m-d');

        return Holiday::query()
            ->where('active', true)
            // match global OU escopo específico
            ->where(function ($q) use ($scopeType, $scopeId) {
                $q->whereNull('scope'); // global
                if ($scopeType && $scopeId) {
                    $q->orWhere(fn ($w) => $w->where('scope', $scopeType)->where('scope_id', $scopeId));
                }
            })
            // match recorrente (mês-dia) OU exata (com ano)
            ->where(function ($q) use ($date, $md) {
                $q->where(function ($w) use ($md) {
                    $w->where('is_recurring', true)->whereRaw("DATE_FORMAT(`date`, '%m-%d') = ?", [$md]);
                })->orWhere(function ($w) use ($date) {
                    $w->where('is_recurring', false)->whereDate('date', $date->toDateString());
                });
            })
            ->orderByRaw('CASE WHEN scope IS NULL THEN 0 ELSE 1 END') // escopo específico > global (se quiser inverter, troque)
            ->first();
    }
}
