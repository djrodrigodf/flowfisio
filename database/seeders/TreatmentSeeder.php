<?php

namespace Database\Seeders;

use App\Models\Specialty;
use App\Models\Treatment;
use App\Models\TreatmentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TreatmentSeeder extends Seeder
{
    public function run(): void
    {
        $spec = Specialty::where('slug', 'fisioterapia-ortopedica')->first()
            ?? Specialty::first(); // fallback
        $typeSessao = TreatmentType::where('slug', 'sessao')->first()
            ?? TreatmentType::first();

        $items = [
            ['name' => 'Sessão de Fisioterapia Ortopédica', 'valor_base' => 120.00],
            ['name' => 'Avaliação Inicial Ortopédica',      'valor_base' => 180.00],
        ];

        foreach ($items as $i) {
            $slug = Str::slug($i['name']);
            Treatment::firstOrCreate(
                ['slug' => $slug],
                [
                    'specialty_id' => $spec?->id,
                    'treatment_type_id' => $typeSessao?->id,
                    'name' => $i['name'],
                    'valor_base' => $i['valor_base'],
                    'active' => true,
                ]
            );
        }
    }
}
