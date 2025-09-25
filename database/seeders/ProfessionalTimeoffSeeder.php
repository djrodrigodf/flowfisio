<?php

namespace Database\Seeders;

use App\Models\Professional;
use App\Models\ProfessionalTimeoff;
use Illuminate\Database\Seeder;

class ProfessionalTimeoffSeeder extends Seeder
{
    public function run(): void
    {
        $p = Professional::where('email', 'ana@flowfisio.test')->first();
        if (! $p) {
            return;
        }

        // Exemplo: férias de 10 a 20 de janeiro de 2025
        ProfessionalTimeoff::firstOrCreate([
            'professional_id' => $p->id,
            'starts_at' => '2025-01-10 00:00:00',
            'ends_at' => '2025-01-20 23:59:59',
        ], [
            'reason' => 'Férias',
        ]);
    }
}
