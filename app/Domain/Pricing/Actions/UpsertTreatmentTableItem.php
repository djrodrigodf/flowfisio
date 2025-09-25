<?php

namespace App\Domain\Pricing\Actions;

use App\Models\TreatmentTable;
use App\Models\TreatmentTableItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpsertTreatmentTableItem
{
    public function handle(TreatmentTable $table, array $data, ?TreatmentTableItem $item = null): TreatmentTableItem
    {
        return DB::transaction(function () use ($table, $data, $item) {
            // Evitar duplicidade por tratamento
            $exists = $table->items()
                ->when($item, fn ($q) => $q->where('id', '!=', $item->id))
                ->where('treatment_id', $data['treatment_id'])
                ->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'treatment_id' => 'Já existe uma linha para este tratamento nesta tabela.',
                ]);
            }

            $payload = [
                'treatment_id' => $data['treatment_id'],
                'price' => $data['price'],
                'repasse_type' => $data['repasse_type'],
                'repasse_value' => $data['repasse_value'],
                'duration_min' => $data['duration_min'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];

            if ($item) {
                $item->fill($payload)->save();

                return $item;
            }

            return $table->items()->create($payload);
        });
    }
}
