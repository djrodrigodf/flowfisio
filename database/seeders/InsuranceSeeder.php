<?php

namespace Database\Seeders;

use App\Models\Insurance;
use Illuminate\Database\Seeder;

class InsuranceSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Particular', 'code' => 'PARTICULAR', 'active' => true],
            ['name' => 'Unimed',     'code' => 'UNIMED',     'active' => true],
            ['name' => 'Bradesco',   'code' => 'BRADESCO',   'active' => true],
        ];

        foreach ($items as $i) {
            Insurance::firstOrCreate(['code' => $i['code']], $i);
        }
    }
}
