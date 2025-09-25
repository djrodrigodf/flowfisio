<?php

namespace Database\Seeders;

use App\Models\TreatmentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TreatmentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $items = ['Avaliação', 'Sessão', 'Retorno'];

        foreach ($items as $name) {
            TreatmentType::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'active' => true]
            );
        }
    }
}
