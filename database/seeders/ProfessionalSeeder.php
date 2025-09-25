<?php

namespace Database\Seeders;

use App\Models\Professional;
use App\Models\Specialty;
use Illuminate\Database\Seeder;

class ProfessionalSeeder extends Seeder
{
    public function run(): void
    {
        $p = Professional::firstOrCreate(['email' => 'ana@flowfisio.test'], [
            'name' => 'Dra. Ana Souza',
            'document' => null,
            'phone' => '(31) 90000-0000',
            'active' => true,
        ]);

        // vincular especialidades
        $specs = Specialty::take(2)->pluck('id')->all(); // pega duas de exemplo
        $p->specialties()->sync($specs);
    }
}
