<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $centro = Location::where('code', 'UNI-CENTRO')->first();
        $bairro = Location::where('code', 'UNI-BAIRROX')->first();

        $rooms = [
            ['location' => $centro, 'name' => 'Sala 01', 'code' => 'CEN-S01', 'capacity' => 1],
            ['location' => $centro, 'name' => 'Sala 02', 'code' => 'CEN-S02', 'capacity' => 1],
            ['location' => $bairro, 'name' => 'Sala 01', 'code' => 'BX-S01', 'capacity' => 1],
        ];

        foreach ($rooms as $r) {
            if (! $r['location']) {
                continue;
            }
            Room::firstOrCreate(
                ['location_id' => $r['location']->id, 'name' => $r['name']],
                ['code' => $r['code'], 'capacity' => $r['capacity'], 'active' => true]
            );
        }
    }
}
