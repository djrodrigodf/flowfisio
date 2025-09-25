<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['date' => '2025-01-01', 'scope' => 'national', 'state' => null, 'city' => null, 'description' => 'Confraternização Universal'],
            ['date' => '2025-04-21', 'scope' => 'national', 'state' => null, 'city' => null, 'description' => 'Tiradentes'],
            // exemplo municipal (BH):
            ['date' => '2025-12-08', 'scope' => 'city', 'state' => 'MG', 'city' => 'Belo Horizonte', 'description' => 'Nossa Senhora da Conceição'],
        ];

        foreach ($items as $i) {
            Holiday::firstOrCreate(
                [
                    'date' => $i['date'],
                    'scope' => $i['scope'],
                    'state' => $i['state'],
                    'city' => $i['city'],
                ],
                ['description' => $i['description']]
            );
        }
    }
}
