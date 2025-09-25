<?php

namespace Database\Seeders;

use App\Models\Insurance;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $part = Insurance::where('code', 'PARTICULAR')->first();

        Patient::firstOrCreate(['document' => '12345678901'], [
            'name' => 'Paciente Exemplo',
            'gender' => 'M',
            'phone' => '(31) 90000-0000',
            'insurance_id' => $part?->id,
            'active' => true,
        ]);
    }
}
