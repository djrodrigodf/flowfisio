<?php

namespace Database\Seeders;

use App\Models\Insurance;
use App\Models\Treatment;
use App\Models\TreatmentPayout;
use Illuminate\Database\Seeder;

class TreatmentPayoutSeeder extends Seeder
{
    public function run(): void
    {
        $part = Insurance::where('code', 'PARTICULAR')->first();
        $unimed = Insurance::where('code', 'UNIMED')->first();

        $t = Treatment::where('slug', 'sessao-de-fisioterapia-ortopedica')->first();
        $a = Treatment::where('slug', 'avaliacao-inicial-ortopedica')->first();

        $items = [
            // Sessão: repasse 40% no particular, R$35 no convênio
            ['t' => $t, 'ins' => $part,  'mode' => 'percent', 'value' => 40.00, 'starts' => '2025-01-01', 'ends' => null],
            ['t' => $t, 'ins' => $unimed, 'mode' => 'fixed',   'value' => 35.00, 'starts' => '2025-01-01', 'ends' => null],

            // Avaliação: repasse fixo de R$60 em ambos
            ['t' => $a, 'ins' => $part,  'mode' => 'fixed',   'value' => 60.00, 'starts' => '2025-01-01', 'ends' => null],
            ['t' => $a, 'ins' => $unimed, 'mode' => 'fixed',   'value' => 60.00, 'starts' => '2025-01-01', 'ends' => null],
        ];

        foreach ($items as $i) {
            if (! $i['t']) {
                continue;
            }
            TreatmentPayout::firstOrCreate(
                [
                    'treatment_id' => $i['t']->id,
                    'insurance_id' => $i['ins']?->id,
                    'mode' => $i['mode'],
                    'starts_at' => $i['starts'],
                    'ends_at' => $i['ends'],
                ],
                ['value' => $i['value']]
            );
        }
    }
}
