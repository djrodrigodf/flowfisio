<?php

namespace Database\Seeders;

use App\Models\Insurance;
use App\Models\Treatment;
use App\Models\TreatmentPrice;
use Illuminate\Database\Seeder;

class TreatmentPriceSeeder extends Seeder
{
    public function run(): void
    {
        $part = Insurance::where('code', 'PARTICULAR')->first();
        $unimed = Insurance::where('code', 'UNIMED')->first();

        $t = Treatment::where('slug', 'sessao-de-fisioterapia-ortopedica')->first();
        $a = Treatment::where('slug', 'avaliacao-inicial-ortopedica')->first();

        $data = [
            // Sessão ortopédica
            ['t' => $t, 'ins' => $part,  'price' => 120.00, 'starts' => '2025-01-01', 'ends' => null],
            ['t' => $t, 'ins' => $unimed, 'price' => 90.00,  'starts' => '2025-01-01', 'ends' => null],

            // Avaliação
            ['t' => $a, 'ins' => $part,  'price' => 180.00, 'starts' => '2025-01-01', 'ends' => null],
            ['t' => $a, 'ins' => $unimed, 'price' => 150.00, 'starts' => '2025-01-01', 'ends' => null],
        ];

        foreach ($data as $d) {
            if (! $d['t']) {
                continue;
            }
            TreatmentPrice::firstOrCreate(
                [
                    'treatment_id' => $d['t']->id,
                    'insurance_id' => $d['ins']?->id,
                    'starts_at' => $d['starts'],
                    'ends_at' => $d['ends'],
                ],
                ['price' => $d['price']]
            );
        }
    }
}
