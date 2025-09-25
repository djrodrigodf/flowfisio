<?php

namespace Database\Seeders;

use App\Models\Professional;
use App\Models\Room;
use Illuminate\Database\Seeder;

class ProfessionalRoomSeeder extends Seeder
{
    public function run(): void
    {
        $p = Professional::where('email', 'ana@flowfisio.test')->first();
        if (! $p) {
            return;
        }

        $rooms = Room::pluck('id')->take(2)->all();
        $p->rooms()->sync($rooms);
    }
}
