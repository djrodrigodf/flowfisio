<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\ScheduleBlock;
use Illuminate\Database\Seeder;

class ScheduleBlockSeeder extends Seeder
{
    public function run(): void
    {
        $room = Room::first();
        if (! $room) {
            return;
        }

        // Exemplo: manutenção da sala no dia 2025-02-05 das 09:00 às 10:30
        ScheduleBlock::firstOrCreate([
            'room_id' => $room->id,
            'starts_at' => '2025-02-05 09:00:00',
            'ends_at' => '2025-02-05 10:30:00',
        ], [
            'reason' => 'Manutenção',
        ]);
    }
}
