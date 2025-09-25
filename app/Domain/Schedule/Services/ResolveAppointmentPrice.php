<?php

namespace App\Domain\Schedule\Services;

use App\Domain\Pricing\Services\ResolvePrice;
use App\Models\Treatment;
use Carbon\Carbon;

class ResolveAppointmentPrice
{
    public function __construct(private ResolvePrice $resolver) {}

    /**
     * Retorna array: [price, repasse_type, repasse_value, treatment_table_id, duration_min]
     */
    public function handle(
        int $treatmentId,
        ?int $insuranceId,
        ?int $locationId,
        ?int $partnerId,
        Carbon $date
    ): array {
        $hit = $this->resolver->handle($treatmentId, $insuranceId, $locationId, $partnerId, $date);

        if ($hit) {
            return [
                'price' => $hit['price'],
                'repasse_type' => $hit['repasse_type'],
                'repasse_value' => $hit['repasse_value'],
                'treatment_table_id' => $hit['table_id'],
                'duration_min' => $hit['duration_min'],
            ];
        }

        // Fallback: pegar do tratamento base (seu model Treatment deve ter price/duration)
        /** @var Treatment $t */
        $t = Treatment::find($treatmentId);

        return [
            'price' => (float) ($t?->price ?? 0),
            'repasse_type' => 'percent',
            'repasse_value' => 0,
            'treatment_table_id' => null,
            'duration_min' => (int) ($t?->duration_min ?? 50),
        ];
    }
}
