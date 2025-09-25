<?php

namespace App\Domain\Pricing\Actions;

use App\Models\TreatmentTable;
use Illuminate\Support\Facades\DB;

class CreateOrUpdateTreatmentTable
{
    public function handle(array $data, ?TreatmentTable $table = null): TreatmentTable
    {
        return DB::transaction(function () use ($data, $table) {
            $payload = [
                'name' => $data['name'],
                'insurance_id' => $data['insurance_id'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'partner_id' => $data['partner_id'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'effective_from' => $data['effective_from'] ?? null,
                'effective_to' => $data['effective_to'] ?? null,
                'priority' => $data['priority'] ?? 0,
                'created_by' => $data['created_by'] ?? auth()->id(),
            ];

            if ($table) {
                $table->fill($payload)->save();

                return $table;
            }

            return TreatmentTable::create($payload);
        });
    }
}
