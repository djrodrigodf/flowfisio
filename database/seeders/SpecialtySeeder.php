<?php

namespace Database\Seeders;

use App\Models\Specialty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SpecialtySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'Fisioterapia Ortopédica',
            'Fisioterapia Neurológica',
            'Fisioterapia Respiratória',
            'Fisioterapia Pediátrica',
            'Fisioterapia Desportiva',
        ];

        foreach ($items as $name) {
            Specialty::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'active' => true]
            );
        }
    }
}
