<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Unidade Centro', 'code' => 'UNI-CENTRO', 'address' => 'Rua Principal, 100', 'city' => 'Belo Horizonte', 'state' => 'MG', 'active' => true],
            ['name' => 'Unidade Bairro X', 'code' => 'UNI-BAIRROX', 'address' => 'Av. das Acácias, 200', 'city' => 'Belo Horizonte', 'state' => 'MG', 'active' => true],
        ];

        foreach ($data as $d) {
            Location::firstOrCreate(['code' => $d['code']], $d);
        }
    }
}
