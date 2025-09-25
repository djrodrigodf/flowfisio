<?php

namespace Database\Seeders;

use App\Models\Professional;
use App\Models\ProfessionalSchedule;
use App\Models\Room;
use Illuminate\Database\Seeder;

class ProfessionalScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $p = Professional::where('email', 'ana@flowfisio.test')->first();
        if (! $p) {
            return;
        }

        $room = Room::first();

        $items = [
            // Seg/Qua/Sex - 08:00-12:00 (slots de 40min)
            ['weekday' => 1, 'start' => '08:00:00', 'end' => '12:00:00', 'slot' => 40],
            ['weekday' => 3, 'start' => '08:00:00', 'end' => '12:00:00', 'slot' => 40],
            ['weekday' => 5, 'start' => '08:00:00', 'end' => '12:00:00', 'slot' => 40],
        ];

        foreach ($items as $i) {
            ProfessionalSchedule::firstOrCreate([
                'professional_id' => $p->id,
                'weekday' => $i['weekday'],
                'start_time' => $i['start'],
                'end_time' => $i['end'],
            ], [
                'slot_minutes' => $i['slot'],
                'room_id' => $room?->id,
                'active' => true,
            ]);
        }
    }
}
