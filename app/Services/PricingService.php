<?php

namespace App\Services;

use App\Models\Insurance;
use App\Models\Treatment;
use App\Models\TreatmentPayout;
use App\Models\TreatmentPrice;
use Carbon\Carbon;

class PricingService
{
    /**
     * Retorna o preço efetivo do tratamento na data ($date) para o convênio (ou particular se null).
     */
    public function resolvePrice(Treatment $treatment, ?Insurance $insurance, Carbon $date): float
    {
        // 1) tenta por convênio específico
        $price = TreatmentPrice::where('treatment_id', $treatment->id)
            ->where('insurance_id', $insurance?->id)
            ->effectiveAt($date)
            ->orderByDesc('starts_at')
            ->first();

        // 2) fallback: particular (insurance_id NULL)
        if (! $price) {
            $price = TreatmentPrice::where('treatment_id', $treatment->id)
                ->whereNull('insurance_id')
                ->effectiveAt($date)
                ->orderByDesc('starts_at')
                ->first();
        }

        // 3) fallback final: valor_base
        return $price?->price ?? (float) $treatment->valor_base;
    }

    /**
     * Retorna o repasse ao profissional baseado no preço final e vigência.
     */
    public function resolvePayout(Treatment $treatment, ?Insurance $insurance, Carbon $date, float $priceFinal): float
    {
        $payout = TreatmentPayout::where('treatment_id', $treatment->id)
            ->where('insurance_id', $insurance?->id)
            ->effectiveAt($date)
            ->orderByDesc('starts_at')
            ->first();

        if (! $payout) {
            $payout = TreatmentPayout::where('treatment_id', $treatment->id)
                ->whereNull('insurance_id')
                ->effectiveAt($date)
                ->orderByDesc('starts_at')
                ->first();
        }

        if (! $payout) {
            return 0.0;
        }

        if ($payout->mode === 'fixed') {
            return (float) $payout->value;
        }

        // percent
        return round(($priceFinal * ((float) $payout->value / 100)), 2);
    }
}
