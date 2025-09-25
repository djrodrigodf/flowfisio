<?php

// app/Domain/Pricing/Services/ResolvePrice.php

namespace App\Domain\Pricing\Services;

use App\Models\Treatment;
use App\Models\TreatmentTable;
use App\Models\TreatmentTableItem;
use Carbon\Carbon;

class ResolvePrice
{
    /**
     * Retorna:
     * [
     *   'price' => decimal|null,
     *   'repasse_type' => 'percent'|'fixed'|null,
     *   'repasse_value' => decimal|null,
     *   'duration_min' => int|null,
     *   'table_id' => int|null,
     * ]
     */
    public function handle(
        int $treatmentId,
        ?int $insuranceId,
        ?int $locationId,
        ?int $partnerId,
        Carbon $date
    ): array {
        // 1) Procura uma tabela publicada válida na data
        $table = TreatmentTable::query()
            ->where('status', 'published')
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            })
            // se você tiver colunas de “escopo” (por convênio/unidade/parceiro),
            // aplique os filtros de prioridade aqui
            ->orderByDesc('id') // heurística simples
            ->first();

        if ($table) {
            $item = TreatmentTableItem::query()
                ->where('treatment_table_id', $table->id)
                ->where('treatment_id', $treatmentId)
                ->first();

            if ($item) {
                return [
                    'price' => (float) $item->price,
                    'repasse_type' => $item->repasse_type,
                    'repasse_value' => (float) $item->repasse_value,
                    'duration_min' => $item->duration_min ?: null,
                    'table_id' => $table->id,
                ];
            }
        }

        // 2) Fallback: usa o preço base do tratamento e duração padrão (ex.: 40)
        $treatment = Treatment::select('id', 'valor_base')->find($treatmentId);

        return [
            'price' => $treatment?->valor_base ? (float) $treatment->valor_base : 0.0,
            'repasse_type' => null,
            'repasse_value' => null,
            'duration_min' => 40, // default quando não vier da tabela
            'table_id' => null,
        ];
    }
}
